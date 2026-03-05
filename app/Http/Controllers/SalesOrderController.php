<?php

namespace App\Http\Controllers;

use App\Enums\SalesOrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\ProductService;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function __construct(
        protected SalesOrderService $salesOrderService
    ) {}

    /**
     * Display a listing of sales orders.
     */
    public function index(Request $request): Response
    {
        $query = SalesOrder::with(['customer:id,name,code', 'currency:id,code,symbol', 'createdBy:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->ofStatus($request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->forCustomer($request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('sales-orders/index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'customer_id', 'search']),
            'statuses' => collect(SalesOrderStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Display the specified sales order.
     */
    public function show(SalesOrder $salesOrder): Response
    {
        $salesOrder->load([
            'customer:id,name,code,rnc',
            'contact:id,name,email,phone',
            'currency:id,code,symbol',
            'quote:id,quote_number',
            'lines.productService:id,code,name,type,uom',
            'inventoryReservations.inventoryItem',
            'invoices:id,number,total_amount,status,issue_date,sales_order_id',
            'createdBy:id,name',
        ]);

        return Inertia::render('sales-orders/show', [
            'order' => $salesOrder,
            'statuses' => collect(SalesOrderStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Show the form for creating a new sales order.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('sales-orders/create', [
            'customers' => Customer::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
            'currencies' => Currency::select('id', 'code', 'symbol', 'name')->orderBy('code')->get(),
            'productsServices' => ProductService::where('is_active', true)
                ->select('id', 'code', 'name', 'type', 'default_unit_price', 'taxable')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created sales order.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'currency_id' => 'required|exists:currencies,id',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.product_service_id' => 'required|exists:products_services,id',
            'lines.*.line_type' => 'required|in:product,service',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $order = SalesOrder::create([
            'customer_id' => $validated['customer_id'],
            'contact_id' => $validated['contact_id'] ?? null,
            'currency_id' => $validated['currency_id'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        foreach ($validated['lines'] as $index => $lineData) {
            $qty = (float) $lineData['quantity'];
            $price = (float) $lineData['unit_price'];
            $discount = (float) ($lineData['discount_percent'] ?? 0);
            $lineTotal = $qty * $price * (1 - $discount / 100);

            $order->lines()->create([
                'product_service_id' => $lineData['product_service_id'],
                'line_type' => $lineData['line_type'],
                'description' => $lineData['description'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'unit_cost' => $lineData['unit_cost'] ?? 0,
                'discount_percent' => $discount,
                'tax_rate' => $lineData['tax_rate'] ?? 0,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ]);
        }

        $order->recalculateTotals();

        return redirect()
            ->route('sales-orders.show', $order)
            ->with('success', "Orden de pedido {$order->order_number} creada exitosamente.");
    }

    /**
     * Confirm the sales order (draft → confirmed). Auto-reserves inventory.
     */
    public function confirm(SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $result = $this->salesOrderService->confirm($salesOrder);

            $message = "Orden {$salesOrder->order_number} confirmada exitosamente.";
            if (!empty($result['warnings'])) {
                $message .= ' Advertencias: ' . implode('; ', $result['warnings']);
            }

            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with(!empty($result['warnings']) ? 'warning' : 'success', $message);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Start delivery for the sales order (confirmed → delivering).
     */
    public function startDelivery(SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->startDelivery($salesOrder);

            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('success', "Entrega iniciada para {$salesOrder->order_number}.");
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark the order as delivered (delivering → delivered). Deducts inventory.
     */
    public function markDelivered(SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->markDelivered($salesOrder);

            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('success', "Orden {$salesOrder->order_number} entregada. Inventario descontado.");
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the sales order.
     */
    public function cancel(SalesOrder $salesOrder): RedirectResponse
    {
        if (!$salesOrder->canTransitionTo(SalesOrderStatus::Cancelled)) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', 'No se puede cancelar esta orden en su estado actual.');
        }

        $salesOrder->status = SalesOrderStatus::Cancelled;
        $salesOrder->save();

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('success', "Orden {$salesOrder->order_number} cancelada.");
    }
}
