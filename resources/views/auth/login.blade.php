<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    <title>{{ env('APP_NAME') }}</title>

    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/img/icons/spot-illustrations/wwd.png">
    <link rel="manifest" href="/assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="/assets/img/icons/spot-illustrations/wwd.png">
    <meta name="theme-color" content="#ffffff">
    <script src="/assets/js/config.js"></script>
    <script src="/assets/vendors/simplebar/simplebar.min.js"></script>

    <!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap"
        rel="stylesheet">
    <link href="/assets/vendors/simplebar/simplebar.min.css">
    <link href="/assets/css/theme-rtl.min.css" rel="stylesheet">
    <link href="/assets/css/theme.min.css" rel="stylesheet">
    <link href="/assets/css/user-rtl.min.css" rel="stylesheet">
    <link href="/assets/css/user.min.css" rel="stylesheet">
</head>

<body>
    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
        <div class="container-fluid">
            <script>
                var isFluid = JSON.parse(localStorage.getItem('isFluid'));
                if (isFluid) {
                    var container = document.querySelector('[data-layout]');
                    container.classList.remove('container');
                    container.classList.add('container-fluid');
                }
            </script>
            <div class="row min-vh-100 bg-100">
                <div class="col-6 d-none d-lg-block position-relative">
                    <div class="bg-holder"
                        style="background-image:url(/assets/img/generic/14.png);background-position: 50% 20%;">
                    </div>
                </div>
                <div class="col-sm-10 col-md-6 px-sm-0 align-self-center mx-auto py-5">
                    <div class="row justify-content-center g-0">
                        <div class="col-lg-9 col-xl-8 col-xxl-6">
                            <div class="mt-3 mb-3">
                                @if (Session('error'))
                                    <div class="alert alert-danger mt-4 mb-4" role="alert">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                @if (Session('success'))
                                    <div class="alert alert-success mt-4 mb-4" role="alert">
                                        {{ session('success') }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-center">
                                <a href="" class="text-center p-4">
                                    <img class="text-center" src="/assets/img/icons/spot-illustrations/wwd.png"
                                        alt="" style="border-radius: 50px; height: 100px">
                                </a>
                                <h2 class="mt-3 mb-5">World Web Diretory</h2>
                            </div>

                            <div class="card shadow-none border border-secondary">
                                <div class="card-body p-4">
                                    <div class="row flex-between-center">
                                        <div class="text-center">
                                            <h5>Login</h5>
                                        </div>
                                    </div>
                                    <form class="needs-validation" action="{{ route('admin.login.action') }}"
                                        method="POST" id="login_form">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label" for="split-login-email">Email address</label>
                                            <input class="form-control" id="split-login-email" type="email"
                                                value="{{ old('email') }}" name="email" required />
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <label class="form-label" for="split-login-password">Password</label>
                                            </div>
                                            <input class="form-control" id="split-login-password" type="password"
                                                name="password" required />
                                        </div>
                                        <div class="mb-3">
                                            <button class="btn btn-primary d-block w-100 mt-3"
                                                type="submit">Login</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->

    <script src="/assets/vendors/popper/popper.min.js"></script>
    <script src="/assets/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="/assets/vendors/anchorjs/anchor.min.js"></script>
    <script src="/assets/vendors/is/is.min.js"></script>
    <script src="/assets/vendors/fontawesome/all.min.js"></script>
    <script src="/assets/vendors/lodash/lodash.min.js"></script>
    <script src="/assets/vendors/list.js/list.min.js"></script>
    <script src="/assets/js/theme.js"></script>

    <script>
        $(document).ready(function() {

        });
    </script>
</body>

</html>
