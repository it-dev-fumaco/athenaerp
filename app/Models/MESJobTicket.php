<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MESJobTicket extends Model
{
    use HasFactory;

    protected $table = 'job_ticket';

    protected $connection = 'mysql_mes';

    protected $primaryKey = 'job_ticket_id';

    public $timestamps = false;

    protected $keyType = 'string';
}
