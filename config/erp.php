<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ERP (Frappe) API Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and base URL for the Frappe/ERPNext REST API.
    | Authorization header format: token {api_key}:{api_secret_key}
    |
    */

    'api_base_url' => env('ERP_API_BASE_URL'),

    'api_key' => env('ERP_API_KEY'),

    'api_secret_key' => env('ERP_API_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | ERP (Frappe) Web UI Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for links that point to the ERP web UI (e.g. /app/stock-entry/..).
    | Prefer https:// and keep it consistent with your ERP deployment.
    |
    */
    'web_base_url' => env('ERP_WEB_BASE_URL', env('ERP_API_BASE_URL')),

];
