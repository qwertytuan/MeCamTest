<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Models\VideoRecording;
use App\Models\ThumbnailImage;
use App\Models\CameraShareToken;

class Camera extends Model
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'description',
        'location',
        'connection_type',
        'usb_path',
        'stream_url',
        'websocket_url',
        'is_active',
        'resolution',
        'frame_rate',
        'added_by',
        'stream_username',
        'stream_password',
        // Detection settings
        'detection_type',
        'detection_enabled',
        'detection_sensitivity',
        'recording_duration',
        // Thumbnail settings
        'thumbnail_enabled',
        'thumbnail_url',
    ];

    protected $hidden = [
        'stream_username',
        'stream_password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'detection_enabled' => 'boolean',
        'thumbnail_enabled' => 'boolean',
        'detection_sensitivity' => 'integer',
        'recording_duration' => 'integer',
        'frame_rate' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'added_by', 'id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(VideoRecording::class);
    }

    public function thumbnails(): HasMany
    {
        return $this->hasMany(ThumbnailImage::class);
    }

    public function shareTokens(): HasMany
    {
        return $this->hasMany(CameraShareToken::class);
    }

    public function activeShareTokens(): HasMany
    {
        return $this->hasMany(CameraShareToken::class)->where('is_active', true)->where('expires_at', '>', now());
    }
}
