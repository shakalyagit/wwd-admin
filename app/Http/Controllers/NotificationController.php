<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationSender;
use App\Models\Business;
use App\Models\NotificationLog;
use App\Models\EmailTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Sent log: one summary row per day + email template; clicking a row loads its recipients
    public function notifications(Request $request)
    {
        $summary_select = "COUNT(*) as total,
            SUM(notification_logs.status = 'sent') as sent,
            SUM(notification_logs.status = 'failed') as failed,
            SUM(notification_logs.status = 'sent' AND notification_logs.tracking_token IS NOT NULL) as tracked,
            SUM(notification_logs.click_count > 0) as clicked,
            SUM(notification_logs.`trigger` = 'manual') as manual";

        $groups = $this->log_query($request)
            ->selectRaw('DATE(notification_logs.created_at) as day, notification_logs.type, MAX(notification_logs.created_at) as last_sent, ' . $summary_select)
            ->groupBy('day', 'notification_logs.type')
            ->orderByDesc('day')
            ->orderBy('notification_logs.type')
            ->paginate(30)
            ->withQueryString();

        $totals = $this->log_query($request)->selectRaw($summary_select)->first();
        $types = EmailTemplate::where('trigger_type', 'scheduled')->orderBy('name')->pluck('name', 'template_key');

        return view('notifications.index', compact('groups', 'totals', 'types'));
    }

    // Recipients of one summary row (day + template), as table rows for the expanded view
    public function notification_details(Request $request)
    {
        $request->validate(['day' => 'required|date_format:Y-m-d', 'type' => 'required|string|max:80']);

        $logs = $this->log_query($request)
            ->leftJoin('businesses', 'notification_logs.business_id', '=', 'businesses.business_id')
            ->select('notification_logs.*', 'businesses.business_name', 'businesses.notifications_paused')
            ->whereDate('notification_logs.created_at', $request->day)
            ->where('notification_logs.type', $request->type)
            ->orderByDesc('notification_logs.notification_log_id')
            ->limit(500)
            ->get();

        return response()->json(['html' => view('notifications.partials.detail_rows', compact('logs'))->render(), 'count' => $logs->count()]);
    }

    public function notification_queue(Request $request)
    {
        $template = EmailTemplate::where('template_key', NotificationSender::VERIFICATION_REMINDER)->first();

        $businesses = Business::where('is_claimed', 0)
            ->select('business_id', 'business_name', 'business_email', 'is_admin_verified', 'notifications_paused', 'created_at')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('business_name', 'like', '%' . $request->search . '%')
                        ->orWhere('business_email', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('paused'), fn($q) => $q->where('notifications_paused', $request->paused))
            ->orderBy('business_id', 'desc')
            ->paginate(50);

        // Sent reminders for the businesses on this page, in one query
        $sent_logs = NotificationLog::where('type', NotificationSender::VERIFICATION_REMINDER)
            ->where('status', 'sent')
            ->whereIn('business_id', $businesses->pluck('business_id'))
            ->orderBy('sent_at')
            ->get(['business_id', 'step', 'trigger', 'sent_at'])
            ->groupBy('business_id');

        $today = now('UTC')->startOfDay();
        foreach ($businesses as $business) {
            $reminders = $sent_logs->get($business->business_id, collect());
            $business->reminder_count = $reminders->count();
            $business->last_reminder = $reminders->last();
            $business->next_reminder = $this->next_reminder($business, $reminders, $template, $today);
        }

        return view('notifications.queue', compact('businesses', 'template'));
    }

    public function notification_resend($business_id)
    {
        $business = Business::findOrFail($this->decrypt_id($business_id));
        $template = EmailTemplate::where('template_key', NotificationSender::VERIFICATION_REMINDER)->firstOrFail();

        if (empty($business->business_email)) {
            return redirect()->back()->with('error', 'This business has no email address.');
        }
        if (!$template->is_active) {
            return redirect()->back()->with('error', 'The verification reminder template is switched off in Email templates.');
        }

        $owner = User::find($business->user_id);
        $log = NotificationSender::send($template, $business, $owner, null, 'manual');

        if ($log->status !== 'sent') {
            return redirect()->back()->with('error', 'Email could not be sent: ' . $log->error);
        }
        return redirect()->back()->with('success', 'Verification reminder sent to ' . $business->business_email . '.');
    }

    // Public (no login): the email's button lands here, the click is recorded, then the owner is forwarded
    public function email_click(Request $request, $token)
    {
        $log = NotificationLog::where('tracking_token', $token)->first(['notification_log_id', 'target_url', 'clicked_at', 'click_count']);
        $fallback = config('services.wwd.business_login_url');

        if (!$log) {
            return redirect()->away($fallback);
        }

        // Link checkers (mail security scanners, previews, bots) and HEAD requests are not real clicks
        $agent = (string) $request->userAgent();
        $is_bot = $request->isMethod('HEAD') || $agent === ''
            || preg_match('/bot|crawl|spider|preview|scanner|safelinks|proofpoint|mimecast|barracuda|headless/i', $agent);

        if (!$is_bot) {
            NotificationLog::where('notification_log_id', $log->notification_log_id)->update([
                'click_count' => $log->click_count + 1,
                'clicked_at'  => $log->clicked_at ?? now(),
            ]);
        }

        return redirect()->away(preg_match('#^https?://#i', (string) $log->target_url) ? $log->target_url : $fallback);
    }

    public function notification_pause_toggle($business_id)
    {
        $business = Business::findOrFail($this->decrypt_id($business_id));
        $business->notifications_paused = $business->notifications_paused ? 0 : 1;
        // businesses is shared with the public site; don't touch its updated_at
        $business->timestamps = false;
        $business->save();

        $message = $business->notifications_paused ? 'Notifications paused for ' : 'Notifications resumed for ';
        return redirect()->back()->with('success', $message . $business->business_name . '.');
    }

    // Only scheduled (cron, bulk) emails, i.e. templates whose "When" is Scheduled; one-off action emails
    // like approve/reject are still logged but not listed here. Plus the page's filters.
    private function log_query(Request $request)
    {
        return NotificationLog::query()
            ->whereIn('notification_logs.type', EmailTemplate::where('trigger_type', 'scheduled')->select('template_key'))
            ->when($request->filled('filter_type'), fn($q) => $q->where('notification_logs.type', $request->filter_type))
            ->when($request->filled('from_date'), fn($q) => $q->whereDate('notification_logs.created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate('notification_logs.created_at', '<=', $request->to_date))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('notification_logs.recipient_email', 'like', $term)
                        ->orWhereIn('notification_logs.business_id', Business::where('business_name', 'like', $term)->select('business_id'));
                });
            });
    }

    // Text for the "Next reminder" column on the unverified businesses screen
    private function next_reminder($business, $reminders, $template, Carbon $today): string
    {
        if (!$template || !$template->is_active) {
            return 'Template inactive';
        }
        if ($business->notifications_paused) {
            return 'Paused';
        }
        if ($business->is_admin_verified == 2) {
            return 'Rejected, not sent';
        }
        if (empty($business->business_email)) {
            return 'No email';
        }
        if ($template->starts_at && Carbon::parse($business->created_at)->lt($template->starts_at)) {
            return 'Before go-live, manual only';
        }

        $submitted = Carbon::parse($business->created_at)->startOfDay();
        $days = $submitted->diffInDays($today);
        $due_step = NotificationSender::verification_due_step($days);
        $due_sent = $reminders->contains(fn($log) => $log->step !== null && (int) $log->step === $due_step);

        if (!$due_sent) {
            return 'Today 11:59 PM UTC (day ' . $due_step . ')';
        }

        $next_step = NotificationSender::verification_next_step($days);
        return $submitted->copy()->addDays($next_step)->format('d-m-Y') . ' (day ' . $next_step . ')';
    }

    private function decrypt_id($id)
    {
        try {
            return decrypt($id);
        } catch (DecryptException $e) {
            abort(404);
        }
    }
}
