@extends('layouts.main')
@section('content')
<div class="content-header">
    <div>
        <h5>Notifications</h5>
        <p class="page-subtitle">Reminders go out at 11:59 PM UTC on day 0, 1, 3 and 15 after submission, then every 15 days until verified.</p>
    </div>
</div>
<x-flash />
@include('notifications.partials.tabs')
<div class="card mt-3">
    <div class="card-header">
        <form method="get" action="{{ route('notification_queue') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                    placeholder="Business name or email">
            </div>
            <div class="col-auto">
                <select name="paused" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="0" {{ request('paused') === '0' ? 'selected' : '' }}>Active</option>
                    <option value="1" {{ request('paused') === '1' ? 'selected' : '' }}>Paused</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                <a href="{{ route('notification_queue') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
    <div id="tableExample">
        <div class="table-responsive scrollbar">
            <table class="table mb-0 data-table fs-10 tableToExport">
                <thead class="bg-200">
                    <tr>
                        <th class="text-900 sort text-nowrap">Submitted</th>
                        <th class="text-900 sort text-nowrap">Business</th>
                        <th class="text-900 sort text-nowrap">Sent</th>
                        <th class="text-900 sort text-nowrap">Last sent</th>
                        <th class="text-900 sort text-nowrap">Next reminder</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($businesses) > 0)
                    @foreach ($businesses as $business)
                    <tr>
                        <td>{{ date('d-m-Y', strtotime($business->created_at)) }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('edit_business', encrypt($business->business_id)) }}">{{ $business->business_name }}</a>
                            <div class="small text-600">{{ $business->business_email }}</div>
                        </td>
                        <td>{{ $business->reminder_count }}</td>
                        <td style="white-space: nowrap;">
                            @if ($business->last_reminder)
                            {{ $business->last_reminder->sent_at->format('d M, h:i A') }}
                            <div class="small text-600">{{ $business->last_reminder->trigger === 'manual' ? 'Manual resend' : 'Day ' . $business->last_reminder->step }}</div>
                            @else
                            <span class="text-500">–</span>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $business->next_reminder }}</td>
                        <td class="text-nowrap noExl">
                            @include('notifications.partials.business_actions', ['business_id' => $business->business_id, 'paused' => $business->notifications_paused])
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="6" class="text-center"><?php echo no_record_found_in_table(); ?></td>
                    </tr>
                    @endif
                </tbody>
            </table>
            <div class="pagination-wrapper">
                {{ $businesses->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
