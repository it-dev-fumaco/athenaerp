<?php

$groups = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('LOGIN_ACTIVITY_ALLOWED_USER_GROUPS', 'Director,Inventory Manager'))
)));

return [
    /*
    |--------------------------------------------------------------------------
    | User groups allowed to view login activity
    |--------------------------------------------------------------------------
    |
    | Comma-separated list in LOGIN_ACTIVITY_ALLOWED_USER_GROUPS (matches
    | tabWarehouse Users.user_group). Empty list denies all access.
    |
    */
    'allowed_user_groups' => $groups,
];
