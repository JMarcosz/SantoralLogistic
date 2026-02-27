<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewFinancials = $request->user()?->can('viewFinancials', $this->resource);

        return [
            'id' => $this->id,
            'quote_number' => $this->quote_number,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),

            // Relationships
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer'),
            'contact_id' => $this->contact_id,
            'contact' => $this->whenLoaded('contact'),

            'origin_port_id' => $this->origin_port_id,
            'origin_port' => $this->whenLoaded('originPort'),
            'destination_port_id' => $this->destination_port_id,
            'destination_port' => $this->whenLoaded('destinationPort'),
            'lane' => $this->lane,

            'transport_mode_id' => $this->transport_mode_id,
            'transport_mode' => $this->whenLoaded('transportMode'),
            'service_type_id' => $this->service_type_id,
            'service_type' => $this->whenLoaded('serviceType'),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency'),

            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('createdBy'),
            'sales_rep_id' => $this->sales_rep_id,
            'sales_rep' => $this->whenLoaded('salesRep'),

            // Expanded Fields
            'division' => $this->division,
            'transit_days' => $this->transit_days,
            'incoterms' => $this->incoterms,

            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project'),
            'issuing_company_id' => $this->issuing_company_id,
            'issuing_company' => $this->whenLoaded('issuingCompany'),
            'carrier_id' => $this->carrier_id,
            'carrier' => $this->whenLoaded('carrier'),
            'shipper_id' => $this->shipper_id,
            'shipper' => $this->whenLoaded('shipper'),
            'consignee_id' => $this->consignee_id,
            'consignee' => $this->whenLoaded('consignee'),

            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'is_expired' => $this->is_expired,
            'notes' => $this->notes,

            'payment_terms_id' => $this->payment_terms_id,
            'footer_terms_id' => $this->footer_terms_id,

            // Totals
            'total_pieces' => (int) $this->total_pieces,
            'total_weight_kg' => (float) $this->total_weight_kg,
            'total_volume_cbm' => (float) $this->total_volume_cbm,
            'chargeable_weight_kg' => (float) $this->chargeable_weight_kg,

            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->formatted_total,

            // Financials (Conditional)
            'total_cost' => $this->when($canViewFinancials, (float) $this->total_cost),
            'total_profit' => $this->when($canViewFinancials, (float) $this->total_profit),
            'profit_margin' => $this->when($canViewFinancials, (float) $this->profit_margin),

            // Lines
            'lines' => $this->whenLoaded('lines', function () use ($canViewFinancials) {
                return $this->lines->map(function ($line) use ($canViewFinancials) {
                    $resource = new QuoteLineResource($line);
                    $resource->canViewFinancials = $canViewFinancials;
                    return $resource->resolve();
                });
            }),

            // Meta
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Permissions for UI
            'can' => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
                'view_financials' => $canViewFinancials,
            ],
        ];
    }
}
