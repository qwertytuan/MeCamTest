<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CameraShareToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'camera_id',
        'created_by',
        'name',
        'watch_duration',
        'max_views',
        'current_views',
        'expires_at',
        'first_accessed_at',
        'is_active',
    ];

    protected $casts = [
        'watch_duration' => 'integer',
        'max_views' => 'integer',
        'current_views' => 'integer',
        'expires_at' => 'datetime',
        'first_accessed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the camera associated with this share token.
     */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    /**
     * Get the user who created this share token.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate a unique share token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Check if the token is valid and can be used.
     */
    public function isValid(): bool
    {
        // Token must be active
        if (!$this->is_active) {
            return false;
        }

        // Token must not be expired
        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            return false;
        }

        // Check max views if set
        if ($this->max_views && $this->current_views >= $this->max_views) {
            return false;
        }

        return true;
    }

    /**
     * Check if the watch duration has elapsed since first access.
     */
    public function hasWatchDurationExpired(): bool
    {
        if (!$this->first_accessed_at) {
            return false;
        }

        $watchEndTime = $this->first_accessed_at->addSeconds($this->watch_duration);
        return Carbon::now()->isAfter($watchEndTime);
    }

    /**
     * Get the remaining watch time in seconds.
     */
    public function getRemainingWatchTime(): int
    {
        if (!$this->first_accessed_at) {
            return $this->watch_duration;
        }

        $watchEndTime = $this->first_accessed_at->addSeconds($this->watch_duration);
        $remaining = Carbon::now()->diffInSeconds($watchEndTime, false);

        return max(0, $remaining);
    }

    /**
     * Get the watch end timestamp.
     */
    public function getWatchEndTimestamp(): ?Carbon
    {
        if (!$this->first_accessed_at) {
            return null;
        }

        return $this->first_accessed_at->addSeconds($this->watch_duration);
    }

    /**
     * Record access to this share token.
     */
    public function recordAccess(): void
    {
        if (!$this->first_accessed_at) {
            $this->first_accessed_at = Carbon::now();
        }

        $this->current_views++;
        $this->save();
    }

    /**
     * Scope to get only valid (non-expired, active) tokens.
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                     ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Deactivate this token.
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }
}
