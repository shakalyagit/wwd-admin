<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Starting email templates, converted from the designs in "WWD/Email Template".
 * Only inserts keys that don't exist yet, so admin edits are never overwritten.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $owner = ['name' => 'BusinessOwnerName', 'sample' => 'Priya Sharma', 'description' => 'Business owner full name'];
        $business = ['name' => 'BusinessName', 'sample' => 'Sunrise Bakery', 'description' => 'Business name'];
        $login = ['name' => 'LoginURL', 'sample' => 'https://biz.worldweb-directory.com/login', 'description' => 'WWD Business login page'];
        $user = ['name' => 'UserName', 'sample' => 'Priya Sharma', 'description' => 'Recipient full name'];

        $templates = [
            [
                'template_key'    => 'business_submitted',
                'name'            => 'Business submitted',
                'description'     => 'Sent by WWD Business right after an owner submits a new business.',
                'project'         => 'business',
                'trigger_type'    => 'event',
                'subject'         => 'We received {{BusinessName}} – next steps on WWD',
                'preheader'       => 'Thanks for submitting your business. Verify it to get listed.',
                'header_title'    => 'Thanks for submitting!',
                'header_subtitle' => 'We’ve received your business profile',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>Thank you for submitting <strong>{{BusinessName}}</strong> to World Web Directory.</p>'
                    . '<h3 style="color:#dd3f3b;">What happens next?</h3>'
                    . '<ul><li>Verify ownership of your business with the code we email you</li><li>Upload your business logo</li><li>Our team reviews and approves your profile</li></ul>'
                    . '<p>Verified profiles with a logo are reviewed first.</p>',
                'button_text'     => 'Go to my dashboard',
                'button_url'      => '{{LoginURL}}',
                'footer_note'     => 'You’re receiving this email because you submitted a business profile on WWD.',
                'variables'       => [$owner, $business, $login],
            ],
            [
                'template_key'    => 'business_approved',
                'name'            => 'Business approved',
                'description'     => 'Sent by WWD Admin when an admin approves a business.',
                'project'         => 'admin',
                'trigger_type'    => 'event',
                'subject'         => 'Congratulations! Your WWD Business Profile Is Approved',
                'preheader'       => '{{BusinessName}} is now approved on WWD.',
                'header_title'    => 'Congratulations! 🎉',
                'header_subtitle' => 'Your business profile has been approved on WWD',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>We’re excited to let you know that your submitted business profile for <strong>{{BusinessName}}</strong> has been successfully reviewed and approved by the WWD team.</p>'
                    . '<hr style="border:none; border-top:1px solid #eeeeee; margin:25px 0;">'
                    . '<h3 style="color:#dd3f3b; margin-top:0;">Complete Your Business Profile</h3>'
                    . '<p>To get the best visibility and attract more customers, please log in and update the following details:</p>'
                    . '<ul><li>Business logo</li><li>Business description</li><li>Business address</li><li>Business hours &amp; working days</li><li>Social media links</li></ul>'
                    . '<p>A complete profile builds trust, improves local SEO, and helps customers choose your business with confidence.</p>',
                'button_text'     => 'Login & Update Your Profile',
                'button_url'      => '{{LoginURL}}',
                'footer_note'     => 'You’re receiving this email because you submitted a business profile on WWD.',
                'variables'       => [$owner, $business, $login],
            ],
            [
                'template_key'    => 'business_rejected',
                'name'            => 'Business rejected',
                'description'     => 'Sent by WWD Admin when an admin rejects a business, with the reason chosen in the reject popup.',
                'project'         => 'admin',
                'trigger_type'    => 'event',
                'subject'         => 'Update on your WWD business profile: {{BusinessName}}',
                'preheader'       => 'We couldn’t approve {{BusinessName}} on WWD.',
                'header_title'    => 'Business profile not approved',
                'header_subtitle' => 'We reviewed your submission',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>Thank you for submitting <strong>{{BusinessName}}</strong> to World Web Directory. Our team has reviewed your business profile, and unfortunately we can’t approve it at this time.</p>'
                    . '<p style="padding:12px 16px; background:#fdecec; border-left:4px solid #dd3f3b; border-radius:4px;"><strong>Reason:</strong> {{Reason}}</p>'
                    . '<p>If you believe this is a mistake, or you have fixed the issue, please reply to this email or contact our support team at {{SupportEmail}}. We’ll be happy to review your business again.</p>',
                'button_text'     => 'Log in to WWD Business',
                'button_url'      => '{{LoginURL}}',
                'footer_note'     => 'You’re receiving this email because you submitted a business profile on WWD.',
                'variables'       => [$owner, $business, $login,
                    ['name' => 'Reason', 'sample' => 'Website is not opening', 'description' => 'Reason chosen by the admin when rejecting (plus any note)']],
            ],
            [
                'template_key'    => 'claim_business',
                'name'            => 'Claim your approved business',
                'description'     => 'Asks the owner of an approved but unclaimed business to claim it with the email OTP.',
                'project'         => 'admin',
                'trigger_type'    => 'manual',
                'subject'         => 'Action Required: Claim Your Approved Business on WWD',
                'preheader'       => '{{BusinessName}} is approved but not claimed yet.',
                'header_title'    => 'Claim Your Business',
                'header_subtitle' => 'Your business is already approved on WWD',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>Your business profile for <strong>{{BusinessName}}</strong> has been successfully reviewed and approved by the WWD team. However, the business has not yet been claimed.</p>'
                    . '<p>To manage your listing and receive customer inquiries, please verify ownership by claiming your business using the email OTP.</p>'
                    . '<h3 style="color:#dd3f3b;">Why Claim Your Business?</h3>'
                    . '<ul><li>Verify ownership of your business profile</li><li>Update business details, address, and working hours</li><li>Add your business logo and description</li><li>Manage social media and contact information</li><li>Improve visibility and customer trust</li></ul>'
                    . '<p><em>Note: You will receive a one-time password (OTP) on this email address to verify and claim your business.</em></p>',
                'button_text'     => 'Claim Your Business',
                'button_url'      => '{{ClaimURL}}',
                'footer_note'     => 'This email was sent because a business profile associated with this email is approved on WWD.',
                'variables'       => [$owner, $business, ['name' => 'ClaimURL', 'sample' => 'https://biz.worldweb-directory.com/claim-business', 'description' => 'Claim business page']],
            ],
            [
                'template_key'    => 'verification_reminder',
                'name'            => 'Business verification reminder',
                'description'     => 'Sent by the WWD Admin scheduler at 11:59 PM UTC on day 0, 1, 3, 15 after submission, then every 15 days, until the business is verified.',
                'project'         => 'admin',
                'trigger_type'    => 'scheduled',
                'subject'         => 'Verify your business to get listed on WWD',
                'preheader'       => 'Verification is needed before {{BusinessName}} can be listed.',
                'header_title'    => 'Verify your business',
                'header_subtitle' => 'One quick step to get listed on WWD',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>Thank you for submitting <strong>{{BusinessName}}</strong> to World Web Directory.</p>'
                    . '<p>Your business profile is <strong>not verified yet</strong>. Verification is necessary for listing your business profile on WWD. Until it is verified, your business will not be visible to customers.</p>'
                    . '<p>It only takes a minute.</p>',
                'button_text'     => 'Verify my business',
                'button_url'      => '{{ClaimURL}}',
                'footer_note'     => 'You’re receiving this email because you submitted a business profile on WWD.',
                'variables'       => [$owner, $business, ['name' => 'ClaimURL', 'sample' => 'https://biz.worldweb-directory.com/claim-business', 'description' => 'Claim / verify business page']],
                'starts_at'       => now()->startOfDay(),
            ],
            [
                'template_key'    => 'otp_verification',
                'name'            => 'OTP code',
                'description'     => 'One-time password, e.g. when claiming a business. Send instantly.',
                'project'         => 'business',
                'trigger_type'    => 'event',
                'subject'         => 'Your WWD verification code: {{OTPCode}}',
                'preheader'       => 'Your one-time code is {{OTPCode}}.',
                'header_title'    => 'Your OTP Code',
                'header_subtitle' => '{{CurrentDate}}',
                'body'            => '<p><strong>Dear {{UserName}},</strong></p>'
                    . '<p>Use the following One-Time Password (OTP) to complete your verification:</p>'
                    . '<p style="text-align:center; font-size:32px; font-weight:bold; letter-spacing:8px; color:#dd3f3b; margin:24px 0;">{{OTPCode}}</p>'
                    . '<p>This OTP is valid for the next {{OTPValidMinutes}} minutes.</p>'
                    . '<p>If you did not request this code, please ignore this email.</p>',
                'button_text'     => null,
                'button_url'      => null,
                'footer_note'     => 'You’re receiving this email because a verification code was requested for this address on WWD.',
                'variables'       => [$user,
                    ['name' => 'OTPCode', 'sample' => '482913', 'description' => 'The one-time code'],
                    ['name' => 'OTPValidMinutes', 'sample' => '10', 'description' => 'Minutes until the code expires'],
                    ['name' => 'CurrentDate', 'sample' => '29 Oct 2026', 'description' => 'Today’s date']],
            ],
            [
                'template_key'    => 'password_reset',
                'name'            => 'Password reset',
                'description'     => 'Password reset link. Send instantly.',
                'project'         => 'all',
                'trigger_type'    => 'event',
                'subject'         => 'Reset your World Web Directory password',
                'preheader'       => 'Use the link inside to reset your password.',
                'header_title'    => 'Password Reset Request',
                'header_subtitle' => null,
                'body'            => '<p><strong>Dear {{UserName}},</strong></p>'
                    . '<p>We have sent this email because you requested a password reset on World Web Directory. Click the button below to reset it.</p>'
                    . '<p>If you did not request a password reset, please ignore this email.</p>'
                    . '<p>This link will expire in {{ResetValidHours}} hours.</p>',
                'button_text'     => 'Reset Password',
                'button_url'      => '{{ResetURL}}',
                'footer_note'     => 'You’re receiving this email because a password reset was requested for your WWD account.',
                'variables'       => [$user,
                    ['name' => 'ResetURL', 'sample' => 'https://biz.worldweb-directory.com/reset-password?token=abc123', 'description' => 'Password reset link'],
                    ['name' => 'ResetValidHours', 'sample' => '24', 'description' => 'Hours until the link expires']],
            ],
            [
                'template_key'    => 'subscription_confirmation',
                'name'            => 'Subscription confirmation',
                'description'     => 'Sent after a successful plan purchase or upgrade.',
                'project'         => 'business',
                'trigger_type'    => 'event',
                'subject'         => 'Subscription confirmed: {{PlanName}}',
                'preheader'       => 'Thank you for subscribing to {{PlanName}}.',
                'header_title'    => 'Subscription Confirmation',
                'header_subtitle' => '{{CurrentDate}}',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>Thank you for subscribing. Here are your subscription details for <strong>{{BusinessName}}</strong>:</p>'
                    . '<table role="presentation" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:15px;">'
                    . '<tr><td style="color:#666666;">Subscription plan</td><td><strong>{{PlanName}}</strong></td></tr>'
                    . '<tr><td style="color:#666666;">Start date</td><td>{{StartDate}}</td></tr>'
                    . '<tr><td style="color:#666666;">Billing cycle</td><td>{{BillingCycle}}</td></tr>'
                    . '<tr><td style="color:#666666;">Amount</td><td><strong>{{Amount}}</strong></td></tr>'
                    . '</table>'
                    . '<p>If you have any questions or need assistance, feel free to reach out to our support team.</p>',
                'button_text'     => 'Access Your Dashboard',
                'button_url'      => '{{LoginURL}}',
                'footer_note'     => 'You’re receiving this email because you purchased a plan on WWD.',
                'variables'       => [$owner, $business, $login,
                    ['name' => 'PlanName', 'sample' => 'Premium Growth Plan', 'description' => 'Plan name'],
                    ['name' => 'StartDate', 'sample' => '29 Oct 2026', 'description' => 'Subscription start date'],
                    ['name' => 'BillingCycle', 'sample' => 'Monthly', 'description' => 'Billing cycle'],
                    ['name' => 'Amount', 'sample' => '$9.90', 'description' => 'Amount paid'],
                    ['name' => 'CurrentDate', 'sample' => '29 Oct 2026', 'description' => 'Today’s date']],
            ],
            [
                'template_key'    => 'premium_welcome',
                'name'            => 'Welcome to Premium',
                'description'     => 'Welcome message after upgrading to the Premium Growth Plan.',
                'project'         => 'business',
                'trigger_type'    => 'event',
                'subject'         => 'You are now a Premium Member 🎉',
                'preheader'       => 'Your Premium Growth Plan is active.',
                'header_title'    => 'Congratulations!',
                'header_subtitle' => 'You are now a Premium Member',
                'body'            => '<p><strong>Hello {{BusinessOwnerName}},</strong></p>'
                    . '<p>Thank you for your payment of <strong>{{Amount}}</strong>. Your subscription is now active, and <strong>{{BusinessName}}</strong> is ready for the spotlight!</p>'
                    . '<h3 style="color:#dd3f3b;">Your Active Perks</h3>'
                    . '<ul><li>🚀 Top Placement in Your Niche</li><li>✅ Verified Business Seal</li><li>💎 Premium Featured Listing</li><li>📸 Expanded Service Showcase</li></ul>',
                'button_text'     => 'Access Your Dashboard',
                'button_url'      => '{{LoginURL}}',
                'footer_note'     => 'You received this email because you subscribed to our Premium Plan.',
                'variables'       => [$owner, $business, $login, ['name' => 'Amount', 'sample' => '$9.90', 'description' => 'Amount paid']],
            ],
            [
                'template_key'    => 'profile_action_needed',
                'name'            => 'Profile not appearing in searches',
                'description'     => 'Tells an owner their profile is hidden from searches (incomplete or inactive).',
                'project'         => 'admin',
                'trigger_type'    => 'scheduled',
                'subject'         => 'Action needed: {{BusinessName}} is not appearing in searches',
                'preheader'       => 'Update your profile to appear in client searches again.',
                'header_title'    => 'Action Needed',
                'header_subtitle' => 'Your profile is no longer appearing in client searches',
                'body'            => '<p><strong>Dear {{BusinessOwnerName}},</strong></p>'
                    . '<p>We wanted to inform you that your profile for <strong>{{BusinessName}}</strong> is no longer appearing in client searches. This might be due to one of the following reasons:</p>'
                    . '<ul><li>Your profile information is incomplete.</li><li>You haven’t updated your profile in a while.</li><li>Your account has been inactive for an extended period.</li></ul>'
                    . '<p>To make sure your profile is visible to clients, please log in and update your profile information as soon as possible.</p>',
                'button_text'     => 'Update Your Profile',
                'button_url'      => '{{LoginURL}}',
                'footer_note'     => 'You’re receiving this email because you have a business profile on WWD.',
                'variables'       => [$owner, $business, $login],
            ],
            [
                'template_key'    => 'premium_promotion',
                'name'            => 'Premium upgrade offer',
                'description'     => 'Promotional offer to upgrade to Premium. Example custom template.',
                'project'         => 'admin',
                'trigger_type'    => 'manual',
                'is_system'       => 0,
                'subject'         => 'Unlock Premium Growth for {{BusinessName}} – save 35%',
                'preheader'       => 'Limited time: Premium for {{OfferPrice}}/mo.',
                'header_title'    => 'Unlock Your Premium Growth',
                'header_subtitle' => 'Limited Time Offer',
                'body'            => '<p><strong>Hi {{BusinessOwnerName}},</strong></p>'
                    . '<p>Is your business getting the attention it deserves? Thousands of customers are searching for services in your niche right now. Don’t let your competitors take the lead.</p>'
                    . '<h3 style="color:#dd3f3b;">Why go Premium?</h3>'
                    . '<ul><li>🚀 <strong>Top Niche Placement</strong> – appear at the very top of search results, where 80% of clicks happen.</li>'
                    . '<li>✅ <strong>Verified Trust Seal</strong> – build instant credibility with a “Verified Business” badge.</li>'
                    . '<li>📈 <strong>Smart Reputation Tools</strong> – collect and showcase your best customer reviews.</li></ul>'
                    . '<p style="text-align:center; font-size:18px;">Upgrade today &amp; save 35%: <s style="color:#999999;">{{RegularPrice}}</s> <strong style="color:#dd3f3b; font-size:24px;">{{OfferPrice}}</strong>/mo</p>'
                    . '<p style="text-align:center; color:#666666; font-size:13px;">Cancel anytime. No hidden fees.</p>',
                'button_text'     => 'Claim My Premium Spot',
                'button_url'      => '{{UpgradeURL}}',
                'footer_note'     => 'You’re receiving this offer because you have a business profile on WWD.',
                'variables'       => [$owner, $business,
                    ['name' => 'RegularPrice', 'sample' => '$15.00', 'description' => 'Normal monthly price'],
                    ['name' => 'OfferPrice', 'sample' => '$9.90', 'description' => 'Offer monthly price'],
                    ['name' => 'UpgradeURL', 'sample' => 'https://biz.worldweb-directory.com/plans', 'description' => 'Upgrade / plans page']],
            ],
            [
                'template_key'    => 'review_kit',
                'name'            => 'Review kit (get more reviews)',
                'description'     => 'Sent once by WWD Admin\'s daily review kit job when a business is public on WorldWeb Directory: review link, QR code and the Get reviews page. At most 50 a day.',
                'project'         => 'admin',
                'trigger_type'    => 'scheduled',
                'subject'         => 'Get more reviews for {{BusinessName}}',
                'preheader'       => 'Your review link and QR code for {{BusinessName}} on WorldWeb Directory.',
                'header_title'    => 'Get more reviews',
                'header_subtitle' => '{{BusinessName}} is live on WorldWeb Directory',
                'body'            => '<p>Hi {{BusinessOwnerName}},</p><p>Reviews are the first thing visitors look at on WWD. In your category, businesses with better ratings and more reviews are listed higher. Share this link with your customers:</p><p style="margin:0 0 18px; padding:12px 14px; background:#f8f9fb; border:1px solid #e3e6eb; border-radius:8px; word-break:break-all;"><a href="{{ReviewURL}}" style="color:{{BrandColor}};">{{ReviewURL}}</a></p><p style="text-align:center; margin:0 0 6px;"><img src="{{QRURL}}" width="160" height="160" alt="QR code that opens your review form" style="border:1px solid #e3e6eb; border-radius:8px;"></p><p style="text-align:center; color:#5b6474; font-size:13px; margin:0 0 18px;">Put this QR code on your counter or invoices: customers scan it with their phone camera.</p><p>In WWD for Business you&rsquo;ll find the link with WhatsApp and email buttons, printable review cards and a badge for your website.</p><p style="color:#5b6474; font-size:13px;">Ask every customer, not only the happy ones, and never offer anything in return for a review.</p>',
                'button_text'     => 'Open Get reviews',
                'button_url'      => '{{GetReviewsURL}}',
                'footer_note'     => 'You’re receiving this email because {{BusinessName}} is listed on World Web Directory.',
                'variables'       => [$owner, $business,
                    ['name' => 'ReviewURL', 'sample' => 'https://worldweb-directory.com/review/sunrise-bakery?src=email', 'description' => 'Review link (opens the review form)'],
                    ['name' => 'QRURL', 'sample' => 'https://worldweb-directory.com/review/sunrise-bakery/qr', 'description' => 'QR code picture for the review link'],
                    ['name' => 'GetReviewsURL', 'sample' => 'https://biz.worldweb-directory.com/get-reviews', 'description' => 'Get reviews page in WWD Business'],
                    ['name' => 'ListingURL', 'sample' => 'https://worldweb-directory.com/review/sunrise-bakery', 'description' => 'The public business page']],
            ],
        ];

        foreach ($templates as $template) {
            if (DB::table('email_templates')->where('template_key', $template['template_key'])->exists()) {
                continue;
            }
            $template['variables'] = json_encode($template['variables'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            DB::table('email_templates')->insert($template + [
                'is_system'  => 1,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
