<?php

namespace App\Http\Controllers;

use App\Enums\WarehouseReceiptStatus;
use App\Exceptions\InvalidWarehouseReceiptTransitionException;
use App\Exports\WarehouseReceiptsExport;
use App\Http\Requests\StoreWarehouseReceiptRequest;
use App\Http\Requests\UpdateWarehouseReceiptRequest;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptLine;
use App\Services\WarehouseReceiptStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseReceiptController extends Controller
{
    public function __construct(
        private WarehouseReceiptStateMachine $stateMachine
    ) {}

    /**
     * Display a listing of warehouse receipts.
     */
    public function index(): Response
    {
        $filters = request()->only(['warehouse_id', 'customer_id', 'status', 'date_from', 'date_to']);

        $query = WarehouseReceipt::with(['warehouse', 'customer'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $receipts = $query->paginate(20)->withQueryString();

        return Inertia::render('warehouse-receipts/index', [
            'receipts' => $receipts,
            'filters' => $filters,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name', 'code']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'statuses' => collect(WarehouseReceiptStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    /**
     * Show the form for creating a new receipt.
     */
    public function create(): Response
    {
        return Inertia::render('warehouse-receipts/create', [
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name', 'code']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created receipt.
     */
    public function store(StoreWarehouseReceiptRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $receipt = DB::transaction(function () use ($validated) {
            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();

            // Create receipt
            $receipt = WarehouseReceipt::create([
                'warehouse_id' => $validated['warehouse_id'],
                'customer_id' => $validated['customer_id'],
                'shipping_order_id' => $validated['shipping_order_id'] ?? null,
                'receipt_number' => $receiptNumber,
                'reference' => $validated['reference'] ?? null,
                'status' => WarehouseReceiptStatus::Draft,
                'expected_at' => $validated['expected_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create lines
            foreach ($validated['lines'] as $lineData) {
                $receipt->lines()->create([
                    'item_code' => $lineData['item_code'] ?? null,
                    'description' => $lineData['description'],
                    'expected_qty' => $lineData['expected_qty'] ?? null,
                    'received_qty' => $lineData['received_qty'],
                    'uom' => $lineData['uom'],
                    'lot_number' => $lineData['lot_number'] ?? null,
                    'serial_number' => $lineData['serial_number'] ?? null,
                    'expiration_date' => $lineData['expiration_date'] ?? null,
                ]);
            }

            return $receipt;
        });

        return redirect()
            ->route('warehouse-receipts.show', $receipt)
            ->with('success', 'Recepción creada exitosamente.');
    }

    /**
     * Display the specified receipt.
     */
    public function show(WarehouseReceipt $warehouseReceipt): Response
    {
        $warehouseReceipt->load(['warehouse', 'customer', 'lines', 'inventoryItems', 'shippingOrder']);

        return Inertia::render('warehouse-receipts/show', [
            'receipt' => $warehouseReceipt,
            'allowedTransitions' => $this->stateMachine->getAllowedTransitions($warehouseReceipt),
            'canEdit' => $this->stateMachine->canEdit($warehouseReceipt),
        ]);
    }

    /**
     * Show the form for editing the receipt.
     */
    public function edit(WarehouseReceipt $warehouseReceipt): Response|RedirectResponse
    {
        if (!$this->stateMachine->canEdit($warehouseReceipt)) {
            return redirect()
                ->route('warehouse-receipts.show', $warehouseReceipt)
                ->with('error', 'Esta recepción no puede ser editada.');
        }

        $warehouseReceipt->load(['lines']);

        return Inertia::render('warehouse-receipts/edit', [
            'receipt' => $warehouseReceipt,
        ]);
    }

    /**
     * Update the specified receipt.
     */
    public function update(UpdateWarehouseReceiptRequest $request, WarehouseReceipt $warehouseReceipt): RedirectResponse
    {
        if (!$this->stateMachine->canEdit($warehouseReceipt)) {
            return redirect()
                ->route('warehouse-receipts.show', $warehouseReceipt)
                ->with('error', 'Esta recepción no puede ser editada.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($warehouseReceipt, $validated) {
            // Update receipt
            $warehouseReceipt->update([
                'reference' => $validated['reference'] ?? $warehouseReceipt->reference,
                'expected_at' => $validated['expected_at'] ?? $warehouseReceipt->expected_at,
                'notes' => $validated['notes'] ?? $warehouseReceipt->notes,
            ]);

            // Update lines if provided
            if (isset($validated['lines'])) {
                // Get existing line IDs
                $existingIds = $warehouseReceipt->lines()->pluck('id')->toArray();
                $updatedIds = [];

                foreach ($validated['lines'] as $lineData) {
                    if (isset($lineData['id']) && in_array($lineData['id'], $existingIds)) {
                        // Update existing line
                        WarehouseReceiptLine::where('id', $lineData['id'])->update([
                            'item_code' => $lineData['item_code'] ?? null,
                            'description' => $lineData['description'],
                            'expected_qty' => $lineData['expected_qty'] ?? null,
                            'received_qty' => $lineData['received_qty'],
                            'uom' => $lineData['uom'],
                            'lot_number' => $lineData['lot_number'] ?? null,
                            'serial_number' => $lineData['serial_number'] ?? null,
                            'expiration_date' => $lineData['expiration_date'] ?? null,
                        ]);
                        $updatedIds[] = $lineData['id'];
                    } else {
                        // Create new line
                        $newLine = $warehouseReceipt->lines()->create([
                            'item_code' => $lineData['item_code'] ?? null,
                            'description' => $lineData['description'],
                            'expected_qty' => $lineData['expected_qty'] ?? null,
                            'received_qty' => $lineData['received_qty'],
                            'uom' => $lineData['uom'],
                            'lot_number' => $lineData['lot_number'] ?? null,
                            'serial_number' => $lineData['serial_number'] ?? null,
                            'expiration_date' => $lineData['expiration_date'] ?? null,
                        ]);
                        $updatedIds[] = $newLine->id;
                    }
                }

                // Delete lines that were not in the update
                $toDelete = array_diff($existingIds, $updatedIds);
                if (count($toDelete) > 0) {
                    WarehouseReceiptLine::whereIn('id', $toDelete)->delete();
                }
            }
        });

        return redirect()
            ->route('warehouse-receipts.show', $warehouseReceipt)
            ->with('success', 'Recepción actualizada exitosamente.');
    }

    /**
     * Mark receipt as received.
     */
    public function markReceived(WarehouseReceipt $warehouseReceipt): RedirectResponse
    {
        try {
            $this->stateMachine->markReceived($warehouseReceipt, auth()->id());

            return redirect()
                ->route('warehouse-receipts.show', $warehouseReceipt)
                ->with('success', 'Recepción marcada como recibida. Se creó el inventario.');
        } catch (InvalidWarehouseReceiptTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Close receipt.
     */
    public function close(WarehouseReceipt $warehouseReceipt): RedirectResponse
    {
        try {
            $this->stateMachine->close($warehouseReceipt);

            return redirect()
                ->route('warehouse-receipts.show', $warehouseReceipt)
                ->with('success', 'Recepción cerrada exitosamente.');
        } catch (InvalidWarehouseReceiptTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel receipt.
     */
    public function cancel(WarehouseReceipt $warehouseReceipt): RedirectResponse
    {
        try {
            $this->stateMachine->cancel($warehouseReceipt);

            return redirect()
                ->route('warehouse-receipts.show', $warehouseReceipt)
                ->with('success', 'Recepción cancelada.');
        } catch (InvalidWarehouseReceiptTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Generate unique receipt number.
     */
    private function generateReceiptNumber(): string
    {
        $prefix = 'WR';
        $year = date('Y');
        $lastReceipt = WarehouseReceipt::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastReceipt ? ((int) substr($lastReceipt->receipt_number, -6)) + 1 : 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Export warehouse receipts to Excel.
     */
    public function export()
    {
        $filters = request()->only(['warehouse_id', 'customer_id', 'status', 'date_from', 'date_to']);
        $filename = 'recepciones_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new WarehouseReceiptsExport($filters), $filename);
    }
}
