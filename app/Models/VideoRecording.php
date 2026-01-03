<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Camera;
use App\Models\ThumbnailImage;

class VideoRecording extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id',
        'file_path',
        'file_name',
        'file_size',
        'duration',
        'detection_type',
        'triggered_at',
        'resolution',
        'fps',
        'codec',
        'status',
        'error_message',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'file_size' => 'integer',
        'duration' => 'integer',
        'fps' => 'integer',
    ];

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function thumbnails(): HasMany
    {
        return $this->hasMany(ThumbnailImage::class, 'recording_id');
    }

    /**
     * Scope for completed recordings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    /**
     * Scope for recordings by detection type
     */
    public function scopeByDetectionType($query, $type)
    {
        return $query->where('detection_type', $type);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get human-readable duration
     */
    public function getHumanDurationAttribute(): string
    {
        $seconds = $this->duration;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }
}

