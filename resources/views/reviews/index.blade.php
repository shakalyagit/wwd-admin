@extends('layouts.main')
@section('content')
@php
    $total = (int) $stats->total;
    $waiting = (int) $stats->waiting;
    $reply_rate = $total ? round(($total - $waiting) / $total * 100) : 0;
    $today_diff = (int) $stats->today - (int) $stats->yesterday;
    $kpis = [
        ['Total reviews', $total, 'bi-chat-square-text', 'gray', 'For ' . number_format((int) $stats->businesses) . ' ' . Str::plural('business', (int) $stats->businesses)],
        ['Today', (int) $stats->today, 'bi-calendar-day', 'blue', number_format((int) $stats->yesterday) . ' yesterday' . ($today_diff > 0 ? ' · ▲ ' . $today_diff : ($today_diff < 0 ? ' · ▼ ' . abs($today_diff) : ''))],
        ['Last 30 days', (int) $stats->new_30, 'bi-graph-up-arrow', 'violet', number_format((int) $stats->this_month) . ' this month'],
        ['Average rating', $stats->average, 'bi-star-fill', 'amber', 'Out of 5, across all reviews'],
        ['Waiting for reply', $waiting, 'bi-reply', 'red', $reply_rate . '% answered by owners'],
        ['Low ratings (1–2★)', (int) $stats->low, 'bi-hand-thumbs-down', 'gray', number_format((int) $stats->low_30) . ' in the last 30 days'],
    ];
    $tabs = [
        'all' => ['All reviews', $total],
        'waiting' => ['Waiting for reply', $waiting],
        'replied' => ['Replied', $total - $waiting],
        'low' => ['Low ratings', (int) $stats->low],
        'hidden' => ['Hidden', (int) $stats->hidden],
    ];
    $filters = request()->only('q', 'rating', 'from_date', 'to_date');
    $max_stars = max(1, (int) $by_stars->max());
    $sources = ['link' => 'Review link', 'qr' => 'QR code', 'badge' => 'Website badge', 'email' => 'WWD email'];
@endphp
<div class="content-header">
    <div>
        <h5>Reviews</h5>
        <p class="page-subtitle">Customer reviews written on the WWD site, and whether the business owner has replied.</p>
    </div>
</div>
<x-flash />

