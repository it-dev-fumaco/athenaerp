<?php

return [

    /*
    |--------------------------------------------------------------------------
    | tabItem lifecycle column name
    |--------------------------------------------------------------------------
    |
    | ERPNext custom fields may appear as custom_life_cycle_status or
    | custom_lifecycle_status. Leave null to auto-detect from the database.
    |
    */

    'lifecycle_status_column' => env('PHASE_OUT_LIFECYCLE_COLUMN'),

    /*
    |--------------------------------------------------------------------------
    | Months without stock ledger activity (candidates)
    |--------------------------------------------------------------------------
    |
    | Items whose latest non-cancelled Stock Ledger posting_date is strictly
    | before today minus this many months may appear as phase-out candidates.
    |
    */

    'months_without_activity' => (int) env('PHASE_OUT_MONTHS_WITHOUT_ACTIVITY', 12),

    'tagged_per_page' => 15,

    'candidates_per_page' => 10,

];
