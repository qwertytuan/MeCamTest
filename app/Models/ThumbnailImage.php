<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Camera;
use App\Models\VideoRecording;

class ThumbnailImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id',
        'file_path',
        'file_name',
        'file_size',
        'width',
        'height',
        'is_detection_thumbnail',
        'detection_type',
        'recording_id',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'is_detection_thumbnail' => 'boolean',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function recording(): BelongsTo
    {
        return $this->belongsTo(VideoRecording::class, 'recording_id');
    }

    /**
     * Scope for detection thumbnails
     */
    public function scopeDetectionThumbnails($query)
    {
        return $query->where('is_detection_thumbnail', true);
    }

    /**
     * Scope for regular thumbnails
     */
    public function scopeRegularThumbnails($query)
    {
        return $query->where('is_detection_thumbnail', false);
    }

    /**
     * Get the full URL for the thumbnail
     */
    public function getUrlAttribute(): string
    {
        return url('api/thumbnails/' . $this->camera_id . '/' . $this->file_name);
    }
}

