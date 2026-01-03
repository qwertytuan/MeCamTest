<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;
    protected static function booted(): void
    {
        static::deleting(static function (User $user) {
            if ($user->isForceDeleting()) {
                // Permanently delete products if user is force deleted
                $user->products()->forceDelete();
            } else {
                // Soft delete products if user is soft deleted
                $user->products()->delete();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function camera(): HasMany
    {
        return $this->hasMany(Camera::class, 'added_by', 'id');
    }

    public function cameraAccess(): HasMany
    {
        return $this->hasMany(UserCameraAccess::class, 'user_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function getPermissions()
    {
        return $this->roles()->with('permissions')->get()->pluck('permissions')->flatten();
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getPermissions();

        foreach ($permissions as $perm) {
            if (isset($perm->$permission) && $perm->$permission) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin || $this->hasRole('Admin');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
