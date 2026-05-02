<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginActivity extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public $timestamps = false;

    protected $table = 'tabUser Activity Login';

    protected $fillable = [
        'user_id',
        'username',
        'login_at',
        'ip_address',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'name');
    }
}
