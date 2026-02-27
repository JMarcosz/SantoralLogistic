<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    protected $table = 'company_settings';
    protected $fillable = [
        'name',
        'rnc',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'is_active',
        'default_payment_terms_id',
        'default_quote_terms_id',
        'default_so_terms_id',
        'default_invoice_terms_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['logo_url'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // ========== Relationships ==========

    /**
     * Default payment terms.
     */
    public function defaultPaymentTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'default_payment_terms_id');
    }

    /**
     * Default quote footer terms.
     */
    public function defaultQuoteTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'default_quote_terms_id');
    }

    /**
     * Default shipping order footer terms.
     */
    public function defaultSoTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'default_so_terms_id');
    }

    /**
     * Default invoice footer terms.
     */
    public function defaultInvoiceTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'default_invoice_terms_id');
    }

    // ========== Accessors ==========

    /**
     * Get the full logo URL.
     * Works correctly with both local (public) and S3 storage.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        $disk = config('filesystems.default', 'public');

        return Storage::disk($disk)->url($this->logo_path);
    }
    /**
     * Get the logo as a Base64 encoded string.
     * Useful for PDF generation in cloud environments where remote URLs might fail.
     */
    public function getLogoBase64(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        try {
            // Determine storage disk (usually 'public' or 's3')
            $disk = config('filesystems.default', 'public');

            if (Storage::disk($disk)->exists($this->logo_path)) {
                $content = Storage::disk($disk)->get($this->logo_path);
                $mime = Storage::disk($disk)->mimeType($this->logo_path);
                $base64 = base64_encode($content);
                return "data:{$mime};base64,{$base64}";
            }
        } catch (\Exception $e) {
            // Log error but continue without logo
            \Illuminate\Support\Facades\Log::error('Failed to encode company logo', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
