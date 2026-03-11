<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View deliveries / picking list creation window
    |--------------------------------------------------------------------------
    |
    | Limit picking list union query to documents created within this many
    | months (improves performance). Increase if you need older records.
    |
    */
    'creation_months' => (int) env('DELIVERY_CREATION_MONTHS', 12),
];
