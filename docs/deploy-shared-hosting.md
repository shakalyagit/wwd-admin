# Deploying WWD Admin to shared hosting (cPanel)

For the release on branch `feature/admin-redesign` (redesign, notifications, email templates, profile score,
approval queue, click tracking). Production already runs the old code.

Replace `/home/USER/public_html/webadmin` below with the real folder of the admin app (the folder that contains
`artisan`, `app/`, `vendor/`). Commands run in **cPanel → Terminal** (or SSH) inside that folder.

What this release needs: **no new PHP packages** (no `composer install`), **5 database migrations**,
**2 new `.env` settings**, new files in `public/assets`, and **a cron job**.

---

## 1. Back up (5 min)

1. **Database**: phpMyAdmin → select the database → **Export** → Quick → SQL → Go. Keep the file.
2. **Code**: cPanel **File Manager** → right-click the admin folder → **Compress** → Zip. Download the zip.

These two files are your rollback.

## 2. Put the site in maintenance mode

```bash
cd /home/USER/public_html/webadmin
php artisan down
```

## 3. Upload the new code

**A. If the server folder is a git clone** (`git status` works there):
```bash
git fetch origin
git checkout feature/admin-redesign
git pull origin feature/admin-redesign
```
(Once merged, deploy `main` instead: `git checkout main && git pull origin main`.)

**B. If you upload files (no git on the server):**
1. On your PC, zip the project **without** `vendor/`, `.env`, `storage/`, `node_modules/`, `public/wwdbucket`.
2. File Manager → upload the zip into the admin folder → **Extract** → overwrite.
3. Delete these files on the server (removed in this release):
   - `app/Mail/BusinessApprovedMail.php`
   - `resources/views/emails/business-approved.blade.php`
   - `resources/views/users/off_canvas.blade.php`

Never overwrite the server's `.env`, `vendor/` or `storage/`.

## 4. Update `.env` on the server

Edit `.env` (File Manager → Edit) and add / check:

```dotenv
APP_URL=https://webadmin.worldweb-directory.com      # exact public address of the admin (used by email click tracking)
WWD_CLAIM_URL=https://biz.worldweb-directory.com/claim-business
WWD_BUSINESS_LOGIN_URL=https://biz.worldweb-directory.com/login
UPLOAD_URL=...                                      # keep the existing value (must end with the logo folder, e.g. .../wwdbucket/logo/)
```

Keep the real `MAIL_*` settings as they are.

## 5. Database

### 5a. Record the two old migrations that already exist

The `old_businesses` / `old_categories` tables exist on production, but the `migrations` table doesn't know it,
so a normal migrate would try to create them again and fail. Check:

```bash
php artisan migrate:status
```

If `2025_12_25_095041_create_old_businesses_table` and `2025_12_25_174712_create_old_categories_table` show
**Pending**, run this in phpMyAdmin → SQL:

```sql
INSERT INTO migrations (migration, batch)
SELECT m.migration, (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations)
FROM (SELECT '2025_12_25_095041_create_old_businesses_table' AS migration
      UNION ALL SELECT '2025_12_25_174712_create_old_categories_table') m
WHERE NOT EXISTS (SELECT 1 FROM migrations x WHERE x.migration = m.migration);
```

### 5b. Preview, then run the new migrations

```bash
php artisan migrate --pretend    # must list ONLY the 5 migrations below
php artisan migrate --force
```

Expected (and nothing else):
- `2026_10_27_000002_create_notification_logs_table`
- `2026_10_27_000003_add_notifications_paused_to_businesses_table` (adds 1 column to `businesses`)
- `2026_10_29_000001_create_email_layouts_table`
- `2026_10_29_000002_create_email_templates_table` (also creates the 11 email templates)
- `2026_11_02_000001_add_click_tracking_to_notification_logs_table`

If `--pretend` lists anything else (e.g. `create_users_table`), **stop** and don't run migrate.

## 6. Clear caches

```bash
php artisan optimize:clear
```

If you later see "Class … not found": `composer dump-autoload -o` (only if composer is available).

## 7. Bring the site back

```bash
php artisan up
```

## 8. Check it works (10 min)

- [ ] Log in; the new design, sidebar and dashboard load.
- [ ] **Businesses**: logos show, **Ready for approval** tab has a count.
- [ ] **Email templates**: 11 templates. Open *Business approved* → **Send test** to your own email.
      In your inbox: logo, red header, button. Click the button: it should open the WWD Business login page.
- [ ] **Email templates → Master layout**: logo URL loads in the preview.
- [ ] **Notifications** page opens (empty at first).
- [ ] Optional dry run of the reminders (sends nothing):
      `php artisan notifications:verification-reminders --dry-run`

## 9. Add the cron job (scheduled reminders)

Laravel's `schedule:run` does **not** work on this host (it runs, but never starts the reminder), so cron calls
the command directly. cPanel → **Cron Jobs** → Add new → Minute `59`, Hour `23`, Day/Month/Weekday `*`, command:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php /home/USER/public_html/webadmin/artisan notifications:verification-reminders >> /home/USER/public_html/webadmin/storage/logs/reminders.log 2>&1
```

`reminders.log` gets one line per run (`Verification reminders sent: N, failed: N`). To test first, run the same command
with `--dry-run` in a temporary once-per-minute job (it only lists who would be emailed), then delete that job.
cPanel uses the server's clock, so the run is at 23:59 server time.

Use the PHP path of the PHP version the site runs (Terminal: `which php`, or cPanel's
`/opt/cpanel/ea-php83/root/usr/bin/php` for PHP 8.3).

The verification reminder runs daily at **11:59 PM UTC**. It only emails businesses submitted **from the day the
migration ran** onwards. To hold sending, switch the template off: Email templates → *Business verification
reminder* → Active off.

## Rollback

```bash
php artisan down
```
Restore the code zip (File Manager → Extract over the folder) and import the database backup in phpMyAdmin, then
`php artisan optimize:clear && php artisan up`.
