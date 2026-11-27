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
    public const LISTING_INVITE = 'listing_invite';

    // Claim invites for admin-listed businesses: day 0 (sent when the admin saves it), 3, 10, 30, then stop
    public const LISTING_INVITE_STEPS = [0, 3, 10, 30];

    public static function listing_due_step(int $days): int
    {
        $due = 0;
        foreach (self::LISTING_INVITE_STEPS as $step) {
            if ($days >= $step) {
                $due = $step;
            }
        }
        return $due;
    }

    // Next invite after $days, or null once the last one is due
    public static function listing_next_step(int $days): ?int
    {
        foreach (self::LISTING_INVITE_STEPS as $step) {
            if ($step > $days) {
                return $step;
            }
        }
        return null;
    }

    // Public unsubscribe page for a business (route email_unsubscribe, outside the login), signed so ids can't be guessed
    public static function unsubscribe_url($business_id): string
    {
        $path = \Illuminate\Support\Facades\URL::signedRoute('email_unsubscribe', ['business_id' => $business_id], null, false);
        return rtrim(config('services.wwd.tracking_url'), '/') . $path;
    }

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
            'UnsubscribeURL'    => !empty($business->business_id) ? self::unsubscribe_url($business->business_id) : '',
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
