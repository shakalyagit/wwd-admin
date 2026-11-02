# WWD email templates – guide for all projects

WWD Admin, WWD Business and WWD Listing share one database. Every email they send uses a template from
the **`email_templates`** table, wrapped in the master layout from **`email_layouts`**. Admins edit both in
WWD Admin → **Email templates**; projects never hard-code email HTML.

Each project still sends with **its own mail settings**. The template master only decides *what* is sent.

## How to send an email

1. Load the template by its key, only if active:
   ```sql
   SELECT * FROM email_templates WHERE template_key = 'business_submitted' AND is_active = 1
   ```
   No row → the email is switched off (or missing): **don't send**.
2. Load its layout: `email_layouts` row with `email_layout_id = template.email_layout_id`, or the row with `is_default = 1` when that is null.
3. Render with **`EmailTemplateRenderer`**: copy `app/Helpers/EmailTemplateRenderer.php` from WWD Admin into your project unchanged (only adjust the `namespace`). Plain PHP, no dependencies.
   ```php
   $email = EmailTemplateRenderer::render($template, $layout, [
       'BusinessOwnerName' => 'Priya Sharma',
       'BusinessName'      => 'Sunrise Bakery',
       'LoginURL'          => 'https://biz.worldweb-directory.com/login',
   ]);
   // $email['subject'] (plain text), $email['html'] (full HTML email)
   ```
4. Send `$email['subject']` + `$email['html']` with your mailer. Laravel:
   ```php
   Mail::html($email['html'], fn ($m) => $m->to($to)->subject($email['subject']));
   ```
5. Optional: for business emails, add a row to `notification_logs` so it appears in WWD Admin → Notifications → Sent log:
   `type` = template key, `business_id`, `recipient_email`, `subject`, `trigger` = `auto`, `status` = `sent`/`failed`, `error`, `sent_at`.

## Click tracking (optional, for logged business emails)

WWD Admin counts clicks on an email's **button**. To track your emails the same way:

1. Work out the button's real link: `EmailTemplateRenderer::fill($template['button_url'], EmailTemplateRenderer::variables($layout, $data), false)`.
2. If it starts with `http`, create a random 40-character token (letters and digits) and send the template with
   `button_url` replaced by `https://webadmin.worldweb-directory.com/t/{token}` (WWD Admin's `WWD_TRACKING_URL`).
3. Save `tracking_token` = the token and `target_url` = the real link on the `notification_logs` row.

When the owner clicks, WWD Admin records `clicked_at` / `click_count` and forwards them to `target_url`.
Mail security scanners and bots are not counted.

`$template` and `$layout` can be arrays or objects (e.g. a PDO row or `$model->getAttributes()`).

## Rules

- **Placeholders** are written `{{Name}}`: letters, numbers, underscore, case-sensitive (`{{BusinessName}}`).
- Pass placeholder values **without braces**. Values are HTML-escaped automatically; don't escape them yourself.
- Unknown placeholders are removed, so a missing value shows as empty text, never as `{{...}}`.
- Always available: `{{Year}}`, `{{SiteURL}}`, `{{SupportEmail}}`, `{{LogoURL}}`, `{{BrandColor}}` (from the layout).
- **Never change a system template key** in code without renaming it in the database too; admins can't rename system keys.
- OTP and password-reset emails: send immediately in the request.
- New email type? Ask an admin to add a template (or add it to `EmailTemplateSeeder` in WWD Admin as a system template), then load it by its key.

## Template catalogue

| Key | Sent by | When | Placeholders |
|---|---|---|---|
| `business_submitted` | WWD Business | Owner submits a new business | BusinessOwnerName, BusinessName, LoginURL |
| `business_approved` | WWD Admin | Admin approves a business | BusinessOwnerName, BusinessName, LoginURL |
| `business_rejected` | WWD Admin | Admin rejects a business (reason picked in the reject popup) | BusinessOwnerName, BusinessName, LoginURL, Reason |
| `claim_business` | WWD Admin | Approved business not yet claimed | BusinessOwnerName, BusinessName, ClaimURL |
| `verification_reminder` | WWD Admin (scheduler) | Day 0/1/3/15 after submission, then every 15 days, until verified | BusinessOwnerName, BusinessName, ClaimURL |
| `otp_verification` | WWD Business | OTP requested (e.g. claiming a business) | UserName, OTPCode, OTPValidMinutes, CurrentDate |
| `password_reset` | All | Password reset requested | UserName, ResetURL, ResetValidHours |
| `subscription_confirmation` | WWD Business | Plan purchased / upgraded | BusinessOwnerName, BusinessName, LoginURL, PlanName, StartDate, BillingCycle, Amount, CurrentDate |
| `premium_welcome` | WWD Business | Upgraded to Premium | BusinessOwnerName, BusinessName, LoginURL, Amount |
| `profile_action_needed` | WWD Admin (scheduler) | Profile hidden from searches | BusinessOwnerName, BusinessName, LoginURL |
| `premium_promotion` (custom) | WWD Admin | Premium offer campaign | BusinessOwnerName, BusinessName, RegularPrice, OfferPrice, UpgradeURL |

The up-to-date list, with sample values, is always in WWD Admin → Email templates.
