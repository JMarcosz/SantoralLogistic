<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Password Change (accessible even with must_change_password = true)
    Route::get('/change-password', [App\Http\Controllers\ChangePasswordController::class, 'show'])
        ->name('password.change');
    Route::post('/change-password', [App\Http\Controllers\ChangePasswordController::class, 'update'])
        ->name('password.change.update');

    // Protected routes - require password to be changed
    Route::middleware('ensure.password.changed')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
            ->name('dashboard');

        // Demo Routes
        Route::get('demo/data-table', function () {
            return Inertia::render('demo/data-table-demo');
        })->name('demo.data-table');

        // User Management
        Route::resource('users', App\Http\Controllers\UserController::class);

        // Role Management
        Route::resource('roles', App\Http\Controllers\RoleController::class);

        // CRM - Customers
        Route::get('crm/customers', [App\Http\Controllers\Crm\CustomerController::class, 'index'])->name('customers.index');
        Route::get('crm/customers/{customer}', [App\Http\Controllers\Crm\CustomerController::class, 'show'])->name('customers.show');
        Route::post('crm/customers', [App\Http\Controllers\Crm\CustomerController::class, 'store'])->name('customers.store');
        Route::put('crm/customers/{customer}', [App\Http\Controllers\Crm\CustomerController::class, 'update'])->name('customers.update');
        Route::delete('crm/customers/{customer}', [App\Http\Controllers\Crm\CustomerController::class, 'destroy'])->name('customers.destroy');

        // CRM - Contacts (nested under customers)
        Route::post('crm/customers/{customer}/contacts', [App\Http\Controllers\Crm\ContactController::class, 'store'])->name('contacts.store');
        Route::put('crm/customers/{customer}/contacts/{contact}', [App\Http\Controllers\Crm\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('crm/customers/{customer}/contacts/{contact}', [App\Http\Controllers\Crm\ContactController::class, 'destroy'])->name('contacts.destroy');

        // Settings routes (now protected by ensure.password.changed)
        require __DIR__ . '/settings.php';

        // Master Data
        Route::resource('divisions', App\Http\Controllers\DivisionController::class);
        Route::resource('projects', App\Http\Controllers\ProjectController::class);
        Route::resource('carriers', App\Http\Controllers\CarrierController::class);

        // Search APIs
        Route::get('/api/address-search', [App\Http\Controllers\Api\AddressSearchController::class, 'index'])->name('api.address.search');

        // Quotes
        Route::get('quotes', [App\Http\Controllers\QuoteController::class, 'index'])->name('quotes.index');
        Route::get('quotes/create', [App\Http\Controllers\QuoteController::class, 'create'])->name('quotes.create');
        Route::post('quotes', [App\Http\Controllers\QuoteController::class, 'store'])->name('quotes.store');
        Route::get('quotes/{quote}', [App\Http\Controllers\QuoteController::class, 'show'])->name('quotes.show');
        Route::get('quotes/{quote}/edit', [App\Http\Controllers\QuoteController::class, 'edit'])->name('quotes.edit');
        Route::put('quotes/{quote}', [App\Http\Controllers\QuoteController::class, 'update'])->name('quotes.update');
        Route::delete('quotes/{quote}', [App\Http\Controllers\QuoteController::class, 'destroy'])->name('quotes.destroy');

        // Quote state actions
        Route::post('quotes/{quote}/send', [App\Http\Controllers\QuoteController::class, 'send'])->name('quotes.send');
        Route::post('quotes/{quote}/approve', [App\Http\Controllers\QuoteController::class, 'approve'])->name('quotes.approve');
        Route::post('quotes/{quote}/reject', [App\Http\Controllers\QuoteController::class, 'reject'])->name('quotes.reject');
        Route::post('quotes/{quote}/convert-to-shipping-order', [App\Http\Controllers\QuoteController::class, 'convertToShippingOrder'])->name('quotes.convert');
        Route::post('quotes/{quote}/convert-to-sales-order', [App\Http\Controllers\QuoteController::class, 'convertToSalesOrder'])->name('quotes.convert-to-sales-order');
        Route::get('quotes/{quote}/print', [App\Http\Controllers\QuoteController::class, 'print'])->name('quotes.print');

        // Sales Orders (Órdenes de Pedido)
        Route::get('sales-orders', [App\Http\Controllers\SalesOrderController::class, 'index'])->name('sales-orders.index');
        Route::get('sales-orders/create', [App\Http\Controllers\SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::post('sales-orders', [App\Http\Controllers\SalesOrderController::class, 'store'])->name('sales-orders.store');
        Route::get('sales-orders/{salesOrder}', [App\Http\Controllers\SalesOrderController::class, 'show'])->name('sales-orders.show');

        // Sales Orders state actions
        Route::post('sales-orders/{salesOrder}/confirm', [App\Http\Controllers\SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
        Route::post('sales-orders/{salesOrder}/start-delivery', [App\Http\Controllers\SalesOrderController::class, 'startDelivery'])->name('sales-orders.start-delivery');
        Route::post('sales-orders/{salesOrder}/mark-delivered', [App\Http\Controllers\SalesOrderController::class, 'markDelivered'])->name('sales-orders.mark-delivered');
        Route::post('sales-orders/{salesOrder}/cancel', [App\Http\Controllers\SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');

        // Product/Service API (for quote line tabs and receipt dropdowns)
        Route::get('api/products-services', [App\Http\Controllers\Api\ProductServiceApiController::class, 'index'])->name('api.products-services.index');
        Route::get('api/products-services/products', [App\Http\Controllers\Api\ProductServiceApiController::class, 'products'])->name('api.products-services.products');
        Route::get('api/products-services/products-with-stock', [App\Http\Controllers\Api\ProductServiceApiController::class, 'productsWithStock'])->name('api.products-services.products-with-stock');

        // Shipping Orders
        Route::get('shipping-orders', [App\Http\Controllers\ShippingOrderController::class, 'index'])->name('shipping-orders.index');
        Route::get('shipping-orders/create', [App\Http\Controllers\ShippingOrderController::class, 'create'])->name('shipping-orders.create');
        Route::post('shipping-orders', [App\Http\Controllers\ShippingOrderController::class, 'store'])->name('shipping-orders.store');
        Route::get('shipping-orders/{shippingOrder}', [App\Http\Controllers\ShippingOrderController::class, 'show'])->name('shipping-orders.show');
        Route::get('shipping-orders/{shippingOrder}/edit', [App\Http\Controllers\ShippingOrderController::class, 'edit'])->name('shipping-orders.edit');
        Route::put('shipping-orders/{shippingOrder}', [App\Http\Controllers\ShippingOrderController::class, 'update'])->name('shipping-orders.update');

        // Shipping Orders state actions
        Route::post('shipping-orders/{shippingOrder}/book', [App\Http\Controllers\ShippingOrderController::class, 'book'])->name('shipping-orders.book');
        Route::post('shipping-orders/{shippingOrder}/start-transit', [App\Http\Controllers\ShippingOrderController::class, 'startTransit'])->name('shipping-orders.start-transit');
        Route::post('shipping-orders/{shippingOrder}/arrive', [App\Http\Controllers\ShippingOrderController::class, 'arrive'])->name('shipping-orders.arrive');
        Route::post('shipping-orders/{shippingOrder}/deliver', [App\Http\Controllers\ShippingOrderController::class, 'deliver'])->name('shipping-orders.deliver');
        Route::post('shipping-orders/{shippingOrder}/close', [App\Http\Controllers\ShippingOrderController::class, 'close'])->name('shipping-orders.close');
        Route::post('shipping-orders/{shippingOrder}/cancel', [App\Http\Controllers\ShippingOrderController::class, 'cancel'])->name('shipping-orders.cancel');

        // Shipping Orders milestones
        Route::post('shipping-orders/{shippingOrder}/milestones', [App\Http\Controllers\ShippingOrderController::class, 'storeMilestone'])->name('shipping-orders.milestones.store');

        // Shipping Orders documents
        Route::post('shipping-orders/{shippingOrder}/documents', [App\Http\Controllers\ShippingOrderController::class, 'storeDocument'])->name('shipping-orders.documents.store');
        Route::get('shipping-orders/{shippingOrder}/documents/{document}', [App\Http\Controllers\ShippingOrderController::class, 'downloadDocument'])->name('shipping-orders.documents.download');
        Route::delete('shipping-orders/{shippingOrder}/documents/{document}', [App\Http\Controllers\ShippingOrderController::class, 'destroyDocument'])->name('shipping-orders.documents.destroy');
        Route::get('shipping-orders/{shippingOrder}/print', [App\Http\Controllers\ShippingOrderController::class, 'print'])->name('shipping-orders.print');

        // Create Warehouse Order from Shipping Order
        Route::post('shipping-orders/{shippingOrder}/warehouse-order', [App\Http\Controllers\WarehouseOrderController::class, 'storeFromShippingOrder'])->name('shipping-orders.warehouse-order.store');

        // Shipping Orders public tracking management
        Route::post('shipping-orders/{shippingOrder}/enable-public-tracking', [App\Http\Controllers\ShippingOrderController::class, 'enablePublicTracking'])->name('shipping-orders.enable-public-tracking');
        Route::post('shipping-orders/{shippingOrder}/disable-public-tracking', [App\Http\Controllers\ShippingOrderController::class, 'disablePublicTracking'])->name('shipping-orders.disable-public-tracking');

        // Shipping Orders shipment details (modal-specific)
        Route::post('shipping-orders/{shippingOrder}/ocean-shipment', [App\Http\Controllers\ShippingOrderController::class, 'updateOceanShipment'])->name('shipping-orders.ocean-shipment.update');
        Route::post('shipping-orders/{shippingOrder}/air-shipment', [App\Http\Controllers\ShippingOrderController::class, 'updateAirShipment'])->name('shipping-orders.air-shipment.update');

        // Shipping Orders Items (Commodities)
        Route::post('shipping-orders/{shippingOrder}/items', [App\Http\Controllers\ShippingOrderItemController::class, 'store'])->name('shipping-orders.items.store');
        Route::delete('shipping-orders/{shippingOrder}/items/{item}', [App\Http\Controllers\ShippingOrderItemController::class, 'destroy'])->name('shipping-orders.items.destroy');

        // Shipping Orders inventory reservations
        Route::get('shipping-orders/{shippingOrder}/reservations', [App\Http\Controllers\InventoryReservationController::class, 'index'])->name('shipping-orders.reservations.index');
        Route::post('shipping-orders/{shippingOrder}/reserve-inventory', [App\Http\Controllers\InventoryReservationController::class, 'store'])->name('shipping-orders.reserve-inventory');
        Route::delete('shipping-orders/{shippingOrder}/reservations', [App\Http\Controllers\InventoryReservationController::class, 'destroy'])->name('shipping-orders.reservations.destroy');

        // Create Warehouse Order from Shipping Order
        Route::post('shipping-orders/{shippingOrder}/create-warehouse-order', [App\Http\Controllers\WarehouseOrderController::class, 'storeFromShippingOrder'])->name('shipping-orders.create-warehouse-order');

        // Warehouse Orders
        Route::get('warehouse-orders', [App\Http\Controllers\WarehouseOrderController::class, 'index'])->name('warehouse-orders.index');
        Route::get('warehouse-orders/{warehouseOrder}', [App\Http\Controllers\WarehouseOrderController::class, 'show'])->name('warehouse-orders.show');
        Route::post('warehouse-orders/{warehouseOrder}/start-picking', [App\Http\Controllers\WarehouseOrderController::class, 'startPicking'])->name('warehouse-orders.start-picking');
        Route::patch('warehouse-orders/{warehouseOrder}/lines/{line}', [App\Http\Controllers\WarehouseOrderController::class, 'updateLine'])->name('warehouse-orders.lines.update');
        Route::post('warehouse-orders/{warehouseOrder}/mark-packed', [App\Http\Controllers\WarehouseOrderController::class, 'markPacked'])->name('warehouse-orders.mark-packed');
        Route::post('warehouse-orders/{warehouseOrder}/mark-dispatched', [App\Http\Controllers\WarehouseOrderController::class, 'markDispatched'])->name('warehouse-orders.mark-dispatched');
        Route::post('warehouse-orders/{warehouseOrder}/cancel', [App\Http\Controllers\WarehouseOrderController::class, 'cancel'])->name('warehouse-orders.cancel');

        // Pickup Orders
        Route::resource('pickup-orders', App\Http\Controllers\PickupOrderController::class);
        Route::post('pickup-orders/{pickupOrder}/assign-driver', [App\Http\Controllers\PickupOrderController::class, 'assignDriver'])->name('pickup-orders.assign-driver');
        Route::post('pickup-orders/{pickupOrder}/start', [App\Http\Controllers\PickupOrderController::class, 'start'])->name('pickup-orders.start');
        Route::post('pickup-orders/{pickupOrder}/complete', [App\Http\Controllers\PickupOrderController::class, 'complete'])->name('pickup-orders.complete');
        Route::post('pickup-orders/{pickupOrder}/cancel', [App\Http\Controllers\PickupOrderController::class, 'cancel'])->name('pickup-orders.cancel');
        Route::post('pickup-orders/{pickupOrder}/pod', [App\Http\Controllers\PickupOrderController::class, 'storePod'])->name('pickup-orders.pod.store');

        // Delivery Orders
        Route::resource('delivery-orders', App\Http\Controllers\DeliveryOrderController::class);
        Route::post('delivery-orders/{deliveryOrder}/assign-driver', [App\Http\Controllers\DeliveryOrderController::class, 'assignDriver'])->name('delivery-orders.assign-driver');
        Route::post('delivery-orders/{deliveryOrder}/start', [App\Http\Controllers\DeliveryOrderController::class, 'start'])->name('delivery-orders.start');
        Route::post('delivery-orders/{deliveryOrder}/complete', [App\Http\Controllers\DeliveryOrderController::class, 'complete'])->name('delivery-orders.complete');
        Route::post('delivery-orders/{deliveryOrder}/cancel', [App\Http\Controllers\DeliveryOrderController::class, 'cancel'])->name('delivery-orders.cancel');
        Route::post('delivery-orders/{deliveryOrder}/pod', [App\Http\Controllers\DeliveryOrderController::class, 'storePod'])->name('delivery-orders.pod.store');

        // POD Image
        Route::get('pods/{pod}/image', [App\Http\Controllers\PodController::class, 'showImage'])->name('pods.image');

        // Warehouse Receipts
        Route::resource('warehouse-receipts', App\Http\Controllers\WarehouseReceiptController::class);
        Route::post('warehouse-receipts/{warehouseReceipt}/mark-received', [App\Http\Controllers\WarehouseReceiptController::class, 'markReceived'])->name('warehouse-receipts.mark-received');
        Route::post('warehouse-receipts/{warehouseReceipt}/close', [App\Http\Controllers\WarehouseReceiptController::class, 'close'])->name('warehouse-receipts.close');
        Route::post('warehouse-receipts/{warehouseReceipt}/cancel', [App\Http\Controllers\WarehouseReceiptController::class, 'cancel'])->name('warehouse-receipts.cancel');

        // Inventory
        Route::get('inventory', [App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/by-customer', [App\Http\Controllers\InventoryController::class, 'byCustomer'])->name('inventory.by-customer');
        Route::get('inventory/export', [App\Http\Controllers\InventoryController::class, 'export'])->name('inventory.export');
        Route::get('api/inventory/search', [App\Http\Controllers\InventoryController::class, 'searchAvailable'])->name('api.inventory.search');

        // Inventory Movements
        Route::post('inventory/{item}/putaway', [App\Http\Controllers\InventoryMovementController::class, 'putaway'])->name('inventory.putaway');
        Route::post('inventory/{item}/relocate', [App\Http\Controllers\InventoryMovementController::class, 'relocate'])->name('inventory.relocate');
        Route::post('inventory/{item}/adjust', [App\Http\Controllers\InventoryMovementController::class, 'adjust'])->name('inventory.adjust');
        Route::get('inventory/{item}/movements', [App\Http\Controllers\InventoryMovementController::class, 'movements'])->name('inventory.movements');
        Route::get('api/warehouses/{warehouse}/locations', [App\Http\Controllers\InventoryMovementController::class, 'locations'])->name('api.warehouse-locations');

        // Cycle Counts
        Route::get('cycle-counts', [App\Http\Controllers\CycleCountController::class, 'index'])->name('cycle-counts.index');
        Route::get('cycle-counts/create', [App\Http\Controllers\CycleCountController::class, 'create'])->name('cycle-counts.create');
        Route::post('cycle-counts', [App\Http\Controllers\CycleCountController::class, 'store'])->name('cycle-counts.store');
        Route::get('cycle-counts/{cycleCount}', [App\Http\Controllers\CycleCountController::class, 'show'])->name('cycle-counts.show');
        Route::post('cycle-counts/{cycleCount}/start', [App\Http\Controllers\CycleCountController::class, 'start'])->name('cycle-counts.start');
        Route::patch('cycle-counts/{cycleCount}/lines/{line}', [App\Http\Controllers\CycleCountController::class, 'updateLine'])->name('cycle-counts.lines.update');
        Route::post('cycle-counts/{cycleCount}/complete', [App\Http\Controllers\CycleCountController::class, 'complete'])->name('cycle-counts.complete');
        Route::post('cycle-counts/{cycleCount}/cancel', [App\Http\Controllers\CycleCountController::class, 'cancel'])->name('cycle-counts.cancel');

        // Warehouse Orders (Pick/Pack/Dispatch)
        Route::get('warehouse-orders', [App\Http\Controllers\WarehouseOrderController::class, 'index'])->name('warehouse-orders.index');
        Route::get('warehouse-orders/{warehouseOrder}', [App\Http\Controllers\WarehouseOrderController::class, 'show'])->name('warehouse-orders.show');
        Route::post('warehouse-orders/{warehouseOrder}/start-picking', [App\Http\Controllers\WarehouseOrderController::class, 'startPicking'])->name('warehouse-orders.start-picking');
        Route::patch('warehouse-orders/{warehouseOrder}/lines/{line}', [App\Http\Controllers\WarehouseOrderController::class, 'updateLine'])->name('warehouse-orders.lines.update');
        Route::post('warehouse-orders/{warehouseOrder}/pack', [App\Http\Controllers\WarehouseOrderController::class, 'markPacked'])->name('warehouse-orders.pack');
        Route::post('warehouse-orders/{warehouseOrder}/dispatch', [App\Http\Controllers\WarehouseOrderController::class, 'markDispatched'])->name('warehouse-orders.dispatch');
        Route::post('warehouse-orders/{warehouseOrder}/cancel', [App\Http\Controllers\WarehouseOrderController::class, 'cancel'])->name('warehouse-orders.cancel');

        // Warehouse Reports
        Route::get('warehouse/reports/inventory', [App\Http\Controllers\WarehouseReportController::class, 'inventory'])->name('warehouse.reports.inventory');
        Route::get('warehouse/reports/inventory/export', [App\Http\Controllers\WarehouseReportController::class, 'exportInventory'])->name('warehouse.reports.inventory.export');
        Route::get('warehouse/reports/movements', [App\Http\Controllers\WarehouseReportController::class, 'movements'])->name('warehouse.reports.movements');
        Route::get('warehouse/reports/movements/export', [App\Http\Controllers\WarehouseReportController::class, 'exportMovements'])->name('warehouse.reports.movements.export');

        // Warehouse Dashboard
        Route::get('warehouse/dashboard', [App\Http\Controllers\WarehouseDashboardController::class, 'index'])->name('warehouse.dashboard');

        // Exports
        Route::get('warehouse-receipts-export', [App\Http\Controllers\WarehouseReceiptController::class, 'export'])->name('warehouse-receipts.export');

        // API for autocomplete
        Route::get('api/customer-items', [App\Http\Controllers\Api\CustomerItemController::class, 'index'])->name('api.customer-items');
        Route::get('api/sku-search', [App\Http\Controllers\Api\SkuSearchController::class, 'search'])->name('api.sku-search');
        // Charges
        Route::post('shipping-orders/{shippingOrder}/charges', [App\Http\Controllers\ShippingOrderChargeController::class, 'store'])->name('shipping-orders.charges.store');
        Route::put('shipping-orders/{shippingOrder}/charges/{charge}', [App\Http\Controllers\ShippingOrderChargeController::class, 'update'])->name('shipping-orders.charges.update');
        Route::delete('shipping-orders/{shippingOrder}/charges/{charge}', [App\Http\Controllers\ShippingOrderChargeController::class, 'destroy'])->name('shipping-orders.charges.destroy');

        // PreInvoices
        Route::get('pre-invoices', [App\Http\Controllers\PreInvoiceController::class, 'index'])->name('pre-invoices.index');
        Route::get('pre-invoices/create', [App\Http\Controllers\PreInvoiceController::class, 'create'])->name('pre-invoices.create');
        Route::post('pre-invoices', [App\Http\Controllers\PreInvoiceController::class, 'store'])->name('pre-invoices.store');
        Route::get('pre-invoices/{preInvoice}', [App\Http\Controllers\PreInvoiceController::class, 'show'])->name('pre-invoices.show');
        Route::get('pre-invoices/{preInvoice}/print', [App\Http\Controllers\PreInvoiceController::class, 'print'])->name('pre-invoices.print');
        Route::post('pre-invoices/{preInvoice}/issue', [App\Http\Controllers\PreInvoiceController::class, 'issue'])->name('pre-invoices.issue');
        Route::post('pre-invoices/{preInvoice}/cancel', [App\Http\Controllers\PreInvoiceController::class, 'cancel'])->name('pre-invoices.cancel');
        Route::get('pre-invoices-export', [App\Http\Controllers\PreInvoiceController::class, 'export'])->name('pre-invoices.export');

        // PreInvoice Payments
        Route::post('pre-invoices/{preInvoice}/payments', [App\Http\Controllers\PreInvoiceController::class, 'recordPayment'])->name('pre-invoices.payments.store');
        Route::post('pre-invoices/{preInvoice}/payments/{payment}/approve', [App\Http\Controllers\PreInvoiceController::class, 'approvePayment'])->name('pre-invoices.payments.approve');
        Route::post('pre-invoices/{preInvoice}/payments/{payment}/void', [App\Http\Controllers\PreInvoiceController::class, 'voidPayment'])->name('pre-invoices.payments.void');

        // Generate Fiscal Invoice from PreInvoice
        Route::post('pre-invoices/{preInvoice}/generate-invoice', [App\Http\Controllers\PreInvoiceController::class, 'generateInvoice'])->name('pre-invoices.generate-invoice');

        // DGII Export Screen
        Route::get('billing/dgii/exports', function () {
            return Inertia::render('billing/dgii/exports');
        })->name('billing.dgii.exports');


        // DGII Statistics and History
        Route::get('dgii-export/statistics', [App\Http\Controllers\DgiiExportController::class, 'statistics'])->name('dgii.export.statistics');
        Route::get('dgii-export/history', [App\Http\Controllers\DgiiExportController::class, 'history'])->name('dgii.export.history');

        // DGII Export Reports (607/608)
        Route::get('dgii-export/607', [App\Http\Controllers\DgiiExportController::class, 'export607'])->name('dgii.export.607');
        Route::get('dgii-export/608', [App\Http\Controllers\DgiiExportController::class, 'export608'])->name('dgii.export.608');

        // Fiscal Invoices
        Route::get('invoices/analytics', [App\Http\Controllers\InvoiceAnalyticsController::class, 'index'])->name('invoices.analytics');
        Route::get('invoices', [App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/cancel', [App\Http\Controllers\InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::get('invoices/{invoice}/print', [App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/email', [App\Http\Controllers\InvoiceController::class, 'email'])->name('invoices.email');
        Route::post('invoices/batch-export', [App\Http\Controllers\InvoiceController::class, 'batchExport'])->name('invoices.batch-export');

        // ============================
        // PAYMENTS MODULE
        // ============================
        Route::get('payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}', [App\Http\Controllers\PaymentController::class, 'show'])->name('payments.show');
        Route::get('payments/{payment}/edit', [App\Http\Controllers\PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('payments/{payment}', [App\Http\Controllers\PaymentController::class, 'update'])->name('payments.update');
        Route::delete('payments/{payment}', [App\Http\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::post('payments/{payment}/post', [App\Http\Controllers\PaymentController::class, 'post'])->name('payments.post');
        Route::post('payments/{payment}/void', [App\Http\Controllers\PaymentController::class, 'void'])->name('payments.void');
        Route::get('payments/{payment}/pdf', [App\Http\Controllers\PaymentController::class, 'pdf'])->name('payments.pdf');
        Route::get('customers/{customer}/pending-invoices', [App\Http\Controllers\PaymentController::class, 'pendingInvoices'])->name('customers.pending-invoices');

        // Generation of Income Receipt
        Route::get('payments/{id}/pdf', [App\Http\Controllers\PaymentController::class, 'pdf'])->name('payments.pdf');

        // Accounts Receivable
        Route::get('billing/accounts-receivable', [App\Http\Controllers\AccountsReceivableController::class, 'index'])->name('billing.ar.index');
        Route::get('billing/accounts-receivable/export', [App\Http\Controllers\AccountsReceivableController::class, 'export'])->name('billing.ar.export');

        // Generate from SO
        Route::post('shipping-orders/{shippingOrder}/pre-invoices', [App\Http\Controllers\PreInvoiceController::class, 'storeFromOrder'])->name('shipping-orders.pre-invoices.store');

        // Admin: Fiscal Sequences
        Route::prefix('admin')->name('admin.')->middleware('can:fiscal_sequences.manage')->group(function () {
            Route::resource('fiscal-sequences', App\Http\Controllers\Admin\FiscalSequenceController::class);
        });


        // ============================
        // ACCOUNTING MODULE
        // ============================
        Route::middleware('can:accounting.view')->group(function () {
            Route::get('/accounting', [App\Http\Controllers\Accounting\AccountingController::class, 'index'])
                ->name('accounting.index');

            // Chart of Accounts
            Route::resource('/accounting/accounts', App\Http\Controllers\Accounting\AccountController::class)
                ->names('accounting.accounts');

            // Accounting Periods
            Route::get('/accounting/periods', [App\Http\Controllers\Accounting\AccountingPeriodController::class, 'index'])
                ->name('accounting.periods.index');
            Route::post('/accounting/periods/{period}/close', [App\Http\Controllers\Accounting\AccountingPeriodController::class, 'close'])
                ->name('accounting.periods.close')
                ->middleware('can:accounting.close_period');
            Route::post('/accounting/periods/{period}/reopen', [App\Http\Controllers\Accounting\AccountingPeriodController::class, 'reopen'])
                ->name('accounting.periods.reopen')
                ->middleware('can:accounting.close_period');
            Route::get('/accounting/periods/{period}/close-preview', [App\Http\Controllers\Accounting\AccountingPeriodController::class, 'closePreview'])
                ->name('accounting.periods.close-preview');

            // Journal Entries
            Route::get('/accounting/journal-entries', [App\Http\Controllers\Accounting\JournalEntryController::class, 'index'])
                ->name('accounting.journal-entries.index');
            Route::get('/accounting/journal-entries/create', [App\Http\Controllers\Accounting\JournalEntryController::class, 'create'])
                ->name('accounting.journal-entries.create');
            Route::post('/accounting/journal-entries', [App\Http\Controllers\Accounting\JournalEntryController::class, 'store'])
                ->name('accounting.journal-entries.store');
            Route::get('/accounting/journal-entries/{journalEntry}', [App\Http\Controllers\Accounting\JournalEntryController::class, 'show'])
                ->name('accounting.journal-entries.show');
            Route::get('/accounting/journal-entries/{journalEntry}/edit', [App\Http\Controllers\Accounting\JournalEntryController::class, 'edit'])
                ->name('accounting.journal-entries.edit');
            Route::put('/accounting/journal-entries/{journalEntry}', [App\Http\Controllers\Accounting\JournalEntryController::class, 'update'])
                ->name('accounting.journal-entries.update');
            Route::delete('/accounting/journal-entries/{journalEntry}', [App\Http\Controllers\Accounting\JournalEntryController::class, 'destroy'])
                ->name('accounting.journal-entries.destroy');
            Route::post('/accounting/journal-entries/{journalEntry}/post', [App\Http\Controllers\Accounting\JournalEntryController::class, 'post'])
                ->name('accounting.journal-entries.post');
            Route::post('/accounting/journal-entries/{journalEntry}/reverse', [App\Http\Controllers\Accounting\JournalEntryController::class, 'reverse'])
                ->name('accounting.journal-entries.reverse');

            // General Ledger (Libro Mayor)
            Route::get('/accounting/ledger', [App\Http\Controllers\Accounting\GeneralLedgerController::class, 'index'])
                ->name('accounting.ledger.index');
            Route::get('/accounting/ledger/export', [App\Http\Controllers\Accounting\GeneralLedgerController::class, 'export'])
                ->name('accounting.ledger.export');

            // Bank Reconciliation
            Route::get('/accounting/bank-reconciliation', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'index'])
                ->name('accounting.bank-reconciliation.index');
            Route::get('/accounting/bank-reconciliation/create', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'create'])
                ->name('accounting.bank-reconciliation.create');
            Route::post('/accounting/bank-reconciliation', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'store'])
                ->name('accounting.bank-reconciliation.store');
            Route::get('/accounting/bank-reconciliation/unreconciled', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'unreconciledReport'])
                ->name('accounting.bank-reconciliation.unreconciled');
            Route::get('/accounting/bank-reconciliation/{bankStatement}', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'show'])
                ->name('accounting.bank-reconciliation.show');
            Route::post('/accounting/bank-reconciliation/{bankStatement}/lines', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'addLine'])
                ->name('accounting.bank-reconciliation.add-line');
            Route::delete('/accounting/bank-reconciliation/{bankStatement}/lines/{line}', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'deleteLine'])
                ->name('accounting.bank-reconciliation.delete-line');
            Route::post('/accounting/bank-reconciliation/{bankStatement}/import', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'importCsv'])
                ->name('accounting.bank-reconciliation.import');
            Route::post('/accounting/bank-reconciliation/{bankStatement}/complete', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'complete'])
                ->name('accounting.bank-reconciliation.complete');
            Route::post('/accounting/bank-reconciliation/lines/{line}/match', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'matchLine'])
                ->name('accounting.bank-reconciliation.match');
            Route::post('/accounting/bank-reconciliation/lines/{line}/unmatch', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'unmatchLine'])
                ->name('accounting.bank-reconciliation.unmatch');
            Route::get('/accounting/bank-reconciliation/lines/{line}/find-matches', [App\Http\Controllers\Accounting\BankReconciliationController::class, 'findMatches'])
                ->name('accounting.bank-reconciliation.find-matches');

            // Accounting Settings
            Route::get('/accounting/settings', [App\Http\Controllers\Accounting\AccountingSettingController::class, 'index'])
                ->name('accounting.settings.index');
            Route::put('/accounting/settings', [App\Http\Controllers\Accounting\AccountingSettingController::class, 'update'])
                ->name('accounting.settings.update');

            // Tax Mappings
            Route::post('/accounting/tax-mappings', [App\Http\Controllers\Accounting\AccountingSettingController::class, 'storeTaxMapping'])
                ->name('accounting.tax-mappings.store');
            Route::put('/accounting/tax-mappings/{taxMapping}', [App\Http\Controllers\Accounting\AccountingSettingController::class, 'updateTaxMapping'])
                ->name('accounting.tax-mappings.update');
            Route::delete('/accounting/tax-mappings/{taxMapping}', [App\Http\Controllers\Accounting\AccountingSettingController::class, 'destroyTaxMapping'])
                ->name('accounting.tax-mappings.destroy');

            // Financial Reports
            Route::get('/accounting/reports', [App\Http\Controllers\Accounting\FinancialReportsController::class, 'index'])
                ->name('accounting.reports.index');
            Route::get('/accounting/reports/balance-sheet', [App\Http\Controllers\Accounting\FinancialReportsController::class, 'balanceSheet'])
                ->name('accounting.reports.balance-sheet');
            Route::get('/accounting/reports/balance-sheet/export', [App\Http\Controllers\Accounting\FinancialReportsController::class, 'exportBalanceSheet'])
                ->name('accounting.reports.balance-sheet.export');
            Route::get('/accounting/reports/income-statement', [App\Http\Controllers\Accounting\FinancialReportsController::class, 'incomeStatement'])
                ->name('accounting.reports.income-statement');
            Route::get('/accounting/reports/income-statement/export', [App\Http\Controllers\Accounting\FinancialReportsController::class, 'exportIncomeStatement'])
                ->name('accounting.reports.income-statement.export');
            Route::get('/accounting/reports/income-statement/compare', [App\Http\Controllers\Accounting\FinancialReportsController::class, 'compareIncomeStatements'])
                ->name('accounting.reports.income-statement.compare');

            // Audit Logs
            Route::get('/accounting/audit-logs', [App\Http\Controllers\Accounting\AuditLogController::class, 'index'])
                ->name('accounting.audit-logs.index');
            Route::get('/accounting/audit-logs/export', [App\Http\Controllers\Accounting\AuditLogController::class, 'export'])
                ->name('accounting.audit-logs.export');
            Route::get('/accounting/audit-logs/entity-history', [App\Http\Controllers\Accounting\AuditLogController::class, 'entityHistory'])
                ->name('accounting.audit-logs.entity-history');
            Route::get('/accounting/audit-logs/{auditLog}', [App\Http\Controllers\Accounting\AuditLogController::class, 'show'])
                ->name('accounting.audit-logs.show');
        });
    });
});

// Public tracking page (no authentication required)
Route::get('track/{token}', [App\Http\Controllers\PublicTrackingController::class, 'show'])
    ->name('public.track');
