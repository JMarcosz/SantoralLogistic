<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteLineResource extends JsonResource
{
    public ?bool $canViewFinancials = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check permission via injected prop OR policy on the parent quote
        $canViewFinancials = $this->canViewFinancials ?? $request->user()?->can('viewFinancials', $this->quote);

        return [
            'id' => $this->id,
            'product_service' => $this->whenLoaded('productService'),
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'unit_cost' => $this->when($canViewFinancials, $this->unit_cost !== null ? (float) $this->unit_cost : null),
            'discount_percent' => (float) $this->discount_percent,
            'tax_rate' => (float) $this->tax_rate,
            'line_total' => (float) $this->line_total,
            'sort_order' => (int) $this->sort_order,
            'product_service_id' => $this->product_service_id,

            // Computed fields
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'net_amount' => $this->net_amount,
            'tax_amount' => $this->tax_amount,

            // Financials
            $this->mergeWhen($canViewFinancials, [
                'unit_cost' => $this->resource->unit_cost !== null ? (float) $this->resource->unit_cost : null,
                'total_cost' => (float) $this->resource->total_cost,
                'profit' => (float) $this->resource->profit,
            ]),
        ];
    }
}
