@extends('layouts.inventory_shell', [
    'namePage' => 'Login activity',
    'activePage' => 'login_activity',
])

@section('inventory_main')
    <style>
        #login-activity-app .login-activity-filters__row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: flex-start !important;
            gap: 12px !important;
            width: 100% !important;
        }
        #login-activity-app .login-activity-filters__user-col {
            display: flex !important;
            flex-direction: column !important;
            flex: 0 1 280px !important;
            width: 280px !important;
            max-width: 280px !important;
            gap: 8px !important;
        }
        #login-activity-app .login-activity-filters__actions {
            display: flex !important;
            flex: 0 0 auto !important;
            align-items: center !important;
            justify-content: flex-start !important;
            align-self: flex-start !important;
            gap: 8px !important;
            margin-left: 0 !important;
            width: 100% !important;
            height: 40px !important;
        }
        #login-activity-app .login-activity-filters__btn,
        #login-activity-app .login-activity-filters__btn--reset,
        #login-activity-app .login-activity-filters__btn--apply {
            display: inline-flex !important;
            flex: 0 0 auto !important;
            align-items: center !important;
            justify-content: center !important;
            align-self: auto !important;
            box-sizing: border-box !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
            height: 40px !important;
            margin: 0 !important;
            padding: 0 16px !important;
            line-height: 40px !important;
            white-space: nowrap !important;
        }
    </style>
    <div id="login-activity-app"></div>
@endsection
