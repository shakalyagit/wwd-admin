<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr"
    class="{{ request()->is('dashboard-2') || request()->is('dashboard-3') || request()->is('risk-register') || request()->is('assessment') || request()->is('view-risk-register/*') || request()->is('add-risk') || request()->is('edit-risk/*') || request()->is('kri-master') || request()->is('add-kri') || request()->is('edit-kri/*') || request()->is('view-kri-master/*') ? 'navbar-vertical-collapsed' : '' }}">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>{{ env('APP_NAME') }}</title>

    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="manifest" href="/assets/img/favicons/manifest.json">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.0/font/bootstrap-icons.css" />

    <meta name="msapplication-TileImage" content="/assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">

    {{-- Multi File Upload and preview --}}
    <script src="/assets/vendors/dropzone/dropzone.min.js"></script>
    <link href="/assets/vendors/dropzone/dropzone.min.css" rel="stylesheet" />

    <script src="/assets/js/config.js"></script>
    <script src="/assets/vendors/simplebar/simplebar.min.js"></script>
    <script src="/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.min.css"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap"
        rel="stylesheet">
    <link href="/assets/vendors/simplebar/simplebar.min.css" rel="stylesheet">

    <link href="/assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="/assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="/assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="/assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link href="/assets/vendors/choices/choices.min.css" rel="stylesheet" />
    {{-- datepicker --}}
    <link href="/assets/vendors/flatpickr/flatpickr.min.css" rel="stylesheet" />
    <script>
        var isRTL = JSON.parse(localStorage.getItem('isRTL'));
        if (isRTL) {
            var linkDefault = document.getElementById('style-default');
            var userLinkDefault = document.getElementById('user-style-default');
            linkDefault.setAttribute('disabled', true);
            userLinkDefault.setAttribute('disabled', true);
            document.querySelector('html').setAttribute('dir', 'rtl');
        } else {
            var linkRTL = document.getElementById('style-rtl');
            var userLinkRTL = document.getElementById('user-style-rtl');
            linkRTL.setAttribute('disabled', true);
            userLinkRTL.setAttribute('disabled', true);
        }
    </script>
    <style>
        .error {
            color: var(--falcon-form-invalid-border-color) !important;
        }

        select,
        input,
        textarea {
            /* border: 1px solid #0b1727 !important;
            border-radius: 0px !important; */
            color: #0b1727 !important;
        }

        .btn {
            border-radius: 0px !important;
        }

        td,
        th,
        tr,
        label {
            color: #0b1727 !important;
        }

        .navbar-vertical.navbar-vibrant .navbar-nav .nav-item .nav-link:hover,
        .navbar-vertical.navbar-vibrant .navbar-nav .nav-item .nav-link:focus {
            color: #656e7c;
        }
    </style>
    <link href="/assets/css/custom.css" rel="stylesheet">
</head>

<div class="overlay" id="overlay" style="display: none;">
    <div class="loader"></div>
</div>

<body>

    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
        <div class="container-fluid" data-layout="container">

            <!-- ===============================================-->
            <!--    Main Content-->
            <!-- ===============================================-->
