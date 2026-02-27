<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\ShippingOrder;
use App\Models\ShippingOrderPublicLink;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    /**
     * Display the public tracking page for a shipping order.
     */
    public function show(string $token): View
    {
        // Find the link by token
        $link = ShippingOrderPublicLink::byToken($token)
            ->active()
            ->first();

        if (!$link) {
            abort(404, 'Enlace de tracking no encontrado o expirado.');
        }

        // Load the shipping order with limited data
        $order = $link->shippingOrder()
            ->with([
                'originPort:id,code,name,country',
                'destinationPort:id,code,name,country',
                'transportMode:id,code,name',
                'serviceType:id,code,name',
                'customer:id,name',
                'milestones' => function ($query) {
                    $query->orderBy('happened_at', 'asc');
                },
            ])
            ->first();

        if (!$order) {
            abort(404, 'Orden no encontrada.');
        }

        // Get company settings for branding
        $company = CompanySetting::first();

        // Format customer name for privacy (first name + initial)
        $customerDisplayName = $this->formatCustomerName($order->customer?->name);

        return view('tracking.track', [
            'order' => $order,
            'company' => $company,
            'customerDisplayName' => $customerDisplayName,
        ]);
    }

    /**
     * Format customer name for privacy (e.g., "Juan Rodriguez" -> "Juan R.")
     */
    private function formatCustomerName(?string $fullName): string
    {
        if (!$fullName) {
            return 'Cliente';
        }

        $parts = explode(' ', trim($fullName));

        if (count($parts) === 1) {
            return $parts[0];
        }

        $firstName = $parts[0];
        $lastInitial = mb_substr($parts[count($parts) - 1], 0, 1);

        return "{$firstName} {$lastInitial}.";
    }
}
