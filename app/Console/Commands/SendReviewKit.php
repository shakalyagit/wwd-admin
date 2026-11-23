<?php

namespace App\Console\Commands;

use App\Helpers\NotificationSender;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * The "review kit" email (owner decisions 23 Nov 2026, review-requests-plan.md in the WWD Listing project): once per
 * business, when its profile is public on WorldWeb Directory, tell the owner about their review link, QR code and the
 * Get reviews page in WWD Business.
 *
 * Run once a day in the morning by cron (schedule:run doesn't work on the host; see docs). Each run:
 *   - public profiles only: approved, and claimed or Featured (the WWD Listing rule), with an email and an address;
 *   - newest first, so a newly approved business gets it the next morning while older ones are worked through;
 *   - at most --limit emails (default 50) so the first runs, which also cover every business that was already
 *     public, go out over a few days;
 *   - not within 12 hours of the business's "approved" email, so the two never arrive together;
 *   - once per business: a 'sent' log for this template means done. Failed sends are tried again next run, up to 3 times.
 */
class SendReviewKit extends Command
{
    protected $signature = 'notifications:review-kit
        {--dry-run : List who would be emailed without sending}
        {--limit=50 : Most emails to send in this run}';

    protected $description = 'Email owners of public businesses their review link, QR code and Get reviews page (once each)';

    public const MAX_FAILURES = 3;

    public function handle(): int
    {
        $template = EmailTemplate::where('template_key', NotificationSender::REVIEW_KIT)->first();
        if (!$template || !$template->is_active) {
            $this->info('Review kit template is missing or inactive. Nothing sent.');
            return self::SUCCESS;
        }

        $dry_run = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $key = $template->template_key;

        $done = NotificationLog::where('type', $key)->where('status', 'sent')->select('business_id');
        $given_up = NotificationLog::where('type', $key)->where('status', 'failed')
            ->groupBy('business_id')->havingRaw('COUNT(*) >= ?', [self::MAX_FAILURES])->select('business_id');
        $just_approved = NotificationLog::where('type', NotificationSender::BUSINESS_APPROVED)->where('status', 'sent')
            ->where('sent_at', '>', now()->subHours(12))->select('business_id');

        $businesses = Business::where('is_admin_verified', 1)
            ->where(fn($q) => $q->where('is_claimed', 1)->orWhere('business_status', 'P'))
            ->when(Schema::hasColumn('businesses', 'notifications_paused'), fn($q) => $q->where('notifications_paused', 0))
            ->whereNotNull('business_email')->where('business_email', '!=', '')
            ->whereNotNull('wwd_url')->where('wwd_url', '!=', '')
            ->whereNotIn('business_id', $done)
            ->whereNotIn('business_id', $given_up)
            ->whereNotIn('business_id', $just_approved)
            ->orderByDesc('business_id')
            ->limit($limit)
            ->get(['business_id', 'business_name', 'business_email', 'wwd_url', 'user_id']);

        $owners = User::whereIn('id', $businesses->pluck('user_id')->filter())->get(['id', 'first_name', 'last_name'])->keyBy('id');

        $sent = 0;
        $failed = 0;
        foreach ($businesses as $business) {
            if ($dry_run) {
                $this->line("Would send the review kit to #{$business->business_id} {$business->business_email}");
                continue;
            }
            $log = NotificationSender::send($template, $business, $owners->get($business->user_id));
            $log && $log->status === 'sent' ? $sent++ : $failed++;
        }

        $this->info($dry_run
            ? 'Review kit (dry run): ' . $businesses->count() . ' would be emailed (limit ' . $limit . ')'
            : "Review kit sent: {$sent}, failed: {$failed} (limit {$limit})");

        return self::SUCCESS;
    }
}
