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

    /*
    | ERP web UI base URL (browser links in emails, e.g. Stock Entry). Prefer HTTPS.
    | Falls back to the API host with /api stripped when ERP_WEB_BASE_URL is unset.
    */

    'web_base_url' => env('ERP_WEB_BASE_URL') ?: rtrim(preg_replace('#/api(?:/.*)?$#i', '', rtrim((string) env('ERP_API_BASE_URL', ''), '/')), '/'),

    'api_key' => env('ERP_API_KEY'),

    'api_secret_key' => env('ERP_API_SECRET_KEY'),

];
