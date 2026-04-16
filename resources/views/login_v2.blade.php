<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Athena Inventory - Login</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/js/login.js'])
    <style>
        @font-face {
            font-family: 'Montserrat';
            src: url('/font/Montserrat/Montserrat-Bold.ttf');
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
    </style>
</head>
<body>
    <div
        id="login-app"
        data-csrf="{{ csrf_token() }}"
        data-error="{{ e($errors->first()) }}"
        data-initial-email="{{ e(old('email')) }}"
        data-login-url="/login_user"
        data-logo-url="{{ \Illuminate\Support\Facades\Storage::disk('upcloud')->url('logo/fumaco-transparent.png') }}"
    ></div>
</body>
</html>