<div class="row g-3 mb-3">
    @foreach ($kpis as [$label, $value, $icon, $color, $meta])
    <div class="col-6 col-lg-4 col-xxl-2">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">{{ $label }}</span><span class="kpi-icon {{ $color }}"><i class="bi {{ $icon }}"></i></span></div>
            <div class="kpi-value">{{ $label === 'Average rating' ? ($value !== null ? number_format((float) $value, 1) . ' ★' : '–') : number_format($value) }}</div>
            <div class="kpi-meta">{{ $meta }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Rating breakdown</h6></div>
            <div class="card-body">
                @foreach ([5, 4, 3, 2, 1] as $star)
                @php $count = (int) ($by_stars[$star] ?? 0); @endphp
                <a class="review-dist-row" href="{{ route('reviews', ['rating' => $star]) }}" title="Show {{ $star }}-star reviews">
                    <span class="review-dist-label">{{ $star }} <i class="bi bi-star-fill"></i></span>
                    <span class="cat-bar"><span class="{{ $star >= 4 ? 'is-approved' : ($star === 3 ? 'is-pending' : 'is-rejected') }}" style="width: {{ round($count / $max_stars * 100) }}%"></span></span>
                    <span class="cat-rate-value">{{ number_format($count) }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Most reviewed businesses</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <tbody>
                        @forelse ($top_businesses as $top)
                        <tr>
                            <td>
                                <a href="{{ route('edit_business', encrypt($top->business_id)) }}" class="fw-medium text-900">{{ $top->business_name ?? 'Business #' . $top->business_id }}</a>
                            </td>
                            <td class="text-nowrap">@if ($top->average !== null)<span class="review-stars-sm">{{ number_format((float) $top->average, 1) }} <i class="bi bi-star-fill"></i></span>@endif</td>
                            <td class="text-end text-nowrap"><strong>{{ number_format((int) $top->total) }}</strong> <span class="text-600">{{ Str::plural('review', (int) $top->total) }}</span></td>
                        </tr>
                        @empty
                        <tr><td class="text-center py-4 text-600">No reviews yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs status-tabs">
    @foreach ($tabs as $key => [$label, $count])
    <li class="nav-item">
        <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
            href="{{ route('reviews', $filters + ($key === 'all' ? [] : ['tab' => $key])) }}">{{ $label }}<span class="count {{ $key === 'waiting' && $count ? 'is-alert' : '' }}">{{ number_format($count) }}</span></a>
    </li>
    @endforeach
</ul>

<div class="card mt-3">
    <div class="card-header">
        <form method="GET" action="{{ route('reviews') }}" class="users-toolbar" id="reviewsFilter">
            @if ($tab !== 'all')
            <input type="hidden" name="tab" value="{{ $tab }}">
            @endif
            <div class="cat-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                    placeholder="Business, reviewer or text…" autocomplete="off">
            </div>
            <select name="rating" class="form-select form-select-sm" aria-label="Rating">
                <option value="">Any rating</option>
                @foreach ([5, 4, 3, 2, 1] as $star)
                <option value="{{ $star }}" @selected(request('rating') == $star)>{{ $star }} {{ Str::plural('star', $star) }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm w-auto" aria-label="From date">
            <span class="text-600 small">to</span>
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm w-auto" aria-label="To date">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            @if ($has_filters)
            <a href="{{ route('reviews', $tab === 'all' ? [] : ['tab' => $tab]) }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
            <span class="users-count ms-auto">{{ number_format($reviews->total()) }} {{ Str::plural('review', $reviews->total()) }}</span>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 reviews-table">
            <thead>
                <tr>
                    <th>Review</th>
                    <th>Business</th>
                    <th>Reviewer</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reviews as $review)
                @php
                    $reviewer = trim($review->reviewer_first_name . ' ' . $review->reviewer_last_name);
                    $replied = trim((string) $review->review_reply_message) !== '';
                @endphp
                <tr>
                    <td class="review-cell">
                        <span class="review-stars" title="{{ $review->stars }} out of 5">
                            @for ($i = 1; $i <= 5; $i++)<i class="bi {{ $i <= $review->stars ? 'bi-star-fill' : 'bi-star' }}"></i>@endfor
                        </span>
                        @if (trim((string) $review->description) !== '')
                        <p class="review-text">{{ $review->description }}</p>
                        @else
                        <p class="review-text text-500">Rating only, no text.</p>
                        @endif
                        @if ($replied)
                        <div class="review-reply">
                            <span class="review-reply-label"><i class="bi bi-reply"></i> Owner replied{{ $review->review_reply_at ? ' · ' . \Carbon\Carbon::parse($review->review_reply_at)->format('d M Y') : '' }}</span>
                            {{ $review->review_reply_message }}
                        </div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('edit_business', encrypt($review->business_id)) }}" class="fw-medium text-900">{{ $review->business_name ?? 'Business #' . $review->business_id }}</a>
                        @if ($review->category_name)<div class="user-sub">{{ $review->category_name }}</div>@endif
                    </td>
                    <td>
                        <span class="d-block text-900">{{ $reviewer !== '' ? $reviewer : 'Deleted user' }}</span>
                        @if ($review->reviewer_email)<span class="user-sub">{{ $review->reviewer_email }}</span>@endif
                    </td>
                    <td class="text-nowrap">
                        @if ($review->created_at)
                        {{ \Carbon\Carbon::parse($review->created_at)->format('d M Y') }}
                        <div class="user-sub">{{ \Carbon\Carbon::parse($review->created_at)->diffForHumans() }}</div>
                        @endif
                        @if ($has_source)
                        <div class="user-sub">via {{ $sources[$review->source] ?? 'WWD site' }}</div>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if ($review->status == '1')
                        <span class="badge rounded-pill badge-subtle-success">Shown</span>
                        @else
                        <span class="badge rounded-pill badge-subtle-secondary">Hidden</span>
                        @endif
                        <div class="mt-1">
                            @if ($replied)
                            <span class="badge rounded-pill badge-subtle-primary">Replied</span>
                            @else
                            <span class="badge rounded-pill badge-subtle-warning">Waiting</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-600">
                        <i class="bi bi-chat-square-text d-block fs-6 mb-2"></i>No reviews {{ $has_filters || $tab !== 'all' ? 'match these filters' : 'yet' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $reviews->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
@section('scripts')
<script>
    // Rating applies as soon as it changes; search and dates apply on Enter / Apply
    $('#reviewsFilter select').on('change', function() {
        this.form.submit();
    });
</script>
@endsection
