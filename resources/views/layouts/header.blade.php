<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="/assets/brand/wwd-icon.png">
    <link rel="apple-touch-icon" href="/assets/brand/wwd-icon-180.png">
    <meta name="theme-color" content="#ffffff">

    {{-- Restore sidebar state before first paint to avoid a flicker --}}
    <script>
        try {
            if (localStorage.getItem('wwdSidebarCollapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="/assets/css/theme.min.css" rel="stylesheet">
    <link href="/assets/css/user.min.css" rel="stylesheet">
    <link href="/assets/css/custom.css" rel="stylesheet">
    <link href="/assets/css/wwd-admin.css" rel="stylesheet">
    @stack('styles')
</head>

<body>
    <div class="overlay" id="overlay" style="display: none;">
        <div class="loader"></div>
    </div>

    <div class="app">
