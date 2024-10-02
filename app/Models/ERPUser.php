<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ERPUser extends Model
{
    use HasFactory;

    protected $table = 'tabUser';

    public function social(){
        return $this->hasOne(UserSocialLogin::class, 'parent', 'name');
    }

    public function wh_user(){
        return $this->hasOne(User::class, 'wh_user', 'name');
    }
}
