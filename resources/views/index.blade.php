@extends('layouts.main')
@section('content')
@php
    $rejected_business = max(0, $total_business - $approved_business - $pending_review);

    // "▲ 3 vs yesterday" chip
    $versus = function ($now, $before) {
        $diff = $now - $before;
        if ($diff === 0) {
            return '<span class="trend is-flat">Same as yesterday</span>';
        }
        return '<span class="trend ' . ($diff > 0 ? 'is-up' : 'is-down') . '"><i class="bi bi-arrow-' . ($diff > 0 ? 'up' : 'down') . '-short"></i>'
            . abs($diff) . ' vs yesterday (' . $before . ')</span>';
    };
@endphp
<div class="content-header">
    <div>
        <h5>Dashboard</h5>
        <p class="page-subtitle">Here's what's happening in World Web Directory.</p>
    </div>
    <a href="{{ route('business_create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add business</a>
</div>
<x-flash />

<div class="card today-card mb-3">
    <div class="today-head">
        <span class="today-icon"><i class="bi bi-calendar-check"></i></span>
        <div>
            <div class="today-title">Today</div>
            <div class="today-date">{{ $end_date->format('j M Y') }} · since 00:00 UTC</div>
        </div>
    </div>
    <div class="today-stat">
        <div class="today-label"><i class="bi bi-building"></i> New businesses</div>
        <div class="today-value">{{ number_format($today_stats['businesses']) }} {!! $versus($today_stats['businesses'], $today_stats['businesses_yesterday']) !!}</div>
        <div class="today-sub">
            @if ($today_stats['businesses'])
            <strong>{{ $today_stats['businesses_pending'] }}</strong> waiting for review ·
            <a href="{{ route('business_list') }}">Review now <i class="bi bi-arrow-right"></i></a>
            @else
            No submissions yet today
            @endif
        </div>
    </div>
    <div class="today-stat">
        <div class="today-label"><i class="bi bi-person-plus"></i> New users</div>
        <div class="today-value">{{ number_format($today_stats['users']) }} {!! $versus($today_stats['users'], $today_stats['users_yesterday']) !!}</div>
        <div class="today-sub">
            @if ($today_stats['users'])
            <strong>{{ $today_stats['users_business'] }}</strong> business · <strong>{{ $today_stats['users'] - $today_stats['users_business'] }}</strong> guest ·
            <a href="{{ route('users') }}">View <i class="bi bi-arrow-right"></i></a>
            @else
            No sign-ups yet today
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card summary-card h-100">
            <div class="summary-head">
                <span class="kpi-icon red"><i class="bi bi-building"></i></span>
                <div>
                    <div class="summary-label">Businesses</div>
                    <div class="summary-total">{{ number_format($total_business) }}
                        <span class="delta-chip"><i class="bi bi-arrow-up-short"></i>{{ number_format($new_businesses) }} in 30 days</span>
                    </div>
                </div>
                <a href="{{ route('business_list') }}" class="summary-link">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="summary-body">
                @include('partials.split_bar', [
                    'title' => 'Admin review',
                    'total' => $total_business,
                    'parts' => [
                        ['Approved', $approved_business, 'is-green', null],
                        ['Pending', $pending_review, 'is-amber', null],
                        ['Rejected', $rejected_business, 'is-red', null],
                    ],
                ])
                @include('partials.split_bar', [
                    'title' => 'Owner verification',
                    'total' => $total_business,
                    'parts' => [
                        ['Verified', $verified_business, 'is-blue', null],
                        ['Unverified', $unverified_business, 'is-gray', route('notification_queue')],
                    ],
                ])
            </div>
            <div class="summary-foot">
                <a href="{{ route('subscriptions') }}"><i class="bi bi-star"></i> <strong>{{ number_format($featured_business) }}</strong> paid {{ Str::plural('listing', $featured_business) }}</a>
                <span><i class="bi bi-hourglass-split"></i> <strong>{{ number_format($pending_review) }}</strong> waiting for review</span>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card summary-card h-100">
            <div class="summary-head">
                <span class="kpi-icon blue"><i class="bi bi-people"></i></span>
                <div>
                    <div class="summary-label">Users</div>
                    <div class="summary-total">{{ number_format($total_users) }}
                        <span class="delta-chip"><i class="bi bi-arrow-up-short"></i>{{ number_format($new_users) }} in 30 days</span>
                    </div>
                </div>
                <a href="{{ route('users') }}" class="summary-link">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="summary-body">
                @include('partials.split_bar', [
                    'title' => 'Account type',
                    'total' => $total_users,
                    'parts' => [
                        ['Business users', $business_users, 'is-red', route('users', ['type' => 'business'])],
                        ['Guest users', $guest_users, 'is-blue', route('users', ['type' => 'guest'])],
                    ],
                ])
                @include('partials.split_bar', [
                    'title' => 'Account activation',
                    'total' => $total_users,
                    'parts' => [
                        ['Activated', $activated_users, 'is-green', route('users', ['account' => 'activated'])],
                        ['Not activated', $total_users - $activated_users, 'is-gray', route('users', ['account' => 'not_activated'])],
                    ],
                ])
            </div>
            <div class="summary-foot">
                <span><i class="bi bi-info-circle"></i> Business users own a listing; guests don't.</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header justify-content-between">
                <div>
                    <h5>Submissions</h5>
                    <div class="text-600 small">{{ $start_date->format('d M') }} – {{ $end_date->format('d M Y') }}</div>
                </div>
                <div class="chart-legend">
                    <span style="--dot: #dd3f3b;">All</span>
                    <span style="--dot: #1f2937;">Paid</span>
                </div>
            </div>
            <div class="card-body">
                <div id="business_chart" style="height: 320px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header justify-content-between">
                <h5>Latest submissions</h5>
                <a href="{{ route('business_list') }}" class="small fw-medium">View all</a>
            </div>
            <ul class="recent-list">
                @forelse ($recent_businesses as $business)
                <li>
                    <a class="recent-item" href="{{ route('edit_business', encrypt($business->business_id)) }}">
                        <span class="recent-initial">{{ strtoupper(mb_substr(trim($business->business_name) ?: '?', 0, 1)) }}</span>
                        <span class="recent-body">
                            <span class="recent-name d-block">{{ $business->business_name }}</span>
                            <span class="recent-date">{{ \Carbon\Carbon::parse($business->created_at)->diffForHumans() }}</span>
                        </span>
                        {!! status_badge($business->is_claimed) !!}
                    </a>
                </li>
                @empty
                <li class="p-4 text-center text-600">No submissions yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

