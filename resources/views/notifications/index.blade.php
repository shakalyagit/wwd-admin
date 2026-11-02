@extends('layouts.main')
@section('content')
@php
    $rate = fn($clicked, $tracked) => $tracked ? round($clicked / $tracked * 100) : null;
    $total_rate = $rate((int) $totals->clicked, (int) $totals->tracked);
    $has_filters = request()->filled('filter_type') || request()->filled('from_date') || request()->filled('to_date') || request()->filled('q');
    // Passed to the details request; built here because @json() splits its argument on commas
    $active_filters = request()->only(['q', 'from_date', 'to_date', 'filter_type']);
@endphp
<div class="content-header">
    <div>
        <h5>Notifications</h5>
        <p class="page-subtitle">Scheduled bulk emails sent by the cron job, grouped by day and email template. Click a row to see who got it and who clicked the button.</p>
    </div>
</div>
<x-flash />
@include('notifications.partials.tabs')

<div class="row g-3 my-1">
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Emails sent</span><span class="kpi-icon blue"><i class="bi bi-send"></i></span></div>
            <div class="kpi-value">{{ number_format((int) $totals->sent) }}</div>
            <div class="kpi-meta"><strong>{{ number_format((int) $totals->manual) }}</strong> sent manually</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Failed</span><span class="kpi-icon red"><i class="bi bi-exclamation-octagon"></i></span></div>
            <div class="kpi-value">{{ number_format((int) $totals->failed) }}</div>
            <div class="kpi-meta">Retried automatically for reminders</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Button clicks</span><span class="kpi-icon green"><i class="bi bi-cursor"></i></span></div>
            <div class="kpi-value">{{ number_format((int) $totals->clicked) }}</div>
            <div class="kpi-meta">{!! $total_rate === null ? 'No tracked emails yet' : '<strong>' . $total_rate . '%</strong> of tracked emails' !!}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Tracked emails</span><span class="kpi-icon gray"><i class="bi bi-bar-chart"></i></span></div>
            <div class="kpi-value">{{ number_format((int) $totals->tracked) }}</div>
            <div class="kpi-meta">Sent with a trackable button</div>
        </div>
    </div>
</div>

<div class="card mt-2">
    <div class="card-header">
        <form method="GET" action="{{ route('notifications') }}" class="users-toolbar" id="logFilter">
            <div class="cat-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Business or email…">
            </div>
            <select name="filter_type" class="form-select form-select-sm" aria-label="Email template">
                <option value="">All scheduled emails</option>
                @foreach ($types as $key => $name)
                <option value="{{ $key }}" @selected(request('filter_type') === $key)>{{ $name }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm" style="width: auto;" aria-label="From date">
            <span class="text-600 small">to</span>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm" style="width: auto;" aria-label="To date">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            @if ($has_filters)
            <a href="{{ route('notifications') }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 log-table">
            <thead>
                <tr>
                    <th style="width: 36px;"></th>
                    <th>Date</th>
                    <th>Email template</th>
                    <th class="cat-num">Sent</th>
                    <th class="cat-num">Failed</th>
                    <th>Button clicked</th>
                    <th>Last sent</th>
                </tr>
            </thead>
            @forelse ($groups as $group)
            @php $group_rate = $rate((int) $group->clicked, (int) $group->tracked); @endphp
            <tbody class="log-group" data-day="{{ $group->day }}" data-type="{{ $group->type }}">
                <tr class="log-summary" tabindex="0" role="button" aria-expanded="false">
                    <td><i class="bi bi-chevron-right log-chevron"></i></td>
                    <td style="white-space: nowrap;">
                        <strong>{{ \Carbon\Carbon::parse($group->day)->format('d M Y') }}</strong>
                        <div class="user-sub">{{ \Carbon\Carbon::parse($group->day)->format('l') }}</div>
                    </td>
                    <td>
                        <span class="d-block fw-medium text-900">{{ $types[$group->type] ?? Str::headline($group->type) }}</span>
                        <code class="tpl-key">{{ $group->type }}</code>
                    </td>
                    <td class="cat-num"><strong>{{ number_format((int) $group->sent) }}</strong></td>
                    <td class="cat-num">
                        @if ($group->failed)
                        <span class="text-danger fw-semibold">{{ number_format((int) $group->failed) }}</span>
                        @else
                        <span class="cat-zero">0</span>
                        @endif
                    </td>
                    <td class="cat-rate-cell">
                        @if ($group_rate === null)
                        <span class="cat-empty-label">Not tracked</span>
                        @else
                        <div class="cat-rate">
                            <div class="cat-bar"><span class="is-approved" style="width: {{ $group_rate }}%"></span></div>
                            <span class="cat-rate-value" style="width: auto;">{{ (int) $group->clicked }} · {{ $group_rate }}%</span>
                        </div>
                        @endif
                    </td>
                    <td style="white-space: nowrap;" class="text-600">{{ date('h:i A', strtotime($group->last_sent)) }}</td>
                </tr>
                <tr class="log-detail" hidden>
                    <td colspan="7" class="p-0">
                        <div class="log-detail-body"><div class="text-center text-600 py-3">Loading…</div></div>
                    </td>
                </tr>
            </tbody>
            @empty
            <tbody>
                <tr><td colspan="7" class="text-center py-5 text-600"><i class="bi bi-envelope d-block fs-6 mb-2"></i>No emails {{ $has_filters ? 'match these filters' : 'sent yet' }}.</td></tr>
            </tbody>
            @endforelse
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $groups->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
@section('scripts')
<script>
    // Expand a summary row: load its recipients once, then toggle
    (function() {
        var filters = @json($active_filters);

        function toggle(group) {
            var detail = group.querySelector('.log-detail');
            var summary = group.querySelector('.log-summary');
            var open = detail.hidden;
            detail.hidden = !open;
            group.classList.toggle('is-open', open);
            summary.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open && !group.dataset.loaded) {
                group.dataset.loaded = '1';
                $.get(@json(route('notification_details')), Object.assign({}, filters, { day: group.dataset.day, type: group.dataset.type }))
                    .done(function(response) { detail.querySelector('.log-detail-body').innerHTML = response.html; })
                    .fail(function() {
                        group.dataset.loaded = '';
                        detail.querySelector('.log-detail-body').innerHTML = '<div class="text-center text-danger py-3">Could not load the details. Click to try again.</div>';
                    });
            }
        }

        document.querySelectorAll('.log-group').forEach(function(group) {
            var summary = group.querySelector('.log-summary');
            summary.addEventListener('click', function() { toggle(group); });
            summary.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle(group);
                }
            });
        });

        $('#logFilter select').on('change', function() { this.form.submit(); });
    })();
</script>
@endsection
