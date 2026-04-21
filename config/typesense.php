<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Typesense for product search
    |--------------------------------------------------------------------------
    |
    | When false, search uses the legacy SQL implementation. You can still
    | run indexing commands while disabled to prepare the index before cutover.
    |
    */

    'enabled' => filter_var(env('TYPESENSE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Collection name
    |--------------------------------------------------------------------------
    */

    'collection' => env('TYPESENSE_COLLECTION', 'items'),

    /*
    |--------------------------------------------------------------------------
    | Assigned-filter safety limit
    |--------------------------------------------------------------------------
    |
    | When "assigned_to_me" resolves to more than this many item codes, search
    | falls back to SQL to avoid oversized Typesense filter clauses.
    |
    */

    'assigned_filter_max_ids' => (int) env('TYPESENSE_ASSIGNED_FILTER_MAX_IDS', 500),

    /*
    |--------------------------------------------------------------------------
    | PHP Typesense client
    |--------------------------------------------------------------------------
    */

    'client' => [
        'api_key' => env('TYPESENSE_API_KEY', 'devtypesense'),
        'nodes' => [
            [
                'host' => env('TYPESENSE_HOST', '127.0.0.1'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
        ],
        'connection_timeout_seconds' => (int) env('TYPESENSE_CONNECTION_TIMEOUT', 5),
    ],

    'reindex_chunk_size' => (int) env('TYPESENSE_REINDEX_CHUNK', 150),

];
