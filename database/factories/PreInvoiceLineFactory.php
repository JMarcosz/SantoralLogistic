<?php

namespace Database\Factories;

use App\Models\PreInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreInvoiceLineFactory extends Factory
{
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $amount = round($qty * $unitPrice, 2);
        $taxAmount = round($amount * 0.18, 2);

        return [
            'pre_invoice_id' => PreInvoice::factory(),
            'code' => strtoupper($this->faker->bothify('ITEM-####')),
            'description' => $this->faker->sentence(4),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'currency_code' => 'DOP',
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
