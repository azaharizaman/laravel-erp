<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Feature;

use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Models\VendorUser;
use Nexus\Procurement\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vendor Portal Test
 *
 * Tests vendor portal authentication and functionality.
 */
class VendorPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant context for testing
        $this->withTenant();
    }

    /** @test */
    public function vendor_user_can_login_to_portal()
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $vendorUser = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/vendor-portal/auth/login', [
            'email' => $vendorUser->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token',
                'token_type',
            ]);
    }

    /** @test */
    public function inactive_vendor_user_cannot_login()
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $vendorUser = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => false,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/vendor-portal/auth/login', [
            'email' => $vendorUser->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Account is inactive or vendor is suspended']);
    }

    /** @test */
    public function vendor_can_view_their_purchase_orders()
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $vendorUser = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'approved',
        ]);

        $this->actingAs($vendorUser, 'sanctum');

        $response = $this->getJson('/api/vendor-portal/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'po_number',
                        'status',
                        'total_amount',
                    ]
                ]
            ]);
    }

    /** @test */
    public function vendor_can_submit_invoice_for_their_po()
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $vendorUser = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'approved',
            'total_amount' => 1000.00,
        ]);

        $this->actingAs($vendorUser, 'sanctum');

        $invoiceData = [
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => 850.00,
            'tax_amount' => 150.00,
            'total_amount' => 1000.00,
            'currency_code' => 'USD',
            'items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 850.00,
                    'tax_amount' => 150.00,
                    'line_total' => 1000.00,
                ]
            ]
        ];

        $response = $this->postJson('/api/vendor-portal/invoices', $invoiceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'invoice_number',
                'total_amount',
                'items',
            ]);
    }

    /** @test */
    public function vendor_cannot_submit_invoice_for_other_vendors_po()
    {
        $vendor1 = Vendor::factory()->create(['status' => 'active']);
        $vendor2 = Vendor::factory()->create(['status' => 'active']);

        $vendorUser = VendorUser::factory()->create([
            'vendor_id' => $vendor1->id,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor2->id, // Different vendor
            'status' => 'approved',
        ]);

        $this->actingAs($vendorUser, 'sanctum');

        $invoiceData = [
            'purchase_order_id' => $po->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => 850.00,
            'tax_amount' => 150.00,
            'total_amount' => 1000.00,
            'currency_code' => 'USD',
            'items' => []
        ];

        $response = $this->postJson('/api/vendor-portal/invoices', $invoiceData);

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_view_payment_status()
    {
        $vendor = Vendor::factory()->create(['status' => 'active']);
        $vendorUser = VendorUser::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        // Create some invoices with different payment statuses
        $po = PurchaseOrder::factory()->create(['vendor_id' => $vendor->id]);

        $po->invoices()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => 1000.00,
            'status' => 'matched',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($vendorUser, 'sanctum');

        $response = $this->getJson('/api/vendor-portal/payment-status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'total_amount',
                        'payment_status',
                        'due_date',
                    ]
                ]
            ]);
    }
}