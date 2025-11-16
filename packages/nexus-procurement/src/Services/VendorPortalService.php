<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Models\VendorUser;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\VendorInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Vendor Portal Service
 *
 * Business logic for vendor portal operations.
 */
class VendorPortalService
{
    /**
     * Create a new vendor user account.
     */
    public function createVendorUser(array $data): VendorUser
    {
        $tempPassword = Str::random(12);

        $user = VendorUser::create([
            'vendor_id' => $data['vendor_id'],
            'email' => $data['email'],
            'password' => Hash::make($tempPassword),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'is_primary_contact' => $data['is_primary_contact'] ?? false,
            'is_active' => true,
        ]);

        // Send invitation email
        $this->sendInvitationEmail($user, $tempPassword);

        return $user;
    }

    /**
     * Authenticate vendor user.
     */
    public function authenticate(string $email, string $password): ?VendorUser
    {
        $user = VendorUser::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        if (!$user->canAccessPortal()) {
            return null;
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        return $user;
    }

    /**
     * Get vendor dashboard statistics.
     */
    public function getDashboardStats(Vendor $vendor): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'active_pos' => $vendor->purchaseOrders()
                ->whereIn('status', ['approved', 'sent_to_vendor', 'partially_received'])
                ->count(),

            'pending_invoices' => $vendor->invoices()
                ->where('status', 'pending')
                ->count(),

            'overdue_invoices' => $vendor->invoices()
                ->where('due_date', '<', now())
                ->where('payment_status', '!=', 'paid')
                ->count(),

            'total_po_value_current_month' => $vendor->purchaseOrders()
                ->where('created_at', '>=', $currentMonth)
                ->sum('total_amount'),

            'total_po_value_last_month' => $vendor->purchaseOrders()
                ->whereBetween('created_at', [$lastMonth, $currentMonth])
                ->sum('total_amount'),

            'total_invoice_value_pending' => $vendor->invoices()
                ->where('payment_status', 'pending')
                ->sum('total_amount'),

            'average_payment_time' => $this->calculateAveragePaymentTime($vendor),

            'recent_activity' => $this->getRecentActivity($vendor, 10),
        ];
    }

    /**
     * Submit invoice for a purchase order.
     */
    public function submitInvoice(Vendor $vendor, PurchaseOrder $purchaseOrder, array $invoiceData): VendorInvoice
    {
        // Validate PO belongs to vendor
        if ($purchaseOrder->vendor_id !== $vendor->id) {
            throw new \InvalidArgumentException('Purchase order does not belong to this vendor');
        }

        // Check for duplicate invoice number
        if ($purchaseOrder->invoices()->where('invoice_number', $invoiceData['invoice_number'])->exists()) {
            throw new \InvalidArgumentException('Invoice number already exists for this purchase order');
        }

        // Create invoice
        $invoice = $purchaseOrder->invoices()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => $invoiceData['invoice_number'],
            'invoice_date' => $invoiceData['invoice_date'],
            'due_date' => $invoiceData['due_date'],
            'subtotal' => $invoiceData['subtotal'],
            'tax_amount' => $invoiceData['tax_amount'],
            'total_amount' => $invoiceData['total_amount'],
            'currency_code' => $invoiceData['currency_code'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'notes' => $invoiceData['notes'] ?? null,
        ]);

        // Create invoice items
        if (isset($invoiceData['items'])) {
            $invoice->items()->createMany($invoiceData['items']);
        }

        // Trigger 3-way match process
        $this->triggerThreeWayMatch($invoice);

        return $invoice;
    }

    /**
     * Get recent activity for vendor.
     */
    public function getRecentActivity(Vendor $vendor, int $limit = 10): Collection
    {
        $activities = collect();

        // Recent POs
        $recentPOs = $vendor->purchaseOrders()
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($po) {
                return (object) [
                    'type' => 'po_received',
                    'title' => 'Purchase Order Received',
                    'description' => "PO {$po->po_number} received",
                    'date' => $po->created_at,
                    'amount' => $po->total_amount,
                    'reference' => $po->po_number,
                ];
            });

        // Recent invoices
        $recentInvoices = $vendor->invoices()
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($invoice) {
                return (object) [
                    'type' => 'invoice_submitted',
                    'title' => 'Invoice Submitted',
                    'description' => "Invoice {$invoice->invoice_number} submitted",
                    'date' => $invoice->created_at,
                    'amount' => $invoice->total_amount,
                    'reference' => $invoice->invoice_number,
                ];
            });

        // Recent payments
        $recentPayments = $vendor->invoices()
            ->whereNotNull('payment_authorized_at')
            ->latest('payment_authorized_at')
            ->take($limit)
            ->get()
            ->map(function ($invoice) {
                return (object) [
                    'type' => 'payment_processed',
                    'title' => 'Payment Processed',
                    'description' => "Payment processed for invoice {$invoice->invoice_number}",
                    'date' => $invoice->payment_authorized_at,
                    'amount' => $invoice->total_amount,
                    'reference' => $invoice->invoice_number,
                ];
            });

        return $activities->concat($recentPOs)
            ->concat($recentInvoices)
            ->concat($recentPayments)
            ->sortByDesc('date')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate average payment time for vendor.
     */
    private function calculateAveragePaymentTime(Vendor $vendor): ?float
    {
        $paidInvoices = $vendor->invoices()
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_authorized_at')
            ->get();

        if ($paidInvoices->isEmpty()) {
            return null;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($paidInvoices as $invoice) {
            $paymentDays = $invoice->invoice_date->diffInDays($invoice->payment_authorized_at);
            $totalDays += $paymentDays;
            $count++;
        }

        return $count > 0 ? round($totalDays / $count, 1) : null;
    }

    /**
     * Trigger 3-way match process for invoice.
     */
    private function triggerThreeWayMatch(VendorInvoice $invoice): void
    {
        // This would integrate with the ThreeWayMatchService
        // For now, just mark as pending match
        $invoice->update(['match_status' => 'pending']);
    }

    /**
     * Send invitation email to vendor user.
     */
    private function sendInvitationEmail(VendorUser $user, string $tempPassword): void
    {
        // Implementation would send email notification
        // $user->notify(new VendorPortalInvitation($tempPassword));
    }

    /**
     * Get vendor's payment status summary.
     */
    public function getPaymentStatusSummary(Vendor $vendor): array
    {
        $invoices = $vendor->invoices()->get();

        return [
            'total_pending' => $invoices->where('payment_status', 'pending')->count(),
            'total_overdue' => $invoices->filter(function ($invoice) {
                return $invoice->payment_status !== 'paid' && $invoice->due_date < now();
            })->count(),
            'total_paid' => $invoices->where('payment_status', 'paid')->count(),
            'total_authorized' => $invoices->where('payment_status', 'authorized')->count(),
            'pending_amount' => $invoices->where('payment_status', 'pending')->sum('total_amount'),
            'overdue_amount' => $invoices->filter(function ($invoice) {
                return $invoice->payment_status !== 'paid' && $invoice->due_date < now();
            })->sum('total_amount'),
        ];
    }
}