<?php

namespace App\Console\Commands;

use App\Helpers\NotificationSender;
use App\Models\AdminListing;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendListingInvites extends Command
{
    protected $signature = 'notifications:listing-invites {--dry-run : List who would be emailed without sending}';

    protected $description = 'Email admin-listed businesses that are not claimed yet: day 0, 3, 10, 30 after listing, then stop';

    public function handle(): int
    {
        // Record claims that happened since the last run
        AdminListing::whereNull('claimed_at')
            ->whereIn('business_id', Business::where('is_claimed', 1)->select('business_id'))
            ->update(['claimed_at' => now()]);

        $template = EmailTemplate::where('template_key', NotificationSender::LISTING_INVITE)->first();
        if (!$template || !$template->is_active) {
            $this->info('Listing invite template is missing or inactive. Nothing sent.');
            return self::SUCCESS;
        }

        $dry_run = $this->option('dry-run');
        $today = now('UTC')->startOfDay();
        $last_step = max(NotificationSender::LISTING_INVITE_STEPS);
        $sent = 0;
        $failed = 0;

        // Listed within the schedule window (a day of slack so a missed run still sends the last step)
        AdminListing::join('businesses', 'businesses.business_id', '=', 'admin_listings.business_id')
            ->where('businesses.is_claimed', 0)
            ->where('businesses.notifications_paused', 0)
            ->where('businesses.is_admin_verified', '!=', 2)
            ->whereNotNull('businesses.business_email')
            ->where('businesses.business_email', '!=', '')
            ->where('admin_listings.created_at', '>=', $today->copy()->subDays($last_step + 1))
            ->select('businesses.business_id', 'businesses.business_name', 'businesses.business_email', 'businesses.business_website',
                'businesses.user_id', 'admin_listings.admin_listing_id', 'admin_listings.created_at as listed_at')
            ->chunkById(200, function ($rows) use ($template, $today, $dry_run, &$sent, &$failed) {
                $sent_steps = NotificationLog::where('type', $template->template_key)
                    ->where('status', 'sent')
                    ->whereNotNull('step')
                    ->whereIn('business_id', $rows->pluck('business_id'))
                    ->get(['business_id', 'step'])
                    ->map(fn($log) => $log->business_id . ':' . $log->step)
                    ->flip();

                $owners = User::whereIn('id', $rows->pluck('user_id')->filter())->get(['id', 'first_name', 'last_name'])->keyBy('id');

                foreach ($rows as $row) {
                    $days = Carbon::parse($row->listed_at)->startOfDay()->diffInDays($today);
                    $step = NotificationSender::listing_due_step($days);

                    if ($sent_steps->has($row->business_id . ':' . $step)) {
                        continue;
                    }
                    if ($dry_run) {
                        $this->line("Would send day-{$step} listing invite to #{$row->business_id} {$row->business_email}");
                        continue;
                    }

                    $business = Business::find($row->business_id);
                    $log = NotificationSender::send($template, $business, $owners->get($row->user_id), $step);
                    $log && $log->status === 'sent' ? $sent++ : $failed++;
                }
            }, 'admin_listings.admin_listing_id', 'admin_listing_id');

        $this->info("Listing invites sent: {$sent}, failed: {$failed}");
        return self::SUCCESS;
    }
}
