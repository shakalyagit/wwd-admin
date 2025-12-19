@props(['url', 'btn_class', 'icon', 'title'])
<div>
    <a href="{{ $url }}" class="btn {{ $btn_class }}">
        <i class="bi {{ $icon }}"></i> {{ $title }}
    </a>
</div>
