<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MESFeedbackedLogs extends Model
{
    use HasFactory;
    protected $table = 'feedbacked_logs';
    protected $connection = 'mysql_mes';
    protected $primaryKey = 'feedbacked_log_id';
    public $timestamps = false;
    protected $keyType = 'string';
}
