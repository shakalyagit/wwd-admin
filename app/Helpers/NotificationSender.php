<?php

namespace App\Helpers;

use App\Mail\NotificationMail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationSender
{
    public const VERIFICATION_REMINDER = 'verification_reminder';

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

    /**
     * Send one notification to a business and record it in notification_logs.
     * $step is the day offset for automatic sends, null for manual sends.
     */
    public static function send(NotificationTemplate $template, $business, $owner = null, ?int $step = null, string $trigger = 'auto'): NotificationLog
    {
        $owner_name = $owner ? trim($owner->first_name . ' ' . $owner->last_name) : '';
        $values = [
            '{business_name}'  => $business->business_name,
            '{business_email}' => $business->business_email,
            '{owner_name}'     => $owner_name !== '' ? $owner_name : 'Business Owner',
            '{claim_url}'      => config('services.wwd.claim_url'),
        ];

        // Subject is plain text; body is HTML, so escape the values going into it
        $subject = strtr($template->subject, $values);
        $body = strtr($template->body, array_map('e', $values));

        $data = [
            'recipient_email' => $business->business_email,
            'subject'         => $subject,
            'trigger'         => $trigger,
        ];

        try {
            Mail::to($business->business_email)->send(new NotificationMail($subject, $body));
            $data += ['status' => 'sent', 'error' => null, 'sent_at' => now()];
        } catch (\Throwable $e) {
            $data += ['status' => 'failed', 'error' => Str::limit($e->getMessage(), 1000), 'sent_at' => null];
        }

        $keys = ['type' => $template->type, 'business_id' => $business->business_id];

        if ($step === null) {
            return NotificationLog::create($keys + $data);
        }

        // One row per business per step; a failed send is retried on the next run
        return NotificationLog::updateOrCreate($keys + ['step' => $step], $data);
    }
}
