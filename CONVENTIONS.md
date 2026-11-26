# WWD Admin — Project Conventions

## Goal
Admin panel for **World Web Directory (WWD)**, a business listing directory. Admins review, verify, approve or reject business listings, manage users and subscriptions/payments, migrate legacy ("old") businesses, and view category stats.

## Stack
- Laravel 10, PHP 8.1, MySQL; Blade server-rendered views
- UI: Falcon-style Bootstrap 5 theme + jQuery (`$.ajax`, DataTables, select2, flatpickr). Vendor JS/CSS live in `public/assets/vendors` (no npm build for these).
- `intervention/image` (GD) for logo resizing; `rinvex/countries` (`countries()` helper)
- Mail: `BusinessApprovedMail` sent on approval

## UI
- Design layer: `public/assets/css/wwd-admin.css` (tokens on `:root`, WWD red `#e53935`, Inter, Bootstrap Icons `bi-*`). Restyle there; no inline `<style>` hacks, no Font Awesome.
- Shell: `layouts/header` → `sidebar` (sidebar + topbar, menu array at top) → page → `footer`. Add menu items to `$nav_sections` in `sidebar.blade.php`.
- Pages: `.content-header` (h5 + optional `.page-subtitle` + actions), cards, `.table` inside `.table-responsive`; statuses via badge helpers.
- Footer loads only Bootstrap, jQuery, jQuery Validate, `custom.js`. Page-only libraries (ECharts, TinyMCE) go in `@push('vendor_scripts')`.

## Architecture principles
- **Shared DB with the public WWD site.** Core tables (`businesses`, `categories`, `users`, `media`, …) are owned by the main app: no migrations here except `old_*` tables. Don't add migrations for shared tables without checking.
- **Shared file storage:** `Storage::disk('shared')` → `../../wwdbucket` (logos in `logo/`).
- Fat controllers, thin models: queries, validation and business logic sit inline in controller methods. No services, form requests or repositories.
- Models use custom PKs (`business_id`, `category_id`, …) via `$primaryKey`.
- Multi-table writes go in `DB::transaction()`.

## Coding conventions
- **snake_case everywhere:** controller methods (`edit_business_action`), route names (`business_list`), variables, helper functions. Route URLs are kebab-case (`edit-business/{business_id}`).
- Page = `GET name` → view; form submit = `POST name_action`; AJAX list filter = `*_filter` returning `response()->json(['html', 'pagination', 'page'])`.
- IDs in URLs are **encrypted** (`encrypt()`/`decrypt()`).
- Validation: `$request->validate([...], [custom messages])` inline.
- Responses: `redirect()->back()->with('success'|'error', '…')`, shown via `<x-flash />`.
- Queries: Eloquent with `leftjoin` + `select` aliases (`categories.cat_name as category_name`); lists `paginate(50)`.
- Global helpers in `app/Helpers/*.php` (composer `files` autoload), wrapped in `if (!function_exists(...))`; badge helpers return HTML strings (`status_badge`, `payment_badge`).
- Status codes: `0` pending/inactive, `1` verified/active, `2` rejected.
- Views: `resources/views/<module>/{index,edit,view,off_canvas}.blade.php`, `@extends('layouts.main')`, filters in Bootstrap offcanvas, partials in `<module>/partials/`.
- All admin routes sit inside the `auth` middleware group in `routes/web.php`; login in the `guest` group.
- Formatting: 4 spaces, LF, UTF-8 (`.editorconfig`); Pint is available (`vendor/bin/pint`).

## Email templates (shared by all WWD projects)
- `email_templates` (one row per `template_key`) + `email_layouts` (master layout) are shared by WWD Admin, Business and Listing; admins edit them under **Email templates**. Placeholders are `{{PascalCase}}`.
- Render only with `App\Helpers\EmailTemplateRenderer` (framework-free; the other projects copy it). Guide + key catalogue: `docs/email-templates.md`.
- System templates (`is_system = 1`) are referenced by code: never rename their keys; add new ones in `EmailTemplateSeeder` (insert-only, never overwrites admin edits).
- Never write email HTML in Blade/Mailables; `NotificationMail` only carries rendered HTML.

## Notifications module
- `notification_logs` records every send (type = template key, business, step, auto/manual, sent/failed).
- Click tracking: `NotificationSender::send()` swaps an email's http(s) button link for the public `/t/{token}` route (`email_click`, outside auth), which records `clicked_at`/`click_count` (bots and HEAD ignored) and forwards to `target_url`. Base URL = `WWD_TRACKING_URL` (defaults to `APP_URL`, must be publicly reachable).
- Sent log (Notifications) shows one row per day + template; details load via `notification_details`.
- Send business emails via `App\Helpers\NotificationSender::send($template, $business, $owner)`; it fills the business placeholders, renders, mails, logs, and skips inactive templates. New reminder types = template row + command + schedule entry.
- `businesses.notifications_paused` stops all reminders for a business.
- Verification reminder: `notifications:verification-reminders` (`--dry-run`), daily at 23:59 UTC, day 0/1/3/15 then every 15 days while `is_claimed = 0`. On production (shared cPanel) the Laravel scheduler can't start it (`schedule:run` runs but the command never fires), so cPanel calls the command directly: `59 23 * * * php artisan notifications:verification-reminders >> storage/logs/reminders.log 2>&1`. Running it more than once a day is safe: a step is never sent twice.

## Profile score
- `business_profile_score($business, $has_logo, $has_hours, $has_address)` in `CommonHelper.php` is the single source of the 100-point score + colour band (green = submitted + owner verified + logo uploaded, i.e. ready for admin approval; yellow = verified, no logo; red = not verified). Admin approval is not scored; `profile_score_badge()` renders the circle. The list's SQL band filter in `business_list_filter` must match it.

## Workflow
- Branches: work on `dev`, merge to `main` via PR. Short lowercase commit messages.
- **Commits are authored as the repo owner's account (`shakalyagit`) only.** Never add `Co-Authored-By: Claude`, "Generated with Claude Code", or any AI attribution to commits or PR descriptions. Never change `user.name`/`user.email` or pass `--author` without being asked.
- Tests are boilerplate only; verify changes manually in the browser.

## Known gotchas
- Unused imports of non-existent classes: `App\Livewire\AddBusiness` (routes) and `App\Support\BusinessUrlValidator` (BusinessController). Create them before using.
- `RiskRegisterController` and `app/Helpers/Webhook.php` are leftovers from another project (they reference models that don't exist). Don't build on them.
- Column typo `instragram_url` is real (it's the DB column), so keep it.
