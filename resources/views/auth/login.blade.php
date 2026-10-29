<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="/assets/brand/wwd-icon.png">
    <link rel="apple-touch-icon" href="/assets/brand/wwd-icon-180.png">
    <meta name="theme-color" content="#ffffff">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="/assets/css/theme.min.css" rel="stylesheet">
    <link href="/assets/css/wwd-admin.css" rel="stylesheet">
</head>

<body>
    <main class="auth">
        <section class="auth-brand">
            <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 1;">
                <img src="/assets/brand/wwd-logo-white.png" alt="WWD" height="44" width="117">
                <span class="auth-brand-name">World Web<br>Directory</span>
            </div>
            <div class="position-relative" style="z-index: 1;">
                <h1>Admin console</h1>
                <p>Review submissions, verify businesses and keep the directory trustworthy.</p>
                <ul class="auth-points">
                    <li><i class="bi bi-patch-check"></i> Approve and verify business listings</li>
                    <li><i class="bi bi-bell"></i> Automatic verification reminders</li>
                    <li><i class="bi bi-credit-card"></i> Track subscriptions and payments</li>
                </ul>
            </div>
            <div class="small position-relative" style="z-index: 1; color: rgba(255,255,255,.7);">
                &copy; {{ date('Y') }} World Web Directory
            </div>
        </section>

        <section class="auth-form-wrap">
            <div class="auth-form">
                <div class="auth-mobile-brand">
                    <img src="/assets/brand/wwd-logo.png" alt="WWD" height="32" width="85"> <span>Admin</span>
                </div>

                <h2>Welcome back</h2>
                <p class="lead-text">Sign in with your admin account to continue.</p>

                @if (session('error'))
                <div class="alert alert-danger mb-4" role="alert"><i class="bi bi-exclamation-circle"></i> {{ session('error') }}</div>
                @endif
                @if (session('success'))
                <div class="alert alert-success mb-4" role="alert"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
                @endif

                <form action="{{ route('admin.login.action') }}" method="POST" id="login_form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="login-email">Email address</label>
                        <input class="form-control @error('email') is-invalid @enderror" id="login-email" type="email"
                            name="email" value="{{ old('email') }}" placeholder="you@worldweb-directory.com"
                            autocomplete="username" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="login-password">Password</label>
                        <div class="password-field">
                            <input class="form-control @error('password') is-invalid @enderror" id="login-password"
                                type="password" name="password" placeholder="Enter your password"
                                autocomplete="current-password" required>
                            <button class="password-toggle" type="button" id="passwordToggle" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary btn-lg w-100" type="submit" id="loginButton">Sign in</button>
                </form>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('passwordToggle').addEventListener('click', function() {
            var input = document.getElementById('login-password');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
        document.getElementById('login_form').addEventListener('submit', function() {
            var button = document.getElementById('loginButton');
            button.disabled = true;
            button.textContent = 'Signing in…';
        });
    </script>
</body>

</html>
