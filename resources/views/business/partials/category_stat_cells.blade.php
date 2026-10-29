{{-- Number cells + approval bar for one category row. Needs $stats and $link. --}}
@php
    $rate = $stats['total'] ? round($stats['approved'] / $stats['total'] * 100) : 0;
    $pct = fn($n) => $stats['total'] ? $n / $stats['total'] * 100 : 0;
@endphp
<td class="cat-num">
    @if ($stats['total'])
    <a href="{{ $link }}">{{ number_format($stats['total']) }}</a>
    @else
    <span class="cat-zero">0</span>
    @endif
</td>
<td class="cat-num">
    @if ($stats['approved'])
    <a href="{{ $link }}{{ str_contains($link, '?') ? '&' : '?' }}status=approved" class="cat-approved">{{ number_format($stats['approved']) }}</a>
    @else
    <span class="cat-zero">0</span>
    @endif
</td>
<td class="cat-num">
    @if ($stats['pending'])
    <a href="{{ $link }}{{ str_contains($link, '?') ? '&' : '?' }}status=pending" class="cat-pending">{{ number_format($stats['pending']) }}</a>
    @else
    <span class="cat-zero">0</span>
    @endif
</td>
<td class="cat-rate-cell">
    @if ($stats['total'])
    <div class="cat-rate" title="{{ $stats['approved'] }} approved · {{ $stats['pending'] }} pending · {{ $stats['rejected'] }} rejected">
        <div class="cat-bar">
            <span class="is-approved" style="width: {{ $pct($stats['approved']) }}%"></span>
            <span class="is-pending" style="width: {{ $pct($stats['pending']) }}%"></span>
            <span class="is-rejected" style="width: {{ $pct($stats['rejected']) }}%"></span>
        </div>
        <span class="cat-rate-value">{{ $rate }}%</span>
    </div>
    @else
    <span class="cat-empty-label">No submissions</span>
    @endif
</td>
