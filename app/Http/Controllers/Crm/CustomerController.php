<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCustomerRequest;
use App\Http\Requests\Crm\UpdateCustomerRequest;
use App\Models\Currency;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        // Get distinct countries for filter
        $countries = Customer::whereNotNull('country')
            ->distinct()
            ->pluck('country')
            ->sort()
            ->values();

        return Inertia::render('crm/customers/index', [
            'customers' => Customer::with('currency:id,code,symbol')
                ->orderBy('name')
                ->get(),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol']),
            'countries' => $countries,
            'can' => [
                'create' => Gate::allows('create', Customer::class),
                'update' => Gate::allows('update', Customer::class),
                'delete' => Gate::allows('delete', Customer::class),
            ],
        ]);
    }

    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('crm/customers/show', [
            'customer' => $customer->load([
                'currency:id,code,symbol,name',
                'contacts' => fn($q) => $q->orderByDesc('is_primary')->orderBy('name'),
            ]),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol']),
            'can' => [
                'update' => Gate::allows('update', $customer),
                'delete' => Gate::allows('delete', $customer),
                'createContact' => Gate::allows('create', \App\Models\Contact::class),
                'updateContact' => Gate::allows('update', \App\Models\Contact::class),
                'deleteContact' => Gate::allows('delete', \App\Models\Contact::class),
            ],
        ]);
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente creado correctamente.');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
