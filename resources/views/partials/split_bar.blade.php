{{-- One labelled split bar with a legend. Needs $title, $total and $parts = [[label, value, colour class, link|null], ...] --}}
<div class="split">
    <div class="split-title">{{ $title }}</div>
    <div class="split-bar" role="img"
        aria-label="{{ collect($parts)->map(fn($p) => $p[0] . ' ' . $p[1])->join(', ') }}">
        @foreach ($parts as [$label, $value, $color])
        @if ($value > 0)
        <span class="{{ $color }}" style="width: {{ $total ? $value / $total * 100 : 0 }}%"
            title="{{ $label }}: {{ number_format($value) }}"></span>
        @endif
        @endforeach
    </div>
    <div class="split-legend">
        @foreach ($parts as [$label, $value, $color, $link])
        @php $share = $total ? round($value / $total * 100) : 0; @endphp
        @if ($link)
        <a href="{{ $link }}" class="split-item">
        @else
        <span class="split-item">
        @endif
            <i class="split-dot {{ $color }}"></i>
            <span class="split-label">{{ $label }}</span>
            <strong>{{ number_format($value) }}</strong>
            <span class="split-share">{{ $share }}%</span>
        @if ($link)
        </a>
        @else
        </span>
        @endif
        @endforeach
    </div>
</div>
