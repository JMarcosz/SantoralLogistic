<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DgiiExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'record_count',
        'user_id',
        'filename',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'record_count' => 'integer',
    ];

    /**
     * Get the user who exported this report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
