<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationSender;
use App\Models\Business;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class NotificationController extends Controller
{
    public function notifications(Request $request)
    {
        $logs = $this->log_query($request)->paginate(50);
        $types = NotificationTemplate::pluck('name', 'type');
        return view('notifications.index', compact('logs', 'types'));
    }

    public function notifications_filter(Request $request)
    {
        $currentPage = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $logs = $this->log_query($request)->paginate(50);
        $html = view('notifications.partials.log_rows', compact('logs'))->render();

        $pagination = $logs
            ->appends(array_merge($request->all(), ['page' => $currentPage]))
            ->links('pagination::bootstrap-5')
            ->render();

        return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => $currentPage]);
    }

    public function notification_queue(Request $request)
    {
        $template = NotificationTemplate::where('type', NotificationSender::VERIFICATION_REMINDER)->first();

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
        $template = NotificationTemplate::where('type', NotificationSender::VERIFICATION_REMINDER)->firstOrFail();

        if (empty($business->business_email)) {
            return redirect()->back()->with('error', 'This business has no email address.');
        }

        $owner = User::find($business->user_id);
        $log = NotificationSender::send($template, $business, $owner, null, 'manual');

        if ($log->status !== 'sent') {
            return redirect()->back()->with('error', 'Email could not be sent: ' . $log->error);
        }
        return redirect()->back()->with('success', 'Verification reminder sent to ' . $business->business_email . '.');
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

    public function notification_templates()
    {
        $templates = NotificationTemplate::orderBy('name')->get();
        return view('notifications.templates', compact('templates'));
    }

    public function edit_notification_template($id)
    {
        $template = NotificationTemplate::findOrFail($this->decrypt_id($id));
        return view('notifications.edit_template', compact('template'));
    }

    public function edit_notification_template_action(Request $request, $id)
    {
        $request->validate([
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string',
            'starts_at' => 'nullable|date',
        ]);

        $template = NotificationTemplate::findOrFail($this->decrypt_id($id));
        $template->subject = $request->subject;
        $template->body = $request->body;
        $template->is_active = $request->boolean('is_active');
        $template->starts_at = $request->filled('starts_at') ? Carbon::parse($request->starts_at)->startOfDay() : null;
        $template->save();

        return redirect()->back()->with('success', 'Template updated successfully.');
    }

    private function log_query(Request $request)
    {
        return NotificationLog::leftJoin('businesses', 'notification_logs.business_id', '=', 'businesses.business_id')
            ->select('notification_logs.*', 'businesses.business_name', 'businesses.notifications_paused')
            ->when($request->filled('type'), fn($q) => $q->where('notification_logs.type', $request->type))
            ->when($request->filled('status'), fn($q) => $q->where('notification_logs.status', $request->status))
            ->when($request->filled('trigger'), fn($q) => $q->where('notification_logs.trigger', $request->trigger))
            ->when($request->filled('business_name'), fn($q) => $q->where('businesses.business_name', 'like', '%' . $request->business_name . '%'))
            ->when($request->filled('recipient_email'), fn($q) => $q->where('notification_logs.recipient_email', 'like', '%' . $request->recipient_email . '%'))
            ->when($request->filled('from_date'), fn($q) => $q->whereDate('notification_logs.created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate('notification_logs.created_at', '<=', $request->to_date))
            ->orderBy('notification_logs.notification_log_id', 'desc');
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
