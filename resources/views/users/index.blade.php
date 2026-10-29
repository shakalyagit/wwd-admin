@extends('layouts.main')
@section('content')
@php
    $pct = fn($n) => $stats->total ? round($n / $stats->total * 100) : 0;
    $kpis = [
        ['Total users', $stats->total, 'bi-people', 'gray', number_format($stats->google) . ' signed up with Google'],
        ['Business users', $stats->business, 'bi-building', 'red', $pct($stats->business) . '% own a listed business'],
        ['Guest users', $stats->guest, 'bi-person', 'blue', $pct($stats->guest) . '% without a business'],
        ['Activated accounts', $stats->activated, 'bi-patch-check', 'green', number_format($stats->total - $stats->activated) . ' not activated yet'],
        ['Active in last 30 days', $stats->active_30, 'bi-activity', 'violet', number_format($stats->ever_signed_in) . ' have ever signed in'],
        ['New in last 30 days', $stats->new_30, 'bi-person-plus', 'amber', 'Joined since ' . now()->subDays(30)->format('d M')],
    ];
    $tabs = ['all' => ['All users', $stats->total], 'business' => ['Business users', $stats->business], 'guest' => ['Guest users', $stats->guest]];
    $filters = request()->only('q', 'account', 'activity', 'status');
@endphp
<div class="content-header">
    <div>
        <h5>Users</h5>
        <p class="page-subtitle">Business users own a listing in the directory; guest users have an account without a business.</p>
    </div>
</div>
<x-flash />

<div class="row g-3 mb-3">
    @foreach ($kpis as [$label, $value, $icon, $color, $meta])
    <div class="col-6 col-lg-4 col-xxl-2">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">{{ $label }}</span><span class="kpi-icon {{ $color }}"><i class="bi {{ $icon }}"></i></span></div>
            <div class="kpi-value">{{ number_format($value) }}</div>
            <div class="kpi-meta">{{ $meta }}</div>
        </div>
    </div>
    @endforeach
</div>

<ul class="nav nav-tabs status-tabs">
    @foreach ($tabs as $key => [$label, $count])
    <li class="nav-item">
        <a class="nav-link {{ $type === $key ? 'active' : '' }}"
            href="{{ route('users', $filters + ($key === 'all' ? [] : ['type' => $key])) }}">{{ $label }}<span class="count">{{ number_format($count) }}</span></a>
    </li>
    @endforeach
</ul>