@push('vendor_scripts')
<script src="/assets/vendors/echarts/echarts.min.js"></script>
@endpush

@section('scripts')
<script>
    (function() {
        var chart = echarts.init(document.getElementById('business_chart'));
        chart.setOption({
            textStyle: { fontFamily: 'Inter, system-ui, sans-serif' },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow', shadowStyle: { color: 'rgba(17, 24, 39, 0.04)' } },
                backgroundColor: '#111827',
                borderWidth: 0,
                textStyle: { color: '#fff', fontSize: 12 }
            },
            grid: { left: 8, right: 8, top: 16, bottom: 4, containLabel: true },
            xAxis: {
                type: 'category',
                data: @json($dates),
                axisLine: { lineStyle: { color: '#e5e7eb' } },
                axisTick: { show: false },
                axisLabel: { color: '#6b7280', fontSize: 12 }
            },
            yAxis: {
                type: 'value',
                minInterval: 1,
                splitLine: { lineStyle: { color: '#f0f1f3' } },
                axisLabel: { color: '#9ca3af', fontSize: 12 }
            },
            series: [{
                name: 'All submissions',
                type: 'bar',
                data: @json($total_submissions),
                barMaxWidth: 26,
                barGap: '20%',
                itemStyle: { color: '#dd3f3b', borderRadius: [6, 6, 0, 0] }
            }, {
                name: 'Paid',
                type: 'bar',
                data: @json($paid_submissions),
                barMaxWidth: 26,
                itemStyle: { color: '#1f2937', borderRadius: [6, 6, 0, 0] }
            }]
        });
        window.addEventListener('resize', function() {
            chart.resize();
        });
    })();
</script>
@endsection
