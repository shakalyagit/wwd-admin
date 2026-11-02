<ul class="nav nav-tabs mt-2">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('notifications') ? 'active' : '' }}" href="{{ route('notifications') }}">Sent log</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('notification_queue') ? 'active' : '' }}" href="{{ route('notification_queue') }}">Unverified businesses</a>
    </li>
    <li class="nav-item ms-auto">
        <a class="nav-link" href="{{ route('email_templates') }}"><i class="bi bi-envelope-paper"></i> Email templates</a>
    </li>
</ul>
