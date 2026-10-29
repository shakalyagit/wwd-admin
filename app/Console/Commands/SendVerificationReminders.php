<?php

namespace App\Console\Commands;

use App\Helpers\NotificationSender;
use App\Models\Business;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendVerificationReminders extends Command
{
    protected $signature = 'notifications:verification-reminders {--dry-run : List who would be emailed without sending}';

    protected $description = 'Email unverified businesses on day 0, 1, 3, 15 after submission, then every 15 days';

    public function handle(): int
    {
        $template = NotificationTemplate::where('type', NotificationSender::VERIFICATION_REMINDER)->first();
        if (!$template || !$template->is_active) {
            $this->info('Verification reminder template is missing or inactive. Nothing sent.');
            return self::SUCCESS;
        }

        $dry_run = $this->option('dry-run');
        $today = now('UTC')->startOfDay();
        $sent = 0;
        $failed = 0;

        Business::where('is_claimed', 0)
            ->where('notifications_paused', 0)
            ->where(function ($q) {
                // Rejected businesses can't be listed anyway
                $q->whereNull('is_admin_verified')->orWhere('is_admin_verified', '!=', 2);
            })
            ->whereNotNull('business_email')
            ->where('business_email', '!=', '')
            ->when($template->starts_at, fn($q) => $q->where('created_at', '>=', $template->starts_at))
            ->select('business_id', 'business_name', 'business_email', 'user_id', 'created_at')
            ->chunkById(200, function ($businesses) use ($template, $today, $dry_run, &$sent, &$failed) {
                $ids = $businesses->pluck('business_id');

                $sent_steps = NotificationLog::where('type', $template->type)
                    ->where('status', 'sent')
                    ->whereNotNull('step')
                    ->whereIn('business_id', $ids)
                    ->get(['business_id', 'step'])
                    ->map(fn($log) => $log->business_id . ':' . $log->step)
                    ->flip();

                $owners = User::whereIn('id', $businesses->pluck('user_id')->filter())
                    ->get(['id', 'first_name', 'last_name'])
                    ->keyBy('id');

                foreach ($businesses as $business) {
                    $days = Carbon::parse($business->created_at)->startOfDay()->diffInDays($today);
                    $step = NotificationSender::verification_due_step($days);

                    if ($sent_steps->has($business->business_id . ':' . $step)) {
                        continue;
                    }

                    if ($dry_run) {
                        $this->line("Would send day-{$step} reminder to #{$business->business_id} {$business->business_email}");
                        continue;
                    }

                    $log = NotificationSender::send($template, $business, $owners->get($business->user_id), $step);
                    $log->status === 'sent' ? $sent++ : $failed++;
                }
            }, 'business_id');

        $this->info("Verification reminders sent: {$sent}, failed: {$failed}");
        return self::SUCCESS;
    }
}
