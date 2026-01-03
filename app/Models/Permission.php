<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    protected $fillable=[
        'role_id',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
        'can_manage_users',
        'can_manage_roles',
        'can_manage_permissions',
        'can_manage_cameras',
    ];

    protected $casts = [
        'can_create' => 'boolean',
        'can_read' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
        'can_manage_users' => 'boolean',
        'can_manage_roles' => 'boolean',
        'can_manage_permissions' => 'boolean',
        'can_manage_cameras' => 'boolean',
    ];
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
