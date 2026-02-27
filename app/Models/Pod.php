<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Pod extends Model
{
    use HasFactory;

    protected $fillable = [
        'podable_type',
        'podable_id',
        'happened_at',
        'latitude',
        'longitude',
        'image_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clean up image file when POD is deleted
        static::deleting(function (Pod $pod) {
            if ($pod->image_path) {
                Storage::disk(config('filesystems.default', 'local'))->delete($pod->image_path);
            }
        });
    }

    /**
     * Get the parent podable model (PickupOrder or DeliveryOrder).
     */
    public function podable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this POD.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the image URL if an image exists.
     * Works correctly with both local/public and S3 storage.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        $disk = config('filesystems.default', 'local');

        // For S3 or public disk, we can use the URL directly
        if ($disk === 's3' || $disk === 'public') {
            return Storage::disk($disk)->url($this->image_path);
        }

        // For local/private storage, the image will be served via a controller route
        return null;
    }

    /**
     * Check if this POD has geolocation data.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get formatted location string.
     */
    public function getFormattedLocationAttribute(): ?string
    {
        if (!$this->hasLocation()) {
            return null;
        }

        return "{$this->latitude}, {$this->longitude}";
    }
}
