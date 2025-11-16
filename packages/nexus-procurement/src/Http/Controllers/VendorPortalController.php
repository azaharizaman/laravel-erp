<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\VendorInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Vendor Portal Controller
 *
 * API endpoints for vendor portal functionality.
 */
class VendorPortalController extends Controller
{
    /**
     * Get vendor dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $vendor = Auth::user()->vendor;

        $stats = [
            'active_pos' => $vendor->purchaseOrders()
                ->whereIn('status', ['approved', 'sent_to_vendor', 'partially_received'])
                ->count(),
            'pending_invoices' => $vendor->invoices()
                ->where('status', 'pending')
                ->count(),
            'total_po_value' => $vendor->purchaseOrders()
                ->where('created_at', '>=', now()->subYear())
                ->sum('total_amount'),
            'recent_activity' => $this->getRecentActivity($vendor),
        ];

        return response()->json($stats);
    }

    /**
     * Get vendor's purchase orders.
     */
    public function purchaseOrders(Request $request): JsonResponse
    {
        $vendor = Auth::user()->vendor;

        $query = $vendor->purchaseOrders()->with(['items', 'requisition']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        $purchaseOrders = $query->orderBy('order_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($purchaseOrders);
    }

    /**
     * Get specific purchase order details.
     */
    public function purchaseOrder(PurchaseOrder $purchaseOrder): JsonResponse
    {
        // Ensure PO belongs to authenticated vendor
        if ($purchaseOrder->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized access to purchase order');
        }

        return response()->json($purchaseOrder->load([
            'items',
            'requisition',
            'goodsReceipts.items',
            'invoices.items'
        ]));
    }

    /**
     * Get vendor's invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $vendor = Auth::user()->vendor;

        $query = $vendor->invoices()->with(['purchaseOrder', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($invoices);
    }

    /**
     * Submit a new invoice.
     */
    public function submitInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'invoice_number' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);

        // Ensure PO belongs to authenticated vendor
        if ($purchaseOrder->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized access to purchase order');
        }

        // Check if invoice already exists for this PO
        if ($purchaseOrder->invoices()->where('invoice_number', $validated['invoice_number'])->exists()) {
            return response()->json(['error' => 'Invoice number already exists for this purchase order'], 422);
        }

        $invoice = $purchaseOrder->invoices()->create([
            'vendor_id' => Auth::user()->vendor_id,
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'total_amount' => $validated['total_amount'],
            'currency_code' => $validated['currency_code'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create invoice items
        $invoice->items()->createMany($validated['items']);

        // Handle file attachments if provided
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Store attachment logic would go here
                // This would typically use Laravel's file storage
            }
        }

        return response()->json($invoice->load('items'), 201);
    }

    /**
     * Get payment status for vendor's invoices.
     */
    public function paymentStatus(Request $request): JsonResponse
    {
        $vendor = Auth::user()->vendor;

        $query = $vendor->invoices()
            ->with(['purchaseOrder'])
            ->select([
                'id',
                'invoice_number',
                'purchase_order_id',
                'total_amount',
                'status',
                'payment_status',
                'due_date',
                'created_at',
            ]);

        if ($request->has('status')) {
            $query->where('payment_status', $request->status);
        }

        $invoices = $query->orderBy('due_date', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json($invoices);
    }

    /**
     * Update vendor profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:vendor_users,email,' . Auth::id(),
        ]);

        $user = Auth::user();
        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Get recent activity for vendor dashboard.
     */
    private function getRecentActivity(Vendor $vendor): array
    {
        $activities = [];

        // Recent POs
        $recentPOs = $vendor->purchaseOrders()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($po) {
                return [
                    'type' => 'po_received',
                    'description' => "PO {$po->po_number} received",
                    'date' => $po->created_at,
                    'amount' => $po->total_amount,
                ];
            });

        // Recent invoices
        $recentInvoices = $vendor->invoices()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'type' => 'invoice_submitted',
                    'description' => "Invoice {$invoice->invoice_number} submitted",
                    'date' => $invoice->created_at,
                    'amount' => $invoice->total_amount,
                ];
            });

        $activities = $recentPOs->concat($recentInvoices)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();

        return $activities;
    }
}