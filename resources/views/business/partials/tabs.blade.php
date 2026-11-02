{{-- Businesses page tabs. Needs $ready_count. --}}
<ul class="nav nav-tabs status-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('business_list') ? 'active' : '' }}" href="{{ route('business_list') }}">All businesses</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('business_approval_queue') ? 'active' : '' }}" href="{{ route('business_approval_queue') }}">
            Ready for approval<span class="count {{ $ready_count ? 'is-alert' : '' }}">{{ number_format($ready_count) }}</span>
        </a>
    </li>
</ul>
