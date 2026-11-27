@extends('layouts.main')
@section('content')
@php
    $listed = (int) $funnel->listed;
    $pct = fn($n, $of) => $of ? round($n / $of * 100) : 0;
    $stages = [
        ['Listed', $listed, null, null],
        ['Invite sent', (int) $funnel->emailed, $listed, 'emailed'],
        ['Clicked claim link', (int) $funnel->clicked, (int) $funnel->emailed, 'clicked'],
        ['Started claim', (int) $funnel->started, (int) $funnel->clicked, 'started'],
        ['Claimed', (int) $funnel->claimed, (int) $funnel->started, 'claimed'],
        ['Premium', (int) $funnel->premium, (int) $funnel->claimed, 'premium'],
    ];
    $avg_days = $funnel->avg_hours_to_claim !== null ? round($funnel->avg_hours_to_claim / 24, 1) : null;
    $periods = ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'all' => 'All time'];
    $statuses = ['not_emailed' => 'Invite not sent', 'emailed' => 'Invite sent', 'clicked' => 'Clicked claim link', 'started' => 'Started, not finished',
        'claimed' => 'Claimed', 'premium' => 'Premium', 'unsubscribed' => 'Unsubscribed'];
    $keep = fn(array $extra) => array_filter(array_merge(request()->only('period', 'status', 'source', 'q'), $extra), fn($v) => $v !== null && $v !== '');
@endphp
<div class="content-header">
    <div>
        <h5>Admin listings</h5>
        <p class="page-subtitle">Businesses the team listed from other directories, and how many owners clicked, claimed and upgraded.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin_listings') }}" class="d-flex">
            @foreach (request()->only('status', 'source', 'q') as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <select name="period" class="form-select" aria-label="Period" onchange="this.form.submit()">
                @foreach ($periods as $key => $label)
                <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('business_create') }}" class="btn btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add business</a>
    </div>