<div class="card mt-3">
    <div class="card-header">
        <form method="GET" action="{{ route('users') }}" class="users-toolbar" id="usersFilter">
            @if ($type !== 'all')
            <input type="hidden" name="type" value="{{ $type }}">
            @endif
            <div class="cat-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                    placeholder="Name, email or business…" autocomplete="off">
            </div>
            <select name="account" class="form-select form-select-sm" aria-label="Account">
                <option value="">Any account</option>
                <option value="activated" @selected(request('account') === 'activated')>Activated</option>
                <option value="not_activated" @selected(request('account') === 'not_activated')>Not activated</option>
            </select>
            <select name="activity" class="form-select form-select-sm" aria-label="Activity">
                <option value="">Any activity</option>
                <option value="active_30" @selected(request('activity') === 'active_30')>Signed in last 30 days</option>
                <option value="never" @selected(request('activity') === 'never')>Never signed in</option>
            </select>
            <select name="status" class="form-select form-select-sm" aria-label="Status">
                <option value="">Any status</option>
                <option value="enabled" @selected(request('status') === 'enabled')>Enabled</option>
                <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
            </select>
            @if ($has_filters)
            <a href="{{ route('users', $type === 'all' ? [] : ['type' => $type]) }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
            <span class="users-count ms-auto">{{ number_format($users->total()) }} {{ Str::plural('user', $users->total()) }}</span>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Joined</th>
                    <th>Last sign-in</th>
                    <th>Account</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                @php
                    $name = trim($user->first_name . ' ' . $user->last_name);
                    $initials = mb_strtoupper(mb_substr($user->first_name ?: $user->email, 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
                @endphp
                <tr>
                    <td>
                        <div class="user-cell">
                            <span class="user-avatar">
                                {{ $initials }}
                                @if ($user->google_avatar)
                                <img src="{{ $user->google_avatar }}" alt="" loading="lazy" referrerpolicy="no-referrer"
                                    onload="this.classList.add('is-loaded')" onerror="this.remove()">
                                @endif
                            </span>
                            <span class="user-text">
                                <span class="user-name">{{ $name !== '' ? $name : 'No name' }}
                                    @if ($user->google_id)<i class="bi bi-google user-google" title="Signed up with Google"></i>@endif
                                </span>
                                <span class="user-email">{{ $user->email }}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        @if ($user->business_id)
                        <span class="type-pill is-business"><i class="bi bi-building"></i> Business</span>
                        <a href="{{ route('edit_business', encrypt($user->business_id)) }}" class="user-business" title="Open business">
                            {{ $user->business_name }}@if ($user->business_count > 1) <span class="text-600">+{{ $user->business_count - 1 }}</span>@endif
                        </a>
                        @else
                        <span class="type-pill is-guest"><i class="bi bi-person"></i> Guest</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        @if ($user->created_at)
                        {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}
                        <div class="user-sub">{{ \Carbon\Carbon::parse($user->created_at)->diffForHumans() }}</div>
                        @else
                        <span class="text-500">Unknown</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        @if ($user->last_login_at)
                        {{ \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() }}
                        <div class="user-sub">{{ \Carbon\Carbon::parse($user->last_login_at)->format('d M Y, h:i A') }}</div>
                        @else
                        <span class="text-500">Never</span>
                        @endif
                    </td>
                    <td>
                        @if ($user->is_active == 1)
                        <span class="badge rounded-pill d-block badge-subtle-success">Activated</span>
                        @else
                        <span class="badge rounded-pill d-block badge-subtle-warning">Not activated</span>
                        @endif
                    </td>
                    <td>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input user-status-switch" type="checkbox" role="switch"
                                data-id="{{ $user->id }}" data-name="{{ $name !== '' ? $name : $user->email }}"
                                @checked($user->status == 1) aria-label="Account enabled">
                            <span class="user-status-label">{{ $user->status == 1 ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-600">
                        <i class="bi bi-people d-block fs-6 mb-2"></i>No users match these filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

<div class="modal fade" id="confirmStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmStatusTitle">Change account status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmStatusText"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    // Filters apply as soon as a select changes; search applies on Enter
    $('#usersFilter select').on('change', function() {
        this.form.submit();
    });

    // Enable / disable an account after confirmation
    let selected_switch = null;
    let status_confirmed = false;
    const status_modal = new bootstrap.Modal(document.getElementById('confirmStatusModal'));

    $(document).on('click', '.user-status-switch', function(e) {
        e.preventDefault();
        selected_switch = this;
        // Inside a click handler the checkbox already holds its new (requested) state
        const enabling = this.checked;
        $('#confirmStatusTitle').text(enabling ? 'Enable account' : 'Disable account');
        $('#confirmStatusText').text((enabling ? 'Enable' : 'Disable') + ' the account of ' + this.dataset.name + '?');
        $('#confirmStatusBtn').text(enabling ? 'Enable' : 'Disable');
        status_modal.show();
    });

    $('#confirmStatusBtn').on('click', function() {
        const button = $(this).prop('disabled', true);
        $.ajax({
            url: "{{ route('users_status_update') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                user_id: selected_switch.dataset.id
            },
            success: function(response) {
                const enabled = response.status == 1;
                selected_switch.checked = enabled;
                $(selected_switch).siblings('.user-status-label').text(enabled ? 'Enabled' : 'Disabled');
                status_modal.hide();
            },
            error: function() {
                alert('Could not change the status. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
</script>
@endsection
