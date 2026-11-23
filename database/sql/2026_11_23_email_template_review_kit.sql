-- WorldWeb Directory: the "review kit" email to business owners, sent by WWD Admin (owner decisions 23 Nov 2026).
-- Adds one row to the shared email templates (WWD Admin -> Email templates), where admins can change the wording.
-- The daily job `php artisan notifications:review-kit` sends it once per public business, at most 50 a day.
--
-- Run in phpMyAdmin AFTER the WWD Admin redesign is live (it creates the email_templates table).
-- Safe to run twice: INSERT IGNORE keeps an existing row (and any wording an admin changed).
-- Until this row exists (or while it's switched off in WWD Admin), the job sends nothing.

INSERT IGNORE INTO `email_templates`
  (`template_key`, `name`, `description`, `project`, `trigger_type`, `is_system`, `email_layout_id`,
   `subject`, `preheader`, `header_title`, `header_subtitle`, `body`, `button_text`, `button_url`, `footer_note`,
   `variables`, `is_active`, `created_at`, `updated_at`)
VALUES
  ('review_kit',
   'Review kit (get more reviews)',
   'Sent once by WWD Admin''s daily review kit job when a business is public on WorldWeb Directory: review link, QR code and the Get reviews page. At most 50 a day.',
   'admin', 'scheduled', 1, NULL,
   'Get more reviews for {{BusinessName}}',
   'Your review link and QR code for {{BusinessName}} on WorldWeb Directory.',
   'Get more reviews',
   '{{BusinessName}} is live on WorldWeb Directory',
   '<p>Hi {{BusinessOwnerName}},</p><p>Reviews are the first thing visitors look at on WWD. In your category, businesses with better ratings and more reviews are listed higher. Share this link with your customers:</p><p style="margin:0 0 18px; padding:12px 14px; background:#f8f9fb; border:1px solid #e3e6eb; border-radius:8px; word-break:break-all;"><a href="{{ReviewURL}}" style="color:{{BrandColor}};">{{ReviewURL}}</a></p><p style="text-align:center; margin:0 0 6px;"><img src="{{QRURL}}" width="160" height="160" alt="QR code that opens your review form" style="border:1px solid #e3e6eb; border-radius:8px;"></p><p style="text-align:center; color:#5b6474; font-size:13px; margin:0 0 18px;">Put this QR code on your counter or invoices: customers scan it with their phone camera.</p><p>In WWD for Business you&rsquo;ll find the link with WhatsApp and email buttons, printable review cards and a badge for your website.</p><p style="color:#5b6474; font-size:13px;">Ask every customer, not only the happy ones, and never offer anything in return for a review.</p>',
   'Open Get reviews', '{{GetReviewsURL}}',
   'You’re receiving this email because {{BusinessName}} is listed on World Web Directory.',
   '[{"name":"BusinessOwnerName","sample":"Priya Sharma","description":"Business owner full name"},{"name":"BusinessName","sample":"Sunrise Bakery","description":"Business name"},{"name":"ReviewURL","sample":"https://worldweb-directory.com/review/sunrise-bakery?src=email","description":"Review link (opens the review form)"},{"name":"QRURL","sample":"https://worldweb-directory.com/review/sunrise-bakery/qr","description":"QR code picture for the review link"},{"name":"GetReviewsURL","sample":"https://biz.worldweb-directory.com/get-reviews","description":"Get reviews page in WWD Business"},{"name":"ListingURL","sample":"https://worldweb-directory.com/review/sunrise-bakery","description":"The public business page"}]',
   1, NOW(), NOW());
