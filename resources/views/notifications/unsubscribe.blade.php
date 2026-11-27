<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Email preferences · World Web Directory</title>
    <link rel="icon" type="image/png" href="/assets/brand/wwd-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="/assets/css/theme.min.css" rel="stylesheet">
    <link href="/assets/css/wwd-admin.css" rel="stylesheet">
</head>

<body class="unsub-page">
    <main class="unsub-card card">
        <img src="/assets/brand/wwd-logo.png" alt="World Web Directory" width="88" height="33" class="mb-4">
        @if ($done)
        <span class="unsub-icon is-done"><i class="bi bi-check-lg"></i></span>
        <h1>You're unsubscribed</h1>
        <p>We won't send any more reminder emails about <strong>{{ $business->business_name }}</strong> to {{ $business->business_email }}.</p>
        <p class="text-600 small mb-0">Changed your mind? Write to us and we'll switch them back on.</p>
        @else
        <span class="unsub-icon"><i class="bi bi-envelope-slash"></i></span>
        <h1>Stop WWD emails?</h1>
        <p>You'll no longer get reminder emails about <strong>{{ $business->business_name }}</strong> at {{ $business->business_email }}. The listing itself stays on World Web Directory.</p>
        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">Unsubscribe</button>
        </form>
        @endif
    </main>
</body>

</html>
