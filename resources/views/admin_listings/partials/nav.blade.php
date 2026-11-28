{{-- Sub-navigation of the Admin listings feature: overview, add one business, Excel import --}}
@php
    $to_review = \App\Models\AdminListingDraft::whereIn('status', ['uploaded', 'needs_fix'])->count();
    $tabs = [
        ['Overview', 'admin_listings', 'bi-bar-chart', request()->routeIs('admin_listings')],
        ['Add business', 'business_create', 'bi-plus-lg', request()->routeIs('business_create')],
        ['Excel import', 'listing_import', 'bi-file-earmark-spreadsheet', request()->routeIs('listing_import', 'listing_draft')],
    ];
@endphp
<ul class="nav nav-tabs status-tabs listing-nav mb-3">
    @foreach ($tabs as [$label, $route, $icon, $active])
    <li class="nav-item">
        <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($route) }}">
            <i class="bi {{ $icon }}"></i> {{ $label }}
            @if ($route === 'listing_import' && $to_review)
            <span class="count is-alert" title="Drafts waiting for review">{{ number_format($to_review) }}</span>
            @endif
        </a>
    </li>
    @endforeach
</ul>
