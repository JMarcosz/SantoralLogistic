<?php

namespace Tests\Feature\Billing;

use App\Models\PreInvoice;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvoiceRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_sales_cannot_generate_invoice()
    {
        $salesUser = User::factory()->create();
        $salesUser->assignRole('sales');

        $preInvoice = PreInvoice::factory()->create([
            'status' => 'issued', // Ready for invoicing
        ]);

        $response = $this->actingAs($salesUser)
            ->post(route('pre-invoices.generate-invoice', $preInvoice));

        $response->assertForbidden();
    }

    public function test_accounting_can_generate_invoice()
    {
        $accountingUser = User::factory()->create();
        $accountingUser->assignRole('accounting');

        $preInvoice = PreInvoice::factory()->create([
            'status' => 'issued',
        ]);

        // Mock the InvoiceService to avoid complex dependencies (fiscal sequences, etc)
        // We just want to test Authorization here.
        $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldReceive('createFromPreInvoice')
                ->once()
                ->andReturn(new \App\Models\Invoice(['number' => 'B0100000001', 'ncf' => 'B0100000001']));
        });

        $response = $this->actingAs($accountingUser)
            ->post(route('pre-invoices.generate-invoice', $preInvoice));

        $response->assertRedirect(route('pre-invoices.show', $preInvoice));
        $response->assertSessionHas('success');
    }

    public function test_admin_can_generate_invoice()
    {
        $adminUser = User::factory()->create();
        $adminUser->assignRole('super_admin');

        $preInvoice = PreInvoice::factory()->create([
            'status' => 'issued',
        ]);

        $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldReceive('createFromPreInvoice')
                ->once()
                ->andReturn(new \App\Models\Invoice(['number' => 'B0100000001', 'ncf' => 'B0100000001']));
        });

        $response = $this->actingAs($adminUser)
            ->post(route('pre-invoices.generate-invoice', $preInvoice));

        $response->assertRedirect();
    }
}
