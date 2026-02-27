<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AddressSearchController extends Controller
{
    /**
     * Search for addresses in the local database.
     */
    public function index(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Search Customers
        $customers = Customer::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('billing_address', 'ilike', "%{$query}%")
                    ->orWhere('shipping_address', 'ilike', "%{$query}%");
            })
            ->limit(10)
            ->get();

        $results = new Collection();

        foreach ($customers as $customer) {
            if ($customer->billing_address) {
                $results->push([
                    'id' => 'bill-' . $customer->id,
                    'source' => 'Cliente: ' . $customer->name . ' (Facturación)',
                    'address' => $customer->billing_address,
                    'city' => $customer->city,
                    'country' => $customer->country,
                ]);
            }

            if ($customer->shipping_address) {
                $results->push([
                    'id' => 'ship-' . $customer->id,
                    'source' => 'Cliente: ' . $customer->name . ' (Envío)',
                    'address' => $customer->shipping_address,
                    'city' => $customer->city,
                    'country' => $customer->country,
                ]);
            }
        }

        // Filter valid matches if the search was strictly on address content
        // (optional, but good for relevance). 
        // For now, returning all associated addresses of matched customers is fine.

        return response()->json($results->unique('address')->values());
    }
}
