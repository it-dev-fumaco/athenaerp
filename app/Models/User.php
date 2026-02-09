<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     * Note: tabWarehouse Users uses wh_user (not email), frappe_userid, full_name.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'wh_user', 'frappe_userid', 'full_name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_key', 'api_secret'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $table = 'tabWarehouse Users';
    protected $primaryKey = 'name';
    protected $keyType = 'string';

    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();
        if (!$isRememberTokenAttribute)
        {
        parent::setAttribute($key, $value);
        }
    }

    public function assignedWarehouses()
    {
        return $this->hasMany(AssignedWarehouses::class, 'parent', 'frappe_userid');
    }

    public function warehouseAccess()
    {
        return $this->hasMany(WarehouseAccess::class, 'parent', 'frappe_userid');
    }

    /**
     * Get allowed warehouse IDs for this user (non-group, enabled warehouses).
     */
    public function allowedWarehouseIds(): \Illuminate\Support\Collection
    {
        $parentWarehouses = WarehouseAccess::query()
            ->where('parent', $this->frappe_userid)
            ->pluck('warehouse');

        return Warehouse::query()
            ->where('disabled', 0)
            ->whereIn('parent_warehouse', $parentWarehouses)
            ->pluck('name');
    }

    /**
     * Get allowed parent warehouses for this user.
     */
    public function allowedParentWarehouses(): \Illuminate\Support\Collection
    {
        return WarehouseAccess::query()
            ->where('parent', $this->frappe_userid)
            ->pluck('warehouse');
    }

    /**
     * Get allowed warehouse IDs for a user by frappe_userid (static).
     */
    public static function getAllowedWarehouseIdsFor(string $frappeUserid): \Illuminate\Support\Collection
    {
        $parentWarehouses = WarehouseAccess::query()
            ->where('parent', $frappeUserid)
            ->pluck('warehouse');

        return Warehouse::query()
            ->where('disabled', 0)
            ->whereIn('parent_warehouse', $parentWarehouses)
            ->pluck('name');
    }
}
