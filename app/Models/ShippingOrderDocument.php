<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ShippingOrderDocument extends Model
{
    protected $fillable = [
        'shipping_order_id',
        'type',
        'original_name',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'size' => 'integer',
    ];

    // ========== Relationships ==========

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ========== Scopes ==========

    public function scopeOfType($query, string|DocumentType $type)
    {
        return $query->where('type', $type instanceof DocumentType ? $type->value : $type);
    }

    // ========== Accessors ==========

    /**
     * Get the full URL for download.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->getDisk())->url($this->path);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get disk for storage.
     */
    protected function getDisk(): string
    {
        return config('filesystems.default', 'local');
    }

    // ========== Methods ==========

    /**
     * Delete the file from storage when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (ShippingOrderDocument $document) {
            Storage::disk($document->getDisk())->delete($document->path);
        });
    }
}
