@if (count($logs) > 0)
@foreach ($logs as $log)
<tr>
    <td class="text-nowrap">{{ date('d-m-Y h:i A', strtotime($log->created_at)) }}</td>
    <td class="text-nowrap">{{ $log->business_name ?? '#' . $log->business_id }}</td>
    <td class="text-nowrap">{{ $log->recipient_email }}</td>
    <td class="text-nowrap">{{ ucwords(str_replace('_', ' ', $log->type)) }}</td>
    <td class="text-nowrap">{{ $log->trigger === 'manual' ? 'Manual' : 'Day ' . $log->step }}</td>
    <td class="text-nowrap" title="{{ $log->error }}">{!! notification_status_badge($log->status) !!}</td>
    <td class="text-nowrap noExl">
        @if ($log->business_name)
        @include('notifications.partials.business_actions', ['business_id' => $log->business_id, 'paused' => $log->notifications_paused])
        @endif
    </td>
</tr>
@endforeach
@else
<tr>
    <td colspan="7" class="text-center"><?php echo no_record_found_in_table(); ?></td>
</tr>
@endif
