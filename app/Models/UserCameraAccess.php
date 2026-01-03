<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCameraAccess extends Model
{
    protected $fillable = [
        'user_id',
        'camera_id',
        'access_starts_at',
        'access_expires_at',
        'can_view',
        'can_control',
        'can_configure',
        'granted_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'access_starts_at' => 'datetime',
            'access_expires_at' => 'datetime',
        ];
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class, 'camera_id', 'id');
    }

    public function grantedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_admin_id', 'id');
    }
}
