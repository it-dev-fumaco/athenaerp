@php
    use App\Support\DomainHelper;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Athena Inventory - Login</title>
    <link rel="icon" type="image/png" href="/icon/favicon.png">
    @if(DomainHelper::isPasswordLoginHost())
        @vite(['resources/js/login.js'])
    @endif
    <style>
        @font-face {
            font-family: 'Montserrat';
            src: url({{ asset('font/Montserrat/Montserrat-Bold.ttf') }});
        }
        *:not(i):not(.fa) {
            font-family: 'Montserrat', system-ui, sans-serif !important;
        }
        * { box-sizing: border-box; }
        html { font-size: 16px; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            color: #fff;
            -webkit-user-select: none;
            user-select: none;
            overflow-x: hidden;
        }
        #login-app {
            flex: 1;
            display: flex;
            min-height: 100vh;
        }

        .login-page {
            flex: 1;
            display: flex;
            min-height: 100vh;
            position: relative;
            background: linear-gradient(180deg, #0d3a6e 0%, #0a2d52 50%, #0d3a6e 100%);
        }
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }
        .login-page::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, transparent 15%, transparent 85%, rgba(255,255,255,0.06) 100%);
            pointer-events: none;
        }
        .branding {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 4rem;
            position: relative;
            z-index: 1;
        }
        .branding-logo {
            max-width: 350px;
            height: auto;
            margin-bottom: 2rem;
            display: block;
        }
        .branding-title {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.02em;
        }
        .branding-title-with-underline { display: inline-block; }
        .branding-title-with-underline .branding-underline {
            display: block;
            width: 100%;
            height: 4px;
            background: #f5c542;
            border-radius: 2px;
            margin-top: 0.25rem;
        }
        .form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        .form-card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            background: rgba(55, 65, 81, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .form-card h2 {
            margin: 0 0 0.25rem 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .form-card .subtitle {
            margin: 0 0 1.75rem 0;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        .form-errors {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(206, 30, 9, 0.2);
            border-radius: 10px;
            font-size: 0.9rem;
            color: #ffb3a7;
        }
        .btn-microsoft {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            padding: 0.9rem 1.1rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            color: #111827;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }
        .btn-microsoft:hover { box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3); }
        .ms-icon {
            display: grid;
            grid-template-columns: repeat(2, 10px);
            grid-template-rows: repeat(2, 10px);
            gap: 2px;
        }
        .sq { width: 10px; height: 10px; display: block; }
        .sq.red { background: #f25022; }
        .sq.green { background: #7fba00; }
        .sq.blue { background: #00a4ef; }
        .sq.yellow { background: #ffb900; }
        @media (max-width: 900px) {
            .login-page { flex-direction: column; }
            .branding {
                padding: 2rem 2rem 1.5rem;
                text-align: center;
                align-items: center;
            }
            .form-section { padding: 1.5rem 1.5rem 3rem; }
        }
    </style>
</head>
<body>
    @if(request()->getHost() === config('auth.sso_domain'))
        <div class="login-page">
            <section class="branding">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('upcloud')->url('logo/fumaco-transparent.png') }}" alt="Fumaco" class="branding-logo" />
                <h1 class="branding-title">
                    <span class="branding-title-with-underline">
                        <span>Athena</span>
                        <span class="branding-underline" aria-hidden="true"></span>
                    </span>
                    Inventory
                </h1>
            </section>
            <section class="form-section">
                <div class="form-card">
                    <h2>Welcome Back!</h2>
                    <p class="subtitle">Sign in with your Microsoft account</p>
                    @if($errors->any())
                        <div class="form-errors" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <a class="btn-microsoft" href="{{ route('auth.microsoft.redirect') }}">
                        <span class="ms-icon" aria-hidden="true">
                            <span class="sq red"></span>
                            <span class="sq green"></span>
                            <span class="sq blue"></span>
                            <span class="sq yellow"></span>
                        </span>
                        Sign in with Microsoft
                    </a>
                </div>
            </section>
        </div>
    @else
        <div
            id="login-app"
            data-csrf="{{ csrf_token() }}"
            data-error="{{ e($errors->first()) }}"
            data-initial-email="{{ e(old('email')) }}"
            data-login-url="{{ url('/login_user') }}"
            data-microsoft-login-url="{{ route('auth.microsoft.redirect') }}"
            data-show-microsoft="0"
            data-logo-url="{{ \Illuminate\Support\Facades\Storage::disk('upcloud')->url('logo/fumaco-transparent.png') }}"
        ></div>
    @endif
</body>
</html>
