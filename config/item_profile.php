<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Item Profile cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Short-lived cache for Price Settings and department list used on item
    | profile pages. Reduces repeated DB queries across requests.
    |
    */
    'cache_ttl' => (int) env('ITEM_PROFILE_CACHE_TTL', 300),
];
