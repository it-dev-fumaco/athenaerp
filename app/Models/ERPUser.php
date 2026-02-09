<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ERPUser extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $primaryKey = 'name';
    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = 'tabUser';

    public function social(){
        return $this->hasOne(UserSocialLogin::class, 'parent', 'name');
    }

    public function whUser()
    {
        return $this->hasOne(User::class, 'wh_user', 'name');
    }
}
