@php
    // Sidebar menu: section => [label, route, icon, url patterns that mark it active]
    $nav_sections = [
        'Overview' => [
            ['Dashboard', 'dashboard', 'bi-grid-1x2', ['dashboard']],
        ],
        'Directory' => [
            ['Businesses', 'business_list', 'bi-building', ['business-list', 'edit-business/*', 'business/*']],
            ['Old businesses', 'old_business_list', 'bi-archive', ['old-business-list', 'edit-old-business/*']],
            ['Categories', 'category_count', 'bi-tags', ['category-count', 'category/*/businesses', 'add-category']],
        ],
        'Customers' => [
            ['Users', 'users', 'bi-people', ['users']],
            ['Subscriptions', 'subscriptions', 'bi-credit-card', ['subscriptions', 'subscriptions/*']],
        ],
        'Engagement' => [
            ['Notifications', 'notifications', 'bi-bell', ['notification*', 'edit-notification-template/*']],
        ],
    ];

    $admin = Auth::user();
    $admin_name = trim($admin->first_name . ' ' . $admin->last_name) ?: 'Admin';
    $admin_initials = strtoupper(mb_substr($admin->first_name ?: $admin->email, 0, 1) . mb_substr($admin->last_name ?? '', 0, 1));
@endphp

<aside class="app-sidebar" id="appSidebar">
    <a class="app-brand" href="{{ route('dashboard') }}" aria-label="WWD Admin – dashboard">
        <img class="app-brand-logo" src="/assets/brand/wwd-logo.png" alt="WWD" width="74" height="28">
        <img class="app-brand-icon" src="/assets/brand/wwd-icon.png" alt="WWD" width="32" height="32">
        <span class="app-brand-tag">Admin</span>
    </a>
    <nav class="app-nav">
        @foreach ($nav_sections as $section => $items)
        <div class="app-nav-label">{{ $section }}</div>
        @foreach ($items as [$label, $route, $icon, $patterns])
        <a class="app-nav-link {{ request()->is(...$patterns) ? 'active' : '' }}" href="{{ route($route) }}"
            title="{{ $label }}">
            <i class="bi {{ $icon }}"></i>
            <span>{{ $label }}</span>
        </a>
        @endforeach
        @endforeach
    </nav>
</aside>
<div class="app-backdrop" id="appBackdrop"></div>

<div class="app-main">
    <header class="app-topbar">
        <button class="app-icon-btn" type="button" id="sidebarToggle" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="app-topbar-date d-none d-md-inline">{{ now()->format('l, j M Y') }}</span>

        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="app-icon-btn" href="https://www.worldweb-directory.com/" target="_blank" rel="noopener"
                title="Open public site"><i class="bi bi-box-arrow-up-right"></i></a>
            <div class="dropdown">
                <button class="app-user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="app-avatar">
                        @if ($admin->profile_pic)
                        <img src="{{ asset($admin->profile_pic) }}" alt="">
                        @else
                        {{ $admin_initials }}
                        @endif
                    </span>
                    <span class="app-user-name d-none d-sm-block">{{ $admin_name }}<small>Administrator</small></span>
                    <i class="bi bi-chevron-down text-500 d-none d-sm-inline" style="font-size: 12px;"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" style="min-width: 220px;">
                    <div class="dropdown-header">
                        <div class="fw-semibold text-900">{{ $admin_name }}</div>
                        <div class="text-600 small">{{ $admin->email }}</div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                        data-bs-target="#exampleModal"><i class="bi bi-shield-lock"></i> Change password</a>
                    <a class="dropdown-item" href="{{ route('logout') }}"><i class="bi bi-box-arrow-right"></i> Sign out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Change password modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Change password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current password</label>
                            <input class="form-control" type="password" name="old_password" autocomplete="current-password">
                            <span id="old_password_error" class="text-danger"></span>
                            <span id="old_password_match_error" class="text-danger"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input class="form-control" type="password" name="password" autocomplete="new-password">
                            <span id="password_error" class="text-danger"></span>
                        </div>
                        <div>
                            <label class="form-label">Confirm new password</label>
                            <input class="form-control" type="password" name="password_confirmation" autocomplete="new-password">
                            <span id="password_confirmation_error" class="text-danger"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main class="app-content">
