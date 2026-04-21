<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLifecycleStatusChange extends Model
{
    protected $table = 'item_lifecycle_status_changes';

    protected $fillable = [
        'item_code',
        'old_status',
        'new_status',
        'reason',
        'changed_by',
        'changed_by_name',
    ];
}

