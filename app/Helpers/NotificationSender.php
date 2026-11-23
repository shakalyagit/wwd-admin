<?php

namespace App\Helpers;

use App\Mail\NotificationMail;
use App\Models\EmailLayout;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationSender
{
    public const VERIFICATION_REMINDER = 'verification_reminder';
    public const BUSINESS_APPROVED = 'business_approved';
    public const BUSINESS_REJECTED = 'business_rejected';
    public const REVIEW_KIT = 'review_kit';

    // Latest reminder due for a business submitted $days ago: day 0, 1, 3, 15, then every 15 days
    public static function verification_due_step(int $days): int
    {
        if ($days >= 15) {
            return $days - ($days % 15);
        }
        if ($days >= 3) {
            return 3;
        }
        if ($days >= 1) {
            return 1;
        }
        return 0;
    }

    // Reminder after the one due today
    public static function verification_next_step(int $days): int
    {
        if ($days < 1) {
            return 1;
        }
        if ($days < 3) {
            return 3;
        }
        if ($days < 15) {
            return 15;
        }
        return $days - ($days % 15) + 15;
    }

    // Layout for a template: its own, else the default one
    public static function layout_for(EmailTemplate $template): ?EmailLayout
    {
        return ($template->email_layout_id ? EmailLayout::find($template->email_layout_id) : null)
            ?? EmailLayout::where('is_default', 1)->first();
    }

    // Public click-tracking link on the admin domain (route email_click, outside the login)
    public static function tracking_url(string $token): string
    {
        return rtrim(config('services.wwd.tracking_url'), '/') . '/t/' . $token;
    }

    // Placeholder values every business email can use
    public static function business_data($business, $owner = null): array
    {
        $owner_name = $owner ? trim($owner->first_name . ' ' . $owner->last_name) : '';

        return [
            'BusinessName'      => trim((string) $business->business_name),
            'BusinessEmail'     => (string) $business->business_email,
            'BusinessOwnerName' => $owner_name !== '' ? $owner_name : 'Business Owner',
            'LoginURL'          => config('services.wwd.business_login_url'),
            'ClaimURL'          => config('services.wwd.claim_url'),
        ] + self::review_links($business);
    }

    /**
     * The business's public page, review link and QR code on WWD Listing, and the owner's Get reviews page.
     * ReviewURL carries ?src=email so the Listing site saves reviews that came from a WWD email.
     * Empty when the business has no public address (wwd_url) or the caller didn't select it.
     */
    public static function review_links($business): array
    {
        $slug = trim((string) ($business->wwd_url ?? ''));
        $page = $slug !== '' ? config('services.wwd.listing_url') . '/review/' . rawurlencode($slug) : '';

        return [
            'ListingURL'    => $page,
            'ReviewURL'     => $page !== '' ? $page . '?src=email' : '',
            'QRURL'         => $page !== '' ? $page . '/qr' : '',
            'GetReviewsURL' => config('services.wwd.get_reviews_url'),
        ];
    }

    /**
     * Send a template to a business and record it in notification_logs (type = template key).
     * $step is the day offset for scheduled reminders, null otherwise. Returns null if the template is switched off.
     */
    public static function send(EmailTemplate $template, $business, $owner = null, ?int $step = null, string $trigger = 'auto', array $extra = []): ?NotificationLog
    {
        if (!$template->is_active) {
            return null;
        }

        $attributes = $template->getAttributes();
        $layout = optional(self::layout_for($template))->getAttributes() ?? [];
        $values = self::business_data($business, $owner) + $extra;

        // Click tracking: the button points to /t/{token}, which records the click and forwards to the real link
        $tracking = ['tracking_token' => null, 'target_url' => null];
        if (!empty($attributes['button_text']) && !empty($attributes['button_url'])) {
            $target = EmailTemplateRenderer::fill($attributes['button_url'], EmailTemplateRenderer::variables($layout, $values), false);
            if (preg_match('#^https?://#i', $target)) {
                $tracking = ['tracking_token' => Str::random(40), 'target_url' => Str::limit($target, 500, '')];
                $attributes['button_url'] = self::tracking_url($tracking['tracking_token']);
            }
        }

        $email = EmailTemplateRenderer::render($attributes, $layout, $values);
        $subject = $email['subject'];

        $data = [
            'recipient_email' => $business->business_email,
            'subject'         => Str::limit($subject, 250),
            'trigger'         => $trigger,
        ] + $tracking;

        try {
            Mail::to($business->business_email)->send(new NotificationMail($subject, $email['html']));
            $data += ['status' => 'sent', 'error' => null, 'sent_at' => now()];
        } catch (\Throwable $e) {
            $data += ['status' => 'failed', 'error' => Str::limit($e->getMessage(), 1000), 'sent_at' => null];
        }

        $keys = ['type' => $template->template_key, 'business_id' => $business->business_id];

        if ($step === null) {
            return NotificationLog::create($keys + $data);
        }

        // One row per business per step; a failed send is retried on the next run
        return NotificationLog::updateOrCreate($keys + ['step' => $step], $data);
    }
}
