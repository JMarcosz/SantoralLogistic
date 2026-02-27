<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Enums\ShippingOrderStatus;
use App\Models\Quote;
use App\Models\ShippingOrder;
use App\Models\WarehouseReceipt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with real data.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. KPIs
        // Active Shipping Orders (In Transit)
        $inTransitCount = ShippingOrder::where('status', ShippingOrderStatus::InTransit)
            ->count();

        // Delivered Today
        $deliveredTodayCount = ShippingOrder::where('status', ShippingOrderStatus::Delivered)
            ->whereDate('updated_at', today())
            ->count();

        // Received Today (Warehouse Receipts) - Assuming 'status' handling or just creation
        // If WarehouseReceipt has a status column, filter by it. If not, just created_at.
        // Checking WarehouseReceipt model in next step if needed, assuming simple count for now.
        $receivedTodayCount = WarehouseReceipt::whereDate('created_at', today())->count();

        // Active Quotes (Pending/Draft) - Just for context, or could be "En almacén" proxy?
        // Let's stick to the UI labels: "En almacén"
        // For "En almacén", we might look at WarehouseReceipts that are NOT closed/dispatched?
        // Or Inventory items? Let's use Inventory items count for "En almacén"
        $inWarehouseCount = \App\Models\InventoryItem::where('qty', '>', 0)->count();

        $kpis = [
            [
                'label' => 'Recibidos hoy',
                'value' => (string) $receivedTodayCount,
                'tone' => 'primary',
                'icon' => 'Package',
            ],
            [
                'label' => 'En almacén',
                'value' => (string) $inWarehouseCount,
                'tone' => 'accent',
                'icon' => 'Package',
            ],
            [
                'label' => 'En ruta',
                'value' => (string) $inTransitCount,
                'tone' => 'info',
                'icon' => 'Truck',
            ],
            [
                'label' => 'Entregados',
                'value' => (string) $deliveredTodayCount,
                'tone' => 'success',
                'icon' => 'CheckCircle2',
            ],
            [
                'label' => 'Alertas',
                'value' => '0', // Placeholder until Incident model exists
                'tone' => 'warning',
                'icon' => 'AlertTriangle',
            ],
            [
                'label' => 'Actividad',
                'value' => 'OK',
                'tone' => 'neutral',
                'icon' => 'Activity',
            ],
        ];

        // 2. Alerts
        $alerts = [];

        // Expiring Quotes (Next 3 days)
        $expiringQuotes = Quote::valid()
            ->where('valid_until', '<=', now()->addDays(3))
            ->take(5)
            ->get();

        foreach ($expiringQuotes as $quote) {
            $alerts[] = [
                'title' => 'Cotización por vencer',
                'detail' => "Cotización #{$quote->quote_number} vence el {$quote->valid_until->format('d/m')}",
                'severity' => 'warning',
            ];
        }

        // 3. Work Queue (Recent Draft Quotes or Active Orders)
        $workQueue = [];

        $recentQuotes = Quote::with(['originPort', 'destinationPort'])
            ->where('status', QuoteStatus::Draft)
            ->latest()
            ->take(5)
            ->get();

        foreach ($recentQuotes as $quote) {
            $workQueue[] = [
                'code' => $quote->quote_number,
                'status' => $quote->status->label(),
                'meta' => "{$quote->lane} • " . ($quote->created_by == $user->id ? 'Mío' : 'Otros'),
            ];
        }

        return Inertia::render('dashboard', [
            'kpis' => $kpis,
            'alerts' => $alerts,
            'workQueue' => $workQueue,
        ]);
    }
}
