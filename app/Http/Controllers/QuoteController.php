<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Exceptions\InvalidQuoteStateTransitionException;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ProductService;
use App\Models\Quote;
use App\Models\Carrier;
use App\Models\Division;

use App\Models\Project;
use App\Models\ServiceType;
use App\Models\Term;
use App\Models\TransportMode;
use App\Services\QuoteConversionService;
use App\Services\QuotePdfService;
use App\Services\QuotePricingService;
use App\Services\QuoteStateMachine;
use App\Services\TermsResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Http\Resources\QuoteResource;

class QuoteController extends Controller
{
    public function __construct(
        protected QuotePricingService $pricingService,
        protected QuoteStateMachine $stateMachine,
        protected QuoteConversionService $conversionService,
        protected QuotePdfService $pdfService,
        protected TermsResolverService $termsResolver,
    ) {}

    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Quote::class);

        $query = Quote::withRelations()
            ->accessibleBy($request->user())
            ->withCount('lines')
            ->with('shippingOrder:id,quote_id,order_number'); // Load if already converted

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Order by most recent
        $query->orderBy('created_at', 'desc');

        // Paginate
        $quotes = $query->paginate(15)->withQueryString();

        // Add has_shipping_order flag to each quote
        $quotes->getCollection()->transform(function ($quote) {
            $quote->has_shipping_order = $quote->shippingOrder !== null;
            return $quote;
        });

        // Get filter options
        $customers = Customer::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('quotes/index', [
            'quotes' => $quotes,
            'customers' => $customers,
            'statuses' => collect(QuoteStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'filters' => $request->only(['status', 'customer_id', 'date_from', 'date_to']),
            'can' => [
                'create' => $request->user()->can('quotes.create'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new quote.
     */
    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', Quote::class);

        return Inertia::render('quotes/create', [
            'customers' => Customer::select('id', 'name', 'code', 'billing_address as address', 'city', 'country')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'ports' => Port::select('id', 'code', 'name', 'country', 'type')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'transportModes' => TransportMode::select('id', 'code', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'serviceTypes' => ServiceType::select('id', 'code', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'currencies' => Currency::select('id', 'code', 'name', 'symbol')
                ->orderBy('code')
                ->get(),
            'productsServices' => ProductService::select('id', 'code', 'name', 'default_unit_price', 'taxable', 'type')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'paymentTerms' => Term::getOptionsForType(Term::TYPE_PAYMENT),
            'footerTerms' => Term::getOptionsForType(Term::TYPE_QUOTE_FOOTER),
            'projects' => Project::select('id', 'name', 'code')->where('is_active', true)->orderBy('name')->get(),
            'carriers' => Carrier::select('id', 'name', 'code')->where('is_active', true)->orderBy('name')->get(),
            'issuingCompanies' => CompanySetting::select('id', 'name')->orderBy('name')->get(),
            'divisions' => Division::select('id', 'name', 'code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created quote.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $quote = DB::transaction(function () use ($validated, $request) {
            // Create quote header
            $quote = Quote::create([
                'customer_id' => $validated['customer_id'],
                'contact_id' => $validated['contact_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
                'project_id' => $validated['project_id'] ?? null,
                'issuing_company_id' => $validated['issuing_company_id'] ?? null,
                'carrier_id' => $validated['carrier_id'] ?? null,
                'shipper_id' => $validated['shipper_id'] ?? null,
                'consignee_id' => $validated['consignee_id'] ?? null,
                'transit_days' => $validated['transit_days'] ?? null,
                'incoterms' => $validated['incoterms'] ?? null,
                'origin_port_id' => $validated['origin_port_id'],
                'destination_port_id' => $validated['destination_port_id'],
                'transport_mode_id' => $validated['transport_mode_id'],
                'service_type_id' => $validated['service_type_id'],
                'currency_id' => $validated['currency_id'],
                'valid_until' => $validated['valid_until'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'total_pieces' => $validated['total_pieces'] ?? null,
                'total_weight_kg' => $validated['total_weight_kg'] ?? null,
                'total_volume_cbm' => $validated['total_volume_cbm'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // Create lines
            foreach ($validated['lines'] as $index => $lineData) {
                $quote->lines()->create([
                    'product_service_id' => $lineData['product_service_id'],
                    'description' => $lineData['description'] ?? null,
                    'quantity' => $lineData['quantity'],
                    'unit_price' => $lineData['unit_price'],
                    'unit_cost' => $lineData['unit_cost'] ?? null,
                    'discount_percent' => $lineData['discount_percent'] ?? 0,
                    'tax_rate' => $lineData['tax_rate'] ?? 0,
                    'line_total' => 0, // Calculated by model
                    'sort_order' => $index,
                ]);
            }

            // Sync Commodities (Items)
            if (isset($validated['items'])) {
                $this->syncItems($quote, $validated['items']);
            }

            // Resolve and assign terms (use provided IDs or defaults from company settings)
            $this->termsResolver->resolveForQuote(
                $quote,
                $validated['payment_terms_id'] ?? null,
                $validated['footer_terms_id'] ?? null
            );
            $quote->save();

            // Recalculate totals
            $this->pricingService->recalculateAndPersist($quote);

            return $quote;
        });

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', "Cotización {$quote->quote_number} creada exitosamente.");
    }

    /**
     * Display the specified quote.
     */
    public function show(Quote $quote): InertiaResponse
    {
        $this->authorize('view', $quote);

        $quote->load([
            'customer',
            'contact:id,name,email,phone',
            'contact',
            'originPort',
            'destinationPort',
            'transportMode',
            'serviceType',
            'currency',
            'lines.productService',
            'createdBy',
            'salesRep',
            'paymentTerms',
            'footerTerms',
            'items.lines',
        ]);

        $quote->load('shippingOrder');

        return Inertia::render('quotes/show', [
            'quote' => (new QuoteResource($quote))->resolve(),
            'company' => \App\Models\CompanySetting::first(),
            'shippingOrder' => $quote->shippingOrder ? [
                'id' => $quote->shippingOrder->id,
                'order_number' => $quote->shippingOrder->order_number,
            ] : null,
            'can' => [
                'update' => request()->user()?->can('update', $quote) ?? false,
                'delete' => request()->user()?->can('delete', $quote) ?? false,
                'send' => request()->user()?->can('send', $quote) ?? false,
                'approve' => request()->user()?->can('approve', $quote) ?? false,
                'reject' => request()->user()?->can('reject', $quote) ?? false,
                'convert' => request()->user()?->can('convert', $quote) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified quote.
     */
    public function edit(Quote $quote): InertiaResponse
    {
        $this->authorize('update', $quote);

        $quote->load('lines', 'items.lines');

        return Inertia::render('quotes/edit', [
            'quote' => $quote,
            'customers' => Customer::select('id', 'name', 'code', 'billing_address as address', 'city', 'country')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'ports' => Port::select('id', 'code', 'name', 'country', 'type')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'transportModes' => TransportMode::select('id', 'code', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'serviceTypes' => ServiceType::select('id', 'code', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'currencies' => Currency::select('id', 'code', 'name', 'symbol')
                ->orderBy('code')
                ->get(),
            'productsServices' => ProductService::select('id', 'code', 'name', 'default_unit_price', 'taxable', 'type')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'paymentTerms' => Term::getOptionsForType(Term::TYPE_PAYMENT),
            'footerTerms' => Term::getOptionsForType(Term::TYPE_QUOTE_FOOTER),
            'projects' => Project::select('id', 'name', 'code')->where('is_active', true)->orderBy('name')->get(),
            'carriers' => Carrier::select('id', 'name', 'code')->where('is_active', true)->orderBy('name')->get(),
            'issuingCompanies' => CompanySetting::select('id', 'name')->orderBy('name')->get(),
            'divisions' => Division::select('id', 'name', 'code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update the specified quote.
     */
    public function update(UpdateQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $quote) {
            // Update quote header
            $quote->update([
                'customer_id' => $validated['customer_id'],
                'contact_id' => $validated['contact_id'] ?? null,
                'division_id' => $validated['division_id'] ?? null,
                'project_id' => $validated['project_id'] ?? null,
                'issuing_company_id' => $validated['issuing_company_id'] ?? null,
                'carrier_id' => $validated['carrier_id'] ?? null,
                'shipper_id' => $validated['shipper_id'] ?? null,
                'consignee_id' => $validated['consignee_id'] ?? null,
                'transit_days' => $validated['transit_days'] ?? null,
                'incoterms' => $validated['incoterms'] ?? null,
                'origin_port_id' => $validated['origin_port_id'],
                'destination_port_id' => $validated['destination_port_id'],
                'transport_mode_id' => $validated['transport_mode_id'],
                'service_type_id' => $validated['service_type_id'],
                'currency_id' => $validated['currency_id'],
                'valid_until' => $validated['valid_until'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'total_pieces' => $validated['total_pieces'] ?? null,
                'total_weight_kg' => $validated['total_weight_kg'] ?? null,
                'total_volume_cbm' => $validated['total_volume_cbm'] ?? null,
            ]);

            // Sync lines: delete existing and recreate
            $quote->lines()->delete();

            foreach ($validated['lines'] as $index => $lineData) {
                $quote->lines()->create([
                    'product_service_id' => $lineData['product_service_id'],
                    'description' => $lineData['description'] ?? null,
                    'quantity' => $lineData['quantity'],
                    'unit_price' => $lineData['unit_price'],
                    'unit_cost' => $lineData['unit_cost'] ?? null,
                    'discount_percent' => $lineData['discount_percent'] ?? 0,
                    'tax_rate' => $lineData['tax_rate'] ?? 0,
                    'line_total' => 0,
                    'sort_order' => $index,
                ]);
            }

            // Sync Commodities (Items)
            if (isset($validated['items'])) {
                $this->syncItems($quote, $validated['items']);
            }

            // Resolve and assign terms (use provided IDs or defaults from company settings)
            $this->termsResolver->resolveForQuote(
                $quote,
                $validated['payment_terms_id'] ?? null,
                $validated['footer_terms_id'] ?? null
            );
            $quote->save();

            // Recalculate totals
            $this->pricingService->recalculateAndPersist($quote);
        });

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Cotización actualizada exitosamente.');
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Quote $quote): RedirectResponse
    {
        $this->authorize('delete', $quote);

        $quoteNumber = $quote->quote_number;
        $quote->delete();

        return redirect()
            ->route('quotes.index')
            ->with('success', "Cotización {$quoteNumber} eliminada.");
    }

    // ========== State Actions ==========

    /**
     * Send the quote (draft → sent).
     */
    public function send(Quote $quote): RedirectResponse
    {
        $this->authorize('send', $quote);

        try {
            $this->stateMachine->send($quote);

            return redirect()
                ->route('quotes.show', $quote)
                ->with('success', 'Cotización enviada exitosamente.');
        } catch (InvalidQuoteStateTransitionException $e) {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Approve the quote (sent → approved).
     */
    public function approve(Quote $quote): RedirectResponse
    {
        $this->authorize('approve', $quote);

        try {
            $this->stateMachine->approve($quote);

            return redirect()
                ->route('quotes.show', $quote)
                ->with('success', 'Cotización aprobada exitosamente.');
        } catch (InvalidQuoteStateTransitionException $e) {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject the quote (sent → rejected).
     */
    public function reject(Quote $quote): RedirectResponse
    {
        $this->authorize('reject', $quote);

        try {
            $this->stateMachine->reject($quote);

            return redirect()
                ->route('quotes.show', $quote)
                ->with('success', 'Cotización rechazada.');
        } catch (InvalidQuoteStateTransitionException $e) {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Convert approved quote to shipping order.
     */
    public function convertToShippingOrder(Quote $quote): RedirectResponse
    {
        $this->authorize('convertToShippingOrder', $quote);

        try {
            $shippingOrder = $this->conversionService->convertToShippingOrder($quote);

            return redirect()
                ->route('quotes.show', $quote)
                ->with('success', "Orden de envío {$shippingOrder->order_number} creada exitosamente.");
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Generate and stream PDF for the quote.
     */
    public function print(Request $request, Quote $quote): Response
    {
        $this->authorize('view', $quote);

        $lang = $request->input('lang', 'es');
        $mode = $request->input('mode', 'standard');

        return $this->pdfService->stream($quote, $lang, $mode);
    }

    /**
     * Sync quote items (commodities) and their lines.
     */
    private function syncItems(Quote $quote, array $items): void
    {
        // Delete existing items (cascades lines)
        $quote->items()->delete();

        foreach ($items as $itemData) {
            $item = $quote->items()->create([
                'type' => $itemData['type'],
                'identifier' => $itemData['identifier'] ?? null,
                'seal_number' => $itemData['seal_number'] ?? null,
                'properties' => $itemData['properties'] ?? [],
            ]);

            foreach ($itemData['lines'] as $lineData) {
                $item->lines()->create([
                    'pieces' => $lineData['pieces'],
                    'description' => $lineData['description'] ?? null,
                    'weight_kg' => $lineData['weight_kg'],
                    'volume_cbm' => $lineData['volume_cbm'],
                    'marks_numbers' => $lineData['marks_numbers'] ?? null,
                    'hs_code' => $lineData['hs_code'] ?? null,
                ]);
            }
        }
    }
}
