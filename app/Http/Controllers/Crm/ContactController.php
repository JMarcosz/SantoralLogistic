<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreContactRequest;
use App\Http\Requests\Crm\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Customer;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request, Customer $customer)
    {
        $this->authorize('create', Contact::class);

        $customer->contacts()->create($request->validated());

        return back()->with('success', 'Contacto creado correctamente.');
    }

    public function update(UpdateContactRequest $request, Customer $customer, Contact $contact)
    {
        $this->authorize('update', $contact);

        // Ensure contact belongs to customer
        abort_if($contact->customer_id !== $customer->id, 404);

        $contact->update($request->validated());

        return back()->with('success', 'Contacto actualizado correctamente.');
    }

    public function destroy(Customer $customer, Contact $contact)
    {
        $this->authorize('delete', $contact);

        // Ensure contact belongs to customer
        abort_if($contact->customer_id !== $customer->id, 404);

        $contact->delete();

        return back()->with('success', 'Contacto eliminado correctamente.');
    }
}
