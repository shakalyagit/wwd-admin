{{-- Recipients of one day + template summary row. Needs $logs. --}}
<table class="table table-sm mb-0 detail-table">
    <thead>
        <tr>
            <th>Business</th>
            <th>Sent to</th>
            <th>Time</th>
            <th>Sent by</th>
            <th>Status</th>
            <th>Button clicked</th>
            <th class="noExl"></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($logs as $log)
        <tr>
            <td>
                @if ($log->business_name)
                <a href="{{ route('edit_business', encrypt($log->business_id)) }}">{{ $log->business_name }}</a>
                @else
                <span class="text-600">#{{ $log->business_id }}</span>
                @endif
            </td>
            <td class="text-600">{{ $log->recipient_email }}</td>
            <td style="white-space: nowrap;">{{ date('h:i A', strtotime($log->created_at)) }}</td>
            <td style="white-space: nowrap;">
                @if ($log->trigger === 'manual')
                Manual
                @elseif ($log->step !== null)
                Scheduler · day {{ $log->step }}
                @else
                Automatic
                @endif
            </td>
            <td title="{{ $log->error }}">{!! notification_status_badge($log->status) !!}</td>
            <td style="white-space: nowrap;">
                @if ($log->clicked_at)
                <span class="click-yes"><i class="bi bi-cursor-fill"></i> Clicked</span>
                <div class="user-sub">{{ $log->clicked_at->format('d M, h:i A') }}@if ($log->click_count > 1) · {{ $log->click_count }} times @endif</div>
                @elseif ($log->tracking_token)
                <span class="text-600">Not clicked</span>
                @else
                <span class="text-500" title="Sent before click tracking, or the email has no button">Not tracked</span>
                @endif
            </td>
            <td class="noExl" style="white-space: nowrap;">
                @if ($log->business_name)
                @if ($log->type === \App\Helpers\NotificationSender::VERIFICATION_REMINDER)
                @include('notifications.partials.business_actions', ['business_id' => $log->business_id, 'paused' => $log->notifications_paused])
                @endif
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-600 py-3">No emails.</td></tr>
        @endforelse
    </tbody>
</table>
@if (count($logs) >= 500)
<div class="small text-600 px-3 py-2">Showing the latest 500. Narrow the search to see others.</div>
@endif
