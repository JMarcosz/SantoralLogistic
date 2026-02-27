<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\MilestoneCode;
use App\Enums\ShippingOrderStatus;
use App\Exceptions\InvalidShippingOrderStateTransitionException;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\ShippingOrder;
use App\Models\ShippingOrderDocument;
use App\Models\ShippingOrderPublicLink;
use App\Services\ShippingOrderPdfService;
use App\Services\ShippingOrderShipmentService;
use App\Services\ShippingOrderStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingOrderController extends Controller
{
    public function __construct(
        protected ShippingOrderStateMachine $stateMachine,
        protected ShippingOrderPdfService $pdfService,
        protected ShippingOrderShipmentService $shipmentService,
    ) {}


    /**
     * Display a listing of shipping orders.
     */
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', ShippingOrder::class);

        $query = ShippingOrder::with([
            'customer:id,name,code',
            'originPort:id,code,name',
            'destinationPort:id,code,name',
            'transportMode:id,code,name',
            'serviceType:id,code,name',
            'currency:id,code,symbol',
            'quote:id,quote_number',
        ]);

        // Filter by status (supports single or multiple values)
        if ($request->filled('status')) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status', $statuses);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by origin port
        if ($request->filled('origin_port_id')) {
            $query->where('origin_port_id', $request->origin_port_id);
        }

        // Filter by destination port
        if ($request->filled('destination_port_id')) {
            $query->where('destination_port_id', $request->destination_port_id);
        }

        // Filter by date range (created_at)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number, quote number, or customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('quote', fn($qq) => $qq->where('quote_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn($qq) => $qq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter active only by default
        if (!$request->filled('show_inactive')) {
            $query->active();
        }

        // Order by most recent
        $query->orderBy('created_at', 'desc');

        // Paginate
        $orders = $query->paginate(15)->withQueryString();

        // Get filter options - customers
        $customers = Customer::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get filter options - ports
        $ports = \App\Models\Port::select('id', 'code', 'name', 'country')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return Inertia::render('shipping-orders/index', [
            'orders' => $orders,
            'customers' => $customers,
            'ports' => $ports,
            'statuses' => collect(ShippingOrderStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'filters' => [
                'status' => $request->status,
                'customer_id' => $request->customer_id,
                'origin_port_id' => $request->origin_port_id,
                'destination_port_id' => $request->destination_port_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'search' => $request->search,
            ],
            'can' => [
                'create' => $request->user()->can('create', ShippingOrder::class),
            ],
        ]);
    }

    /**
     * Display the specified shipping order.
     */
    public function show(Request $request, ShippingOrder $shippingOrder): InertiaResponse
    {
        $this->authorize('view', $shippingOrder);

        // Load all relationships including milestones, public link, and charges
        $shippingOrder->load([
            'customer:id,name,code,tax_id,billing_address,phone',
            'contact:id,name,email,phone',
            'shipper:id,name,code',
            'consignee:id,name,code',
            'originPort:id,code,name,country',
            'destinationPort:id,code,name,country',
            'transportMode:id,code,name',
            'serviceType:id,code,name',
            'currency:id,code,symbol',
            'quote:id,quote_number,status,total_amount,created_at',
            'createdBy:id,name',
            'milestones.createdBy:id,name',
            'documents.uploadedBy:id,name',
            'publicLink',
            'footerTerms:id,code,name',
            'oceanShipment',
            'airShipment',
            'pickupOrders:id,shipping_order_id,status,scheduled_date,driver_id',
            'pickupOrders.driver:id,name',
            'deliveryOrders:id,shipping_order_id,status,scheduled_date,driver_id',
            'deliveryOrders.driver:id,name',
            'preInvoices' => fn($q) => $q->where('status', '!=', 'cancelled')->latest(),
            'charges' => fn($q) => $q->orderBy('sort_order'),
        ]);

        // Get company settings for display
        $company = CompanySetting::first();

        $user = $request->user();

        // Get milestone code options for the form
        $milestoneCodes = MilestoneCode::options();

        // Get document type options for the form
        $documentTypes = DocumentType::options();

        // Get currencies for charges form
        $currencies = \App\Models\Currency::select('id', 'code', 'symbol', 'name')->orderBy('code')->get();

        // Charge form options
        $chargeTypes = [
            ['value' => 'freight', 'label' => 'Flete'],
            ['value' => 'surcharge', 'label' => 'Recargo'],
            ['value' => 'tax', 'label' => 'Impuesto'],
            ['value' => 'other', 'label' => 'Otro'],
        ];

        $chargeBases = [
            ['value' => 'flat', 'label' => 'Monto Fijo'],
            ['value' => 'per_kg', 'label' => 'Por Kg'],
            ['value' => 'per_cbm', 'label' => 'Por CBM'],
            ['value' => 'per_shipment', 'label' => 'Por Envío'],
        ];

        // Get products/services catalog for charge selection
        $productsServices = \App\Models\ProductService::with('defaultCurrency:id,code,symbol')
            ->where('is_active', true)
            ->select('id', 'code', 'name', 'description', 'type', 'uom', 'default_currency_id', 'default_unit_price')
            ->orderBy('code')
            ->get();

        // Get public tracking URL if enabled
        $publicTrackingUrl = null;
        $publicTrackingEnabled = false;
        if ($shippingOrder->publicLink && $shippingOrder->publicLink->isValid()) {
            $publicTrackingEnabled = true;
            $publicTrackingUrl = $shippingOrder->publicLink->public_url;
        }

        // Check if a pre-invoice can be generated
        $validStatusesForPreInvoice = [
            ShippingOrderStatus::Arrived,
            ShippingOrderStatus::Delivered,
            ShippingOrderStatus::Closed,
        ];
        $hasActivePreInvoice = $shippingOrder->preInvoices->isNotEmpty();
        $canGeneratePreInvoice = $user->can('create', \App\Models\PreInvoice::class)
            && in_array($shippingOrder->status, $validStatusesForPreInvoice)
            && !$hasActivePreInvoice;

        // Get the active pre-invoice if exists
        $activePreInvoice = $shippingOrder->preInvoices->first();

        return Inertia::render('shipping-orders/show', [
            'order' => $shippingOrder,
            'company' => $company,
            'milestoneCodes' => $milestoneCodes,
            'documentTypes' => $documentTypes,
            'currencies' => $currencies,
            'chargeTypes' => $chargeTypes,
            'chargeBases' => $chargeBases,
            'productsServices' => $productsServices,
            'publicTrackingUrl' => $publicTrackingUrl,
            'publicTrackingEnabled' => $publicTrackingEnabled,
            'activePreInvoice' => $activePreInvoice ? [
                'id' => $activePreInvoice->id,
                'number' => $activePreInvoice->number,
                'status' => $activePreInvoice->status,
            ] : null,
            'hasInventoryReservations' => $shippingOrder->inventoryReservations()->exists(),
            'reservationsCount' => $shippingOrder->inventoryReservations()->count(),
            'inventoryReservations' => $shippingOrder->inventoryReservations()
                ->with(['inventoryItem.warehouse'])
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'sku' => $r->inventoryItem->item_code,
                    'qty_reserved' => $r->qty_reserved,
                    'warehouse' => $r->inventoryItem->warehouse?->name ?? '-',
                ]),
            'warehouses' => \App\Models\Warehouse::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'can' => [
                'update' => $user->can('update', $shippingOrder),
                'delete' => $user->can('delete', $shippingOrder),
                'changeStatus' => $user->can('changeStatus', $shippingOrder),
                'book' => $user->can('changeStatus', $shippingOrder) && $shippingOrder->status === ShippingOrderStatus::Draft,
                'startTransit' => $user->can('changeStatus', $shippingOrder) && $shippingOrder->status === ShippingOrderStatus::Booked,
                'arrive' => $user->can('changeStatus', $shippingOrder) && $shippingOrder->status === ShippingOrderStatus::InTransit,
                'deliver' => $user->can('changeStatus', $shippingOrder) && $shippingOrder->status === ShippingOrderStatus::Arrived,
                'close' => $user->can('changeStatus', $shippingOrder) && $shippingOrder->status === ShippingOrderStatus::Delivered,
                'cancel' => $user->can('changeStatus', $shippingOrder) && !$shippingOrder->status->isTerminal() && $shippingOrder->status !== ShippingOrderStatus::InTransit,
                'addMilestone' => $user->can('update', $shippingOrder) && !$shippingOrder->status->isTerminal(),
                'uploadDocument' => $user->can('update', $shippingOrder),
                'deleteDocument' => $user->can('update', $shippingOrder),
                'managePublicTracking' => $user->can('managePublicTracking', $shippingOrder),
                'generatePreInvoice' => $canGeneratePreInvoice,
                'manageCharges' => $user->can('manageCharges', $shippingOrder),
                'createWarehouseOrder' => $user->can('create', \App\Models\WarehouseOrder::class),
                'reserveInventory' => $user->can('update', $shippingOrder) && !$shippingOrder->status->isTerminal(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new shipping order.
     */
    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', ShippingOrder::class);

        // Load catalogs for the form
        $customers = Customer::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $ports = \App\Models\Port::select('id', 'code', 'name', 'country')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $transportModes = \App\Models\TransportMode::select('id', 'code', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $serviceTypes = \App\Models\ServiceType::select('id', 'code', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = \App\Models\Currency::select('id', 'code', 'symbol', 'name')
            ->orderBy('code')
            ->get();

        // Get default currency
        $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();

        // Get SO footer terms for selection
        $footerTerms = \App\Models\Term::getOptionsForType(\App\Models\Term::TYPE_SO_FOOTER);

        // Get company default SO terms
        $companySettings = CompanySetting::first();

        return Inertia::render('shipping-orders/create', [
            'customers' => $customers,
            'ports' => $ports,
            'transportModes' => $transportModes,
            'serviceTypes' => $serviceTypes,
            'currencies' => $currencies,
            'defaultCurrencyId' => $defaultCurrency?->id,
            'footerTerms' => $footerTerms,
            'defaultFooterTermsId' => $companySettings?->default_so_terms_id,
        ]);
    }

    /**
     * Store a newly created shipping order.
     */
    public function store(\App\Http\Requests\StoreShippingOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Extract nested shipment data before creating order
        $oceanShipmentData = $validated['ocean_shipment'] ?? null;
        $airShipmentData = $validated['air_shipment'] ?? null;
        unset($validated['ocean_shipment'], $validated['air_shipment']);

        // Create the shipping order (order_number is auto-generated in boot)
        $shippingOrder = ShippingOrder::create([
            ...$validated,
            'status' => ShippingOrderStatus::Draft->value,
            'quote_id' => null,
            'created_by' => auth()->id(),
        ]);

        // Resolve and assign terms (uses explicit selection or falls back to company default)
        $termsResolver = app(\App\Services\TermsResolverService::class);
        $termsResolver->resolveForShippingOrder(
            $shippingOrder,
            $validated['footer_terms_id'] ?? null
        );
        $shippingOrder->save();

        // Create ocean shipment details if nested ocean_shipment is provided
        if ($oceanShipmentData && is_array($oceanShipmentData)) {
            $oceanData = array_filter([
                'mbl_number' => $oceanShipmentData['mbl_number'] ?? null,
                'hbl_number' => $oceanShipmentData['hbl_number'] ?? null,
                'carrier_name' => $oceanShipmentData['carrier_name'] ?? null,
                'vessel_name' => $oceanShipmentData['vessel_name'] ?? null,
                'voyage_number' => $oceanShipmentData['voyage_number'] ?? null,
                'container_details' => $oceanShipmentData['container_details'] ?? null,
            ]);
            if (!empty($oceanData)) {
                try {
                    $this->shipmentService->upsertOceanDetails($shippingOrder, $oceanData);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Log but don't fail - transport mode might not match
                }
            }
        }

        // Create air shipment details if nested air_shipment is provided
        if ($airShipmentData && is_array($airShipmentData)) {
            $airData = array_filter([
                'mawb_number' => $airShipmentData['mawb_number'] ?? null,
                'hawb_number' => $airShipmentData['hawb_number'] ?? null,
                'airline_name' => $airShipmentData['airline_name'] ?? null,
                'flight_number' => $airShipmentData['flight_number'] ?? null,
            ]);
            if (!empty($airData)) {
                try {
                    $this->shipmentService->upsertAirDetails($shippingOrder, $airData);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Log but don't fail - transport mode might not match
                }
            }
        }

        return redirect()
            ->route('shipping-orders.show', $shippingOrder)
            ->with('success', "Orden {$shippingOrder->order_number} creada exitosamente.");
    }

    /**
     * Show the form for editing the specified shipping order.
     */
    public function edit(ShippingOrder $shippingOrder): InertiaResponse
    {
        $this->authorize('update', $shippingOrder);

        // Load the shipping order with relationships needed for the form
        $shippingOrder->load([
            'customer',
            'originPort',
            'destinationPort',
            'transportMode',
            'serviceType',
            'currency',
            'oceanShipment',
            'airShipment',
            'shipper',
            'consignee',
        ]);

        // Load catalogs for the form (same as create)
        $customers = Customer::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $ports = \App\Models\Port::select('id', 'code', 'name', 'country')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $transportModes = \App\Models\TransportMode::select('id', 'code', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $serviceTypes = \App\Models\ServiceType::select('id', 'code', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = \App\Models\Currency::select('id', 'code', 'symbol', 'name')
            ->orderBy('code')
            ->get();

        // Get SO footer terms for selection
        $footerTerms = \App\Models\Term::getOptionsForType(\App\Models\Term::TYPE_SO_FOOTER);

        return Inertia::render('shipping-orders/edit', [
            'shippingOrder' => $shippingOrder,
            'customers' => $customers,
            'ports' => $ports,
            'transportModes' => $transportModes,
            'serviceTypes' => $serviceTypes,
            'currencies' => $currencies,
            'footerTerms' => $footerTerms,
        ]);
    }

    /**
     * Update the specified shipping order in storage.
     */
    public function update(\App\Http\Requests\UpdateShippingOrderRequest $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('update', $shippingOrder);

        $validated = $request->validated();

        // Extract nested shipment data before updating order
        $oceanShipmentData = $validated['ocean_shipment'] ?? null;
        $airShipmentData = $validated['air_shipment'] ?? null;
        unset($validated['ocean_shipment'], $validated['air_shipment']);

        // Update the shipping order
        $shippingOrder->update($validated);

        // Resolve and assign terms if changed
        if (isset($validated['footer_terms_id'])) {
            $termsResolver = app(\App\Services\TermsResolverService::class);
            $termsResolver->resolveForShippingOrder(
                $shippingOrder,
                $validated['footer_terms_id']
            );
            $shippingOrder->save();
        }

        // Update ocean shipment details
        if ($oceanShipmentData && is_array($oceanShipmentData)) {
            try {
                $this->shipmentService->upsertOceanDetails($shippingOrder, $oceanShipmentData);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Log but don't fail
            }
        }

        // Update air shipment details
        if ($airShipmentData && is_array($airShipmentData)) {
            try {
                $this->shipmentService->upsertAirDetails($shippingOrder, $airShipmentData);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Log but don't fail
            }
        }

        return redirect()
            ->route('shipping-orders.show', $shippingOrder)
            ->with('success', "Orden {$shippingOrder->order_number} actualizada exitosamente.");
    }

    /**
     * Book the shipping order.
     */
    public function book(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $shippingOrder);

        try {
            $this->stateMachine->book($shippingOrder);

            return redirect()
                ->route('shipping-orders.show', $shippingOrder)
                ->with('success', "Orden {$shippingOrder->order_number} reservada exitosamente.");
        } catch (InvalidShippingOrderStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Start transit for the shipping order.
     */
    public function startTransit(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $shippingOrder);

        try {
            $this->stateMachine->startTransit($shippingOrder);

            return redirect()
                ->route('shipping-orders.show', $shippingOrder)
                ->with('success', "Orden {$shippingOrder->order_number} en tránsito.");
        } catch (InvalidShippingOrderStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark the shipping order as arrived.
     */
    public function arrive(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $shippingOrder);

        try {
            $this->stateMachine->arrive($shippingOrder);

            return redirect()
                ->route('shipping-orders.show', $shippingOrder)
                ->with('success', "Orden {$shippingOrder->order_number} marcada como llegada.");
        } catch (InvalidShippingOrderStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark the shipping order as delivered.
     */
    public function deliver(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $shippingOrder);

        try {
            $this->stateMachine->deliver($shippingOrder);

            return redirect()
                ->route('shipping-orders.show', $shippingOrder)
                ->with('success', "Orden {$shippingOrder->order_number} entregada.");
        } catch (InvalidShippingOrderStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Close the shipping order.
     */
    public function close(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $shippingOrder);

        try {
            $this->stateMachine->close($shippingOrder);

            return redirect()
                ->route('shipping-orders.show', $shippingOrder)
                ->with('success', "Orden {$shippingOrder->order_number} cerrada.");
        } catch (InvalidShippingOrderStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the shipping order.
     */
    public function cancel(Request $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $shippingOrder);

        try {
            $reason = $request->input('reason');
            $this->stateMachine->cancel($shippingOrder, $reason);

            return redirect()
                ->route('shipping-orders.index')
                ->with('success', "Orden {$shippingOrder->order_number} cancelada.");
        } catch (InvalidShippingOrderStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Store a new milestone for the shipping order.
     */
    public function storeMilestone(Request $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('update', $shippingOrder);

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'happened_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Get label from enum if not provided
        $code = MilestoneCode::tryFrom($validated['code']);
        $label = $validated['label'] ?? ($code ? $code->label() : $validated['code']);

        $shippingOrder->addMilestone(
            code: $validated['code'],
            label: $label,
            happenedAt: new \DateTime($validated['happened_at']),
            location: $validated['location'] ?? null,
            remarks: $validated['remarks'] ?? null,
        );

        return redirect()
            ->back()
            ->with('success', 'Milestone registrado exitosamente.');
    }

    /**
     * Store a new document for the shipping order.
     */
    public function storeDocument(Request $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('update', $shippingOrder);

        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', array_column(DocumentType::cases(), 'value')),
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        ]);

        $file = $request->file('file');
        $path = $file->store(
            "shipping-orders/{$shippingOrder->id}/documents",
            config('filesystems.default', 'local')
        );

        $shippingOrder->documents()->create([
            'type' => $validated['type'],
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Documento subido exitosamente.');
    }

    /**
     * Download a document.
     */
    public function downloadDocument(ShippingOrder $shippingOrder, ShippingOrderDocument $document): StreamedResponse
    {
        $this->authorize('view', $shippingOrder);

        // Ensure document belongs to this order
        if ($document->shipping_order_id !== $shippingOrder->id) {
            abort(404);
        }

        $disk = config('filesystems.default', 'local');

        if (!Storage::disk($disk)->exists($document->path)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::disk($disk)->download(
            $document->path,
            $document->original_name
        );
    }

    /**
     * Delete a document.
     */
    public function destroyDocument(ShippingOrder $shippingOrder, ShippingOrderDocument $document): RedirectResponse
    {
        $this->authorize('update', $shippingOrder);

        // Ensure document belongs to this order
        if ($document->shipping_order_id !== $shippingOrder->id) {
            abort(404);
        }

        $document->delete();

        return redirect()
            ->back()
            ->with('success', 'Documento eliminado.');
    }

    /**
     * Generate and stream PDF for the shipping order.
     */
    public function print(ShippingOrder $shippingOrder): Response
    {
        $this->authorize('view', $shippingOrder);

        return $this->pdfService->stream($shippingOrder);
    }

    /**
     * Enable public tracking for the shipping order.
     */
    public function enablePublicTracking(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('managePublicTracking', $shippingOrder);

        // Check if a link already exists
        $link = $shippingOrder->publicLink;

        if ($link) {
            // Reactivate existing link
            $link->update(['is_active' => true]);
        } else {
            // Create new link
            ShippingOrderPublicLink::createForOrder($shippingOrder);
        }

        return redirect()
            ->back()
            ->with('success', 'Enlace de tracking público activado.');
    }

    /**
     * Disable public tracking for the shipping order.
     */
    public function disablePublicTracking(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('managePublicTracking', $shippingOrder);

        $link = $shippingOrder->publicLink;

        if ($link) {
            $link->update(['is_active' => false]);
        }

        return redirect()
            ->back()
            ->with('success', 'Enlace de tracking público desactivado.');
    }

    /**
     * Update ocean shipment details.
     */
    public function updateOceanShipment(Request $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('update', $shippingOrder);

        $validated = $request->validate([
            'mbl_number' => 'nullable|string|max:100',
            'hbl_number' => 'nullable|string|max:100',
            'carrier_name' => 'nullable|string|max:255',
            'vessel_name' => 'nullable|string|max:255',
            'voyage_number' => 'nullable|string|max:100',
            'container_details' => 'nullable|array',
        ]);

        try {
            $this->shipmentService->upsertOceanDetails($shippingOrder, $validated);

            return redirect()
                ->back()
                ->with('success', 'Detalles de embarque marítimo actualizados.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors());
        }
    }

    /**
     * Update air shipment details.
     */
    public function updateAirShipment(Request $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('update', $shippingOrder);

        $validated = $request->validate([
            'mawb_number' => 'nullable|string|max:100',
            'hawb_number' => 'nullable|string|max:100',
            'airline_name' => 'nullable|string|max:255',
            'flight_number' => 'nullable|string|max:100',
        ]);

        try {
            $this->shipmentService->upsertAirDetails($shippingOrder, $validated);

            return redirect()
                ->back()
                ->with('success', 'Detalles de embarque aéreo actualizados.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors());
        }
    }
}
