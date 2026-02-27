<?php

namespace App\Models;

use App\Enums\ChargeBasis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'origin_port_id',
        'destination_port_id',
        'transport_mode_id',
        'service_type_id',
        'currency_id',
        'charge_basis',
        'base_amount',
        'min_amount',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'charge_basis' => ChargeBasis::class,
        'base_amount' => 'decimal:4',
        'min_amount' => 'decimal:4',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function originPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'origin_port_id');
    }

    public function destinationPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'destination_port_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    // ========== Scopes ==========

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyValid(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $today);
            });
    }

    public function scopeForLane(Builder $query, int $originId, int $destinationId): Builder
    {
        return $query->where('origin_port_id', $originId)
            ->where('destination_port_id', $destinationId);
    }

    public function scopeForMode(Builder $query, int $modeId): Builder
    {
        return $query->where('transport_mode_id', $modeId);
    }

    public function scopeForServiceType(Builder $query, int $serviceTypeId): Builder
    {
        return $query->where('service_type_id', $serviceTypeId);
    }

    // ========== Accessors ==========

    public function getLaneAttribute(): string
    {
        return "{$this->originPort?->code} → {$this->destinationPort?->code}";
    }

    public function getIsCurrentlyValidAttribute(): bool
    {
        $today = now()->toDateString();

        if ($this->valid_from > $today) {
            return false;
        }

        if ($this->valid_to && $this->valid_to < $today) {
            return false;
        }

        return true;
    }

    // ========== Business Logic ==========

    /**
     * Check if a rate overlaps with another for the same lane/mode/service.
     */
    public function hasOverlap(): bool
    {
        return static::where('origin_port_id', $this->origin_port_id)
            ->where('destination_port_id', $this->destination_port_id)
            ->where('transport_mode_id', $this->transport_mode_id)
            ->where('service_type_id', $this->service_type_id)
            ->where('id', '!=', $this->id ?? 0)
            ->where(function ($query) {
                $query->where(function ($q) {
                    // New rate starts within existing rate's range
                    $q->where('valid_from', '<=', $this->valid_from)
                        ->where(function ($sq) {
                            $sq->whereNull('valid_to')
                                ->orWhere('valid_to', '>=', $this->valid_from);
                        });
                })->orWhere(function ($q) {
                    // New rate ends within existing rate's range
                    if ($this->valid_to) {
                        $q->where('valid_from', '<=', $this->valid_to);
                    }
                });
            })
            ->exists();
    }

    /**
     * Find an active, valid rate for a given lane/mode/service.
     */
    public static function findForLane(
        int $originPortId,
        int $destinationPortId,
        int $transportModeId,
        int $serviceTypeId
    ): ?static {
        return static::query()
            ->active()
            ->currentlyValid()
            ->forLane($originPortId, $destinationPortId)
            ->forMode($transportModeId)
            ->forServiceType($serviceTypeId)
            ->first();
    }
}