</div>
<x-flash />

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Listed</span><span class="kpi-icon gray"><i class="bi bi-building-add"></i></span></div>
            <div class="kpi-value">{{ number_format($listed) }}</div>
            <div class="kpi-meta">{{ number_format((int) $funnel->today) }} today</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Claim rate</span><span class="kpi-icon green"><i class="bi bi-patch-check"></i></span></div>
            <div class="kpi-value">{{ $pct((int) $funnel->claimed, $listed) }}%</div>
            <div class="kpi-meta">{{ number_format((int) $funnel->claimed) }} of {{ number_format($listed) }} claimed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Avg. time to claim</span><span class="kpi-icon blue"><i class="bi bi-hourglass-split"></i></span></div>
            <div class="kpi-value">{{ $avg_days === null ? '–' : $avg_days . ' d' }}</div>
            <div class="kpi-meta">From listing to claim</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Email problems</span><span class="kpi-icon red"><i class="bi bi-envelope-exclamation"></i></span></div>
            <div class="kpi-value">{{ number_format((int) $funnel->email_failed + (int) $funnel->unsubscribed) }}</div>
            <div class="kpi-meta">{{ number_format((int) $funnel->email_failed) }} failed · {{ number_format((int) $funnel->unsubscribed) }} unsubscribed</div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Funnel <span class="text-600 fw-normal small">· {{ $periods[$period] }}</span></h6></div>
    <div class="card-body">
        <div class="listing-funnel">
            @foreach ($stages as [$label, $count, $previous, $key])
            <a class="funnel-step {{ $status === $key && $key ? 'is-active' : '' }}" href="{{ route('admin_listings', $keep(['status' => $key])) }}">
                <span class="funnel-label">{{ $label }}</span>
                <span class="funnel-value">{{ number_format($count) }}</span>
                <span class="funnel-bar"><span style="width: {{ $pct($count, $listed) }}%"></span></span>
                <span class="funnel-rate">
                    @if ($previous === null)
                    {{ $listed ? '100%' : '–' }}
                    @else
                    {{ $pct($count, $previous) }}% of previous
                    @endif
                </span>
            </a>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">By source site</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Source</th><th class="cat-num">Listed</th><th class="cat-num">Clicked</th><th class="cat-num">Claimed</th><th>Claim rate</th></tr></thead>
                    <tbody>
                        @forelse ($by_source as $row)
                        @php $rate = $pct((int) $row->claimed, (int) $row->listed); @endphp
                        <tr>
                            <td><a href="{{ route('admin_listings', $keep(['source' => $row->source])) }}" class="fw-medium text-900">{{ $row->source }}</a></td>
                            <td class="cat-num">{{ number_format((int) $row->listed) }}</td>
                            <td class="cat-num">{{ number_format((int) $row->clicked) }}</td>
                            <td class="cat-num">{{ number_format((int) $row->claimed) }}</td>
                            <td class="cat-rate-cell">
                                <div class="cat-rate"><div class="cat-bar"><span class="is-approved" style="width: {{ $rate }}%"></span></div><span class="cat-rate-value">{{ $rate }}%</span></div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-600">No listings in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">By admin</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Added by</th><th class="cat-num">Listed</th><th class="cat-num">Claimed</th></tr></thead>
                    <tbody>
                        @forelse ($by_admin as $row)
                        <tr>
                            <td class="fw-medium text-900">{{ trim((string) $row->admin_name) ?: 'Unknown' }}</td>
                            <td class="cat-num">{{ number_format((int) $row->listed) }}</td>
                            <td class="cat-num">{{ number_format((int) $row->claimed) }} <span class="text-600 small">({{ $pct((int) $row->claimed, (int) $row->listed) }}%)</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-600">No listings in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('admin_listings') }}" class="users-toolbar" id="listingsFilter">
            <input type="hidden" name="period" value="{{ $period }}">
            <div class="cat-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Business, website or email…" autocomplete="off">
            </div>
            <select name="status" class="form-select form-select-sm" aria-label="Status">
                <option value="">Any status</option>
                @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="source" class="form-select form-select-sm" aria-label="Source">
                <option value="">Any source</option>
                @foreach ($sources as $source)
                <option value="{{ $source }}" @selected(request('source') === $source)>{{ $source }}</option>
                @endforeach
            </select>
            @if ($has_filters)
            <a href="{{ route('admin_listings', ['period' => $period]) }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
            <span class="users-count ms-auto">{{ number_format($listings->total()) }} {{ Str::plural('listing', $listings->total()) }}</span>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 listings-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Listed</th>
                    <th>Source</th>
                    <th>Invite emails</th>
                    <th>Owner</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($listings as $listing)
                <tr>
                    <td>
                        <a href="{{ route('edit_business', encrypt($listing->business_id)) }}" class="fw-medium text-900">{{ $listing->business_name }}</a>
                        <div class="user-sub">{{ preg_replace('#^https?://#', '', (string) $listing->business_website) }} · {{ $listing->business_email }}</div>
                        @if ($listing->category_name)<div class="user-sub">{{ $listing->category_name }}</div>@endif
                    </td>
                    <td class="text-nowrap">
                        {{ \Carbon\Carbon::parse($listing->created_at)->format('d M Y') }}
                        <div class="user-sub">by {{ trim((string) $listing->admin_name) ?: 'Unknown' }}</div>
                    </td>
                    <td>
                        @if ($listing->source_name)
                        @if ($listing->source_url)
                        <a href="{{ $listing->source_url }}" target="_blank" rel="noopener noreferrer">{{ $listing->source_name }} <i class="bi bi-box-arrow-up-right small"></i></a>
                        @else
                        {{ $listing->source_name }}
                        @endif
                        @else
                        <span class="text-500">–</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if ($listing->notifications_paused)
                        <span class="badge rounded-pill badge-subtle-secondary">Unsubscribed</span>
                        @elseif ((int) $listing->sent > 0)
                        <span class="badge rounded-pill badge-subtle-primary">{{ (int) $listing->sent }} sent</span>
                        @if ((int) $listing->failed > 0)<span class="badge rounded-pill badge-subtle-danger">{{ (int) $listing->failed }} failed</span>@endif
                        @elseif ((int) $listing->failed > 0)
                        <span class="badge rounded-pill badge-subtle-danger">Failed</span>
                        @else
                        <span class="badge rounded-pill badge-subtle-warning">Not sent</span>
                        @endif
                        @if ($listing->last_sent_at)
                        <div class="user-sub">Last {{ \Carbon\Carbon::parse($listing->last_sent_at)->format('d M') }}@if ($listing->next_date) · next {{ $listing->next_date->format('d M') }} (day {{ $listing->next_step }})@endif</div>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if ($listing->is_claimed)
                        <span class="badge rounded-pill badge-subtle-success"><i class="bi bi-patch-check"></i> Claimed</span>
                        @if ($listing->business_status === 'P')<span class="badge rounded-pill badge-subtle-warning">Premium</span>@endif
                        @if ($listing->claimed_at)<div class="user-sub">{{ \Carbon\Carbon::parse($listing->claimed_at)->format('d M Y') }}</div>@endif
                        @elseif ($listing->otp_expires_at)
                        <span class="badge rounded-pill badge-subtle-info">Started claim</span>
                        @elseif ((int) $listing->clicked_emails > 0)
                        <span class="badge rounded-pill badge-subtle-primary"><i class="bi bi-cursor"></i> Clicked</span>
                        <div class="user-sub">{{ \Carbon\Carbon::parse($listing->last_clicked_at)->diffForHumans() }}</div>
                        @else
                        <span class="text-500">No response yet</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if (!$listing->is_claimed && !$listing->notifications_paused)
                        <form method="POST" action="{{ route('admin_listing_resend', encrypt($listing->business_id)) }}" class="d-inline"
                            onsubmit="return confirm('Send the claim invite again?');">
                            @csrf
                            <button type="submit" class="btn btn-light btn-sm text-nowrap"><i class="bi bi-send"></i> Resend</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-600">
                        <i class="bi bi-building-add d-block fs-6 mb-2"></i>
                        @if ($has_filters || $period !== 'all')
                        No listings match these filters.
                        @else
                        No admin listings yet. <a href="{{ route('business_create') }}">Add the first business</a>.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $listings->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
@section('scripts')
<script>
    $('#listingsFilter select').on('change', function() {
        this.form.submit();
    });
</script>
@endsection
