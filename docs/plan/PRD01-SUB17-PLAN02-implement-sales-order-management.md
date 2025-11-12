---
plan: Sales Order Management & Processing
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, sales, order-management, approval-workflow, back-orders, business-logic, revenue-management]
---

# PRD01-SUB17-PLAN02: Implement Sales Order Management & Processing

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers sales order creation and management, approval workflows, order status tracking, back order handling, credit limit enforcement, and quotation-to-order conversion. This plan builds on PLAN01's customer and quotation foundation to complete the order entry and processing cycle.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-SD-002**: Convert quotations to sales orders with approval workflow
- **FR-SD-006**: Track sales order status (draft, confirmed, partial, fulfilled, invoiced, closed)
- **FR-SD-007**: Support back orders for out-of-stock items with automatic fulfillment on restock

### Business Rules
- **BR-SD-001**: Orders cannot exceed customer credit limit without management override
- **BR-SD-002**: Confirmed orders cannot be modified; require change order or cancellation
- **BR-SD-003**: Fulfilled quantity cannot exceed ordered quantity without authorization

### Data Requirements
- **DR-SD-001**: Store complete order history including revisions and approvals

### Integration Requirements
- **IR-SD-001**: Integrate with Inventory for stock reservation and fulfillment
- **IR-SD-002**: Integrate with Accounts Receivable for automatic invoice generation
- **IR-SD-003**: Integrate with Backoffice for customer credit limit checking

### Performance Requirements
- **PR-SD-001**: Order processing (including stock allocation) must complete in < 2 seconds for orders with < 100 items

### Architecture Requirements
- **ARCH-SD-001**: Use optimistic locking for concurrent order modifications

### Event Requirements
- **EV-SD-001**: SalesOrderCreatedEvent when order is created
- **EV-SD-002**: SalesOrderConfirmedEvent when order receives approval
- **EV-SD-004**: CustomerCreditLimitExceededEvent when order exceeds credit limit

### Constraints
- **CON-001**: Depends on PLAN01 (Customer and Quotation foundation)
- **CON-002**: Must integrate with SUB14 (Inventory Management) for stock reservation
- **CON-003**: Must integrate with SUB15 (Backoffice) for credit limit validation
- **CON-004**: Must prevent concurrent order modifications using optimistic locking

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Implement pessimistic locking for stock reservation
- **GUD-004**: Log all order status changes with user and timestamp

### Patterns
- **PAT-001**: State machine pattern for order status transitions
- **PAT-002**: Observer pattern for automatic order actions
- **PAT-003**: Strategy pattern for approval rules
- **PAT-004**: Queue jobs for async invoice generation

## 2. Implementation Steps

### GOAL-001: Sales Order Foundation & Creation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-002 | Implement sales order creation from quotations and direct entry | | |
| BR-SD-001 | Enforce credit limit checking before order confirmation | | |
| DR-SD-001 | Store complete order history | | |
| ARCH-SD-001 | Implement optimistic locking | | |
| EV-SD-001 | Dispatch SalesOrderCreatedEvent | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000006_create_sales_orders_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), order_number (VARCHAR 100 unique per tenant), order_date (DATE), customer_id (BIGINT FK customers), quotation_id (BIGINT nullable FK sales_quotations), delivery_address (TEXT nullable), requested_delivery_date (DATE nullable), payment_terms (VARCHAR 100), currency_code (VARCHAR 10), subtotal (DECIMAL 15,2 default 0), discount_amount (DECIMAL 15,2 default 0), tax_amount (DECIMAL 15,2 default 0), total_amount (DECIMAL 15,2 default 0), status (VARCHAR 20 default 'draft': draft/confirmed/partial/fulfilled/invoiced/closed/cancelled), approved_by (BIGINT nullable FK users), approved_at (TIMESTAMP nullable), notes (TEXT nullable), created_by (BIGINT FK users), timestamps, soft deletes, version (INT default 1 for optimistic locking); indexes: tenant_id, order_number, customer_id, status, order_date, quotation_id | | |
| TASK-002 | Create migration `2025_01_01_000007_create_sales_order_lines_table.php` with columns: id (BIGSERIAL), order_id (BIGINT FK sales_orders cascade), line_number (INT), item_id (BIGINT nullable FK inventory_items), item_description (TEXT), quantity (DECIMAL 15,4), fulfilled_quantity (DECIMAL 15,4 default 0), reserved_quantity (DECIMAL 15,4 default 0), uom_id (BIGINT FK uoms), unit_price (DECIMAL 15,2), discount_percent (DECIMAL 5,2 default 0), discount_amount (DECIMAL 15,2 default 0), line_total (DECIMAL 15,2), requested_delivery_date (DATE nullable), warehouse_id (BIGINT nullable FK warehouses), timestamps; indexes: order_id, item_id, warehouse_id | | |
| TASK-003 | Create enum `SalesOrderStatus` with values: DRAFT, CONFIRMED, PARTIAL, FULFILLED, INVOICED, CLOSED, CANCELLED; define allowed transitions: DRAFT→CONFIRMED, CONFIRMED→PARTIAL, PARTIAL→FULFILLED, FULFILLED→INVOICED, INVOICED→CLOSED, any→CANCELLED | | |
| TASK-004 | Create model `SalesOrder.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: order_number, order_date, customer_id, quotation_id, delivery_address, requested_delivery_date, payment_terms, currency_code, notes; casts: order_date → date, requested_delivery_date → date, status → SalesOrderStatus enum, subtotal → float, total_amount → float, approved_at → datetime, version → integer; relationships: customer (belongsTo), quotation (belongsTo), lines (hasMany SalesOrderLine), approvedBy (belongsTo User), createdBy (belongsTo User), deliveryNotes (hasMany), arInvoices (hasMany); scopes: byStatus(SalesOrderStatus $status), pendingApproval(), active(); computed: is_fully_fulfilled, is_partially_fulfilled, can_be_modified (status === DRAFT), requires_approval (total_amount > threshold) | | |
| TASK-005 | Create model `SalesOrderLine.php` with fillable: line_number, item_id, item_description, quantity, uom_id, unit_price, discount_percent, requested_delivery_date, warehouse_id; casts: quantity → float, fulfilled_quantity → float, reserved_quantity → float, unit_price → float, line_total → float; relationships: order (belongsTo), item (belongsTo InventoryItem), uom (belongsTo), warehouse (belongsTo); computed: remaining_quantity (quantity - fulfilled_quantity), is_fully_fulfilled (fulfilled_quantity >= quantity), is_back_ordered (reserved_quantity < quantity) | | |
| TASK-006 | Create factory `SalesOrderFactory.php` with states: draft(), confirmed(), withQuotation(SalesQuotation $quotation), withLines(int $count = 3), pendingApproval(), cancelled() | | |
| TASK-007 | Create factory `SalesOrderLineFactory.php` with states: fullyFulfilled(), partiallyFulfilled(), backOrdered() | | |
| TASK-008 | Create contract `SalesOrderRepositoryContract.php` with methods: findById(int $id): ?SalesOrder, findByNumber(string $number, string $tenantId): ?SalesOrder, create(array $data): SalesOrder, update(SalesOrder $order, array $data): SalesOrder, updateWithLock(SalesOrder $order, array $data): SalesOrder (uses version for optimistic locking), delete(SalesOrder $order): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getByCustomer(int $customerId, array $filters = []): Collection, getPendingOrders(): Collection, getOpenOrders(): Collection | | |
| TASK-009 | Implement `SalesOrderRepository.php` with eager loading for customer, lines, items; implement filters: status, customer_id, date_range, requires_approval; implement updateWithLock() to check version field and throw StaleRecordException if changed | | |
| TASK-010 | Create service `OrderCalculationService.php` with methods: calculateLineTotal(array $lineData): float, calculateSubtotal(SalesOrder $order): float, calculateTotal(SalesOrder $order): float, recalculateOrder(SalesOrder $order): void (updates all totals), copyPricingFromQuotation(SalesQuotation $quotation): array | | |
| TASK-011 | Create action `CreateSalesOrderAction.php` using AsAction; inject SalesOrderRepositoryContract, CreditCheckService, OrderCalculationService, ActivityLoggerContract; validate customer exists and active; check credit limit availability (throw CustomerCreditLimitExceededEvent if exceeded without override); generate order_number; create order header; create order lines; calculate totals; log activity "Sales order created"; dispatch SalesOrderCreatedEvent; return SalesOrder | | |
| TASK-012 | Create action `ConvertQuotationToOrderAction.php`; validate quotation status is ACCEPTED; validate quotation not expired; copy customer, lines, pricing from quotation; create sales order via CreateSalesOrderAction; update quotation status to CONVERTED; link order to quotation; log activity "Quotation converted to order"; dispatch QuotationConvertedEvent; return SalesOrder | | |
| TASK-013 | Create action `UpdateSalesOrderAction.php`; validate order status is DRAFT (BR-SD-002); validate version for optimistic locking; update order using updateWithLock(); recalculate totals; log activity "Sales order updated" with changes; dispatch SalesOrderUpdatedEvent | | |
| TASK-014 | Create event `SalesOrderCreatedEvent` with properties: SalesOrder $order, Customer $customer, User $createdBy | | |
| TASK-015 | Create event `SalesOrderUpdatedEvent` with properties: SalesOrder $order, array $changes, User $updatedBy | | |
| TASK-016 | Create event `QuotationConvertedEvent` with properties: SalesQuotation $quotation, SalesOrder $order, User $convertedBy | | |
| TASK-017 | Create observer `SalesOrderObserver.php` with creating() to generate order_number; updating() to recalculate totals when lines change; deleting() to prevent deletion of non-draft orders | | |
| TASK-018 | Create policy `SalesOrderPolicy.php` requiring 'manage-sales-orders' permission; enforce tenant scope; validate status-based permissions (only DRAFT can be modified) | | |
| TASK-019 | Create API controller `SalesOrderController.php` with routes: index (GET /sales/orders), store (POST /sales/orders), show (GET /sales/orders/{id}), update (PATCH /sales/orders/{id}), destroy (DELETE /sales/orders/{id}), convertFromQuotation (POST /sales/quotations/{id}/convert); authorize actions; inject SalesOrderRepositoryContract | | |
| TASK-020 | Create form request `StoreSalesOrderRequest.php` with validation: customer_id (required, exists:customers), order_date (required, date), requested_delivery_date (nullable, date, after_or_equal:order_date), payment_terms (required), lines (required, array, min:1), lines.*.item_id (nullable, exists:inventory_items), lines.*.quantity (required, numeric, min:0.0001), lines.*.unit_price (required, numeric, min:0), lines.*.warehouse_id (nullable, exists:warehouses) | | |
| TASK-021 | Create form request `UpdateSalesOrderRequest.php` extending StoreSalesOrderRequest; add status validation (must be DRAFT) | | |
| TASK-022 | Create API resource `SalesOrderResource.php` with fields: id, order_number, order_date, customer (nested), quotation_reference, lines (nested collection), subtotal, discount_amount, tax_amount, total_amount, status, requires_approval, is_fully_fulfilled, approved_by, approved_at, version, created_at | | |
| TASK-023 | Create API resource `SalesOrderLineResource.php` with fields: line_number, item, item_description, quantity, fulfilled_quantity, remaining_quantity, reserved_quantity, is_back_ordered, uom, unit_price, line_total, warehouse | | |
| TASK-024 | Write unit tests for SalesOrder model: test status transitions, test is_fully_fulfilled logic, test can_be_modified based on status, test relationships | | |
| TASK-025 | Write unit tests for OrderCalculationService: test line total, subtotal, total calculations; test pricing copy from quotation | | |
| TASK-026 | Write feature tests for SalesOrderController: test create order directly, test convert quotation to order, test cannot update confirmed order (BR-SD-002), test authorization checks | | |

### GOAL-002: Order Approval Workflow

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-002 | Implement approval workflow for sales orders | | |
| BR-SD-001 | Enforce credit limit with management override capability | | |
| EV-SD-002 | Dispatch SalesOrderConfirmedEvent on approval | | |
| EV-SD-004 | Dispatch CustomerCreditLimitExceededEvent when limit exceeded | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create migration `2025_01_01_000008_create_order_approvals_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), order_id (BIGINT FK sales_orders), approval_level (INT), approver_id (BIGINT FK users), status (VARCHAR 20: pending/approved/rejected), comments (TEXT nullable), approved_at (TIMESTAMP nullable), timestamps; indexes: tenant_id, order_id, approver_id, status | | |
| TASK-028 | Create enum `ApprovalStatus` with values: PENDING, APPROVED, REJECTED | | |
| TASK-029 | Create model `OrderApproval.php` with traits: BelongsToTenant; fillable: order_id, approval_level, approver_id, status, comments; casts: status → ApprovalStatus enum, approved_at → datetime; relationships: order (belongsTo), approver (belongsTo User); scopes: pending(), approved(), forOrder(int $orderId) | | |
| TASK-030 | Create factory `OrderApprovalFactory.php` with states: pending(), approved(), rejected() | | |
| TASK-031 | Create service `OrderApprovalService.php` with methods: requiresApproval(SalesOrder $order): bool (based on total_amount threshold), getApprovers(SalesOrder $order): Collection (get users with approval permission), createApprovalRequest(SalesOrder $order): void (create approval records), canApprove(User $user, SalesOrder $order): bool, isPendingApproval(SalesOrder $order): bool, isFullyApproved(SalesOrder $order): bool | | |
| TASK-032 | Create action `SubmitOrderForApprovalAction.php`; validate order status is DRAFT; validate order has lines; validate customer credit available or override requested; check if requiresApproval(); if yes: create approval requests via OrderApprovalService, update status to PENDING_APPROVAL, log activity "Order submitted for approval"; if no: auto-confirm via ConfirmSalesOrderAction; dispatch OrderSubmittedForApprovalEvent | | |
| TASK-033 | Create action `ApproveSalesOrderAction.php`; validate user is approver; validate approval status PENDING; validate order still in PENDING_APPROVAL; update approval status to APPROVED with timestamp; if all approvals complete: confirm order via ConfirmSalesOrderAction; log activity "Order approved by {user}"; dispatch OrderApprovedEvent | | |
| TASK-034 | Create action `RejectSalesOrderAction.php`; validate user is approver; update approval status to REJECTED with comments; update order status to DRAFT; log activity "Order rejected by {user}: {reason}"; dispatch OrderRejectedEvent; notify order creator | | |
| TASK-035 | Create action `ConfirmSalesOrderAction.php`; validate order has approval (if required); validate credit limit; update status to CONFIRMED; set approved_by and approved_at; create stock reservation (integrate with Inventory); log activity "Order confirmed"; dispatch SalesOrderConfirmedEvent; return SalesOrder | | |
| TASK-036 | Create action `OverrideCreditLimitAction.php`; validate user has 'override-credit-limit' permission; validate override reason provided; create audit record for override; allow order confirmation to proceed; log activity "Credit limit overridden: {reason}"; dispatch CreditLimitOverriddenEvent | | |
| TASK-037 | Create event `OrderSubmittedForApprovalEvent` with properties: SalesOrder $order, Collection $approvers | | |
| TASK-038 | Create event `OrderApprovedEvent` with properties: SalesOrder $order, OrderApproval $approval, User $approver | | |
| TASK-039 | Create event `OrderRejectedEvent` with properties: SalesOrder $order, OrderApproval $approval, User $rejector, string $reason | | |
| TASK-040 | Create event `SalesOrderConfirmedEvent` with properties: SalesOrder $order, User $confirmedBy, bool $creditLimitOverridden | | |
| TASK-041 | Create event `CustomerCreditLimitExceededEvent` with properties: Customer $customer, SalesOrder $order, float $creditLimit, float $proposedAmount, float $exceededBy | | |
| TASK-042 | Create event `CreditLimitOverriddenEvent` with properties: Customer $customer, SalesOrder $order, User $overriddenBy, string $reason | | |
| TASK-043 | Create listener `CheckCreditLimitListener.php` listening to SalesOrderCreatedEvent; calculate total outstanding including new order; if exceeds credit limit: dispatch CustomerCreditLimitExceededEvent, flag order as REQUIRES_APPROVAL | | |
| TASK-044 | Create listener `NotifyApproversListener.php` listening to OrderSubmittedForApprovalEvent; send notification to all approvers; create activity log entries | | |
| TASK-045 | Create API controller routes in SalesOrderController: submitForApproval (POST /orders/{id}/submit-approval), approve (POST /orders/{id}/approve), reject (POST /orders/{id}/reject), confirm (POST /orders/{id}/confirm), overrideCreditLimit (POST /orders/{id}/override-credit) | | |
| TASK-046 | Create form request `ApproveOrderRequest.php` with validation: comments (nullable, string, max:500) | | |
| TASK-047 | Create form request `RejectOrderRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-048 | Create form request `OverrideCreditLimitRequest.php` with validation: reason (required, string, max:500), authorized_by (required, string) | | |
| TASK-049 | Create API resource `OrderApprovalResource.php` with fields: approval_level, approver (nested UserResource), status, comments, approved_at | | |
| TASK-050 | Write unit tests for OrderApprovalService: test requiresApproval logic with various amounts, test getApprovers returns correct users, test isPendingApproval status checks | | |
| TASK-051 | Write feature tests for approval workflow: test submit→approve→confirm cycle, test multiple approvers, test rejection returns to draft, test credit limit override, test cannot approve without permission | | |

### GOAL-003: Stock Reservation & Back Order Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-007 | Support back orders with automatic fulfillment on restock | | |
| IR-SD-001 | Integrate with Inventory for stock reservation | | |
| PR-SD-001 | Order processing < 2 seconds for < 100 items | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Create migration `2025_01_01_000009_create_back_orders_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), order_line_id (BIGINT FK sales_order_lines), item_id (BIGINT FK inventory_items), quantity (DECIMAL 15,4), reserved_quantity (DECIMAL 15,4 default 0), status (VARCHAR 20: pending/partial/fulfilled), warehouse_id (BIGINT FK warehouses), created_at (TIMESTAMP), fulfilled_at (TIMESTAMP nullable); indexes: tenant_id, order_line_id, item_id, status, warehouse_id | | |
| TASK-053 | Create enum `BackOrderStatus` with values: PENDING, PARTIAL, FULFILLED | | |
| TASK-054 | Create model `BackOrder.php` with traits: BelongsToTenant; fillable: order_line_id, item_id, quantity, reserved_quantity, status, warehouse_id; casts: quantity → float, reserved_quantity → float, status → BackOrderStatus enum, fulfilled_at → datetime; relationships: orderLine (belongsTo), item (belongsTo), warehouse (belongsTo); computed: remaining_quantity (quantity - reserved_quantity), is_fulfilled (status === FULFILLED); scopes: pending(), byItem(int $itemId), byWarehouse(int $warehouseId) | | |
| TASK-055 | Create factory `BackOrderFactory.php` with states: pending(), partial(), fulfilled() | | |
| TASK-056 | Create contract `StockReservationServiceContract.php` (in Inventory package) with methods: checkAvailability(int $itemId, float $quantity, int $warehouseId): bool, reserveStock(SalesOrderLine $line): bool, releaseReservation(SalesOrderLine $line): bool, getAvailableQuantity(int $itemId, int $warehouseId): float | | |
| TASK-057 | Create service `BackOrderService.php` with methods: createBackOrder(SalesOrderLine $line, float $backOrderQuantity): BackOrder, checkPendingBackOrders(int $itemId, int $warehouseId): Collection, fulfillBackOrders(int $itemId, int $warehouseId, float $availableQuantity): int (returns number fulfilled), getBackOrdersByCustomer(int $customerId): Collection | | |
| TASK-058 | Create action `ReserveStockForOrderAction.php`; inject StockReservationServiceContract, BackOrderService; for each order line: check stock availability; if available: reserve via StockReservationService, update reserved_quantity; if insufficient: create back order via BackOrderService; log activity "Stock reserved" or "Back order created"; dispatch StockReservedEvent or BackOrderCreatedEvent; measure execution time (must be < 2s for < 100 items per PR-SD-001) | | |
| TASK-059 | Create action `FulfillBackOrdersAction.php` using AsAction with asJob(); listen to StockReplenishedEvent from Inventory; get pending back orders for item/warehouse; calculate available quantity; fulfill back orders in FIFO order; update order line reserved_quantity; update back order status; log activity "Back order fulfilled"; dispatch BackOrderFulfilledEvent | | |
| TASK-060 | Create action `ReleaseStockReservationAction.php`; inject StockReservationServiceContract; release reservation when order cancelled; update reserved_quantity to 0; log activity "Stock reservation released" | | |
| TASK-061 | Create event `StockReservedEvent` with properties: SalesOrderLine $line, float $reservedQuantity, int $warehouseId | | |
| TASK-062 | Create event `BackOrderCreatedEvent` with properties: BackOrder $backOrder, SalesOrderLine $line, float $backOrderQuantity | | |
| TASK-063 | Create event `BackOrderFulfilledEvent` with properties: BackOrder $backOrder, SalesOrderLine $line, float $fulfilledQuantity | | |
| TASK-064 | Create listener `ReserveStockOnConfirmationListener.php` listening to SalesOrderConfirmedEvent; dispatch ReserveStockForOrderAction->asJob() for async processing | | |
| TASK-065 | Create listener `FulfillBackOrdersListener.php` listening to StockReplenishedEvent (from SUB14); dispatch FulfillBackOrdersAction->asJob() with item_id and warehouse_id | | |
| TASK-066 | Create API controller routes in SalesOrderController: backOrders (GET /orders/{id}/back-orders), reserveStock (POST /orders/{id}/reserve-stock), releaseStock (POST /orders/{id}/release-stock) | | |
| TASK-067 | Create API resource `BackOrderResource.php` with fields: id, order_line, item, quantity, reserved_quantity, remaining_quantity, status, warehouse, created_at, fulfilled_at | | |
| TASK-068 | Update SalesOrderResource to include: back_orders_count, fully_reserved (all lines have reserved_quantity >= quantity) | | |
| TASK-069 | Write unit tests for BackOrderService: test createBackOrder, test fulfillBackOrders with FIFO logic, test multiple back orders for same item | | |
| TASK-070 | Write integration tests for stock reservation: test successful reservation updates inventory (mocked), test back order creation when out of stock, test automatic fulfillment on restock | | |
| TASK-071 | Write performance test: create order with 100 items, test stock reservation completes in < 2 seconds (PR-SD-001) | | |

### GOAL-004: Order Status Management & Cancellation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-006 | Track sales order status throughout lifecycle | | |
| BR-SD-002 | Confirmed orders cannot be modified | | |
| DR-SD-001 | Store complete order history with status changes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-072 | Create migration `2025_01_01_000010_create_order_status_history_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), order_id (BIGINT FK sales_orders), old_status (VARCHAR 20), new_status (VARCHAR 20), changed_by (BIGINT FK users), reason (TEXT nullable), changed_at (TIMESTAMP), metadata JSONB (store additional context); indexes: tenant_id, order_id, changed_at | | |
| TASK-073 | Create model `OrderStatusHistory.php` with traits: BelongsToTenant; fillable: order_id, old_status, new_status, changed_by, reason, metadata; casts: old_status → SalesOrderStatus enum, new_status → SalesOrderStatus enum, changed_at → datetime, metadata → array; relationships: order (belongsTo), changedBy (belongsTo User) | | |
| TASK-074 | Create factory `OrderStatusHistoryFactory.php` | | |
| TASK-075 | Create service `OrderStatusService.php` with methods: canTransition(SalesOrderStatus $from, SalesOrderStatus $to): bool (validate allowed transitions), recordStatusChange(SalesOrder $order, SalesOrderStatus $oldStatus, SalesOrderStatus $newStatus, ?string $reason = null): void, getStatusHistory(SalesOrder $order): Collection, validateOrderForStatusChange(SalesOrder $order, SalesOrderStatus $newStatus): void (throw exception if invalid) | | |
| TASK-076 | Create action `CancelSalesOrderAction.php`; validate order not yet invoiced (INVOICED, CLOSED cannot be cancelled); validate cancellation reason provided; release stock reservations via ReleaseStockReservationAction; update status to CANCELLED; record status history; log activity "Order cancelled: {reason}"; dispatch SalesOrderCancelledEvent; return SalesOrder | | |
| TASK-077 | Create action `CloseSalesOrderAction.php`; validate order status is INVOICED; validate all invoices paid (check with AR module); update status to CLOSED; record status history; log activity "Order closed"; dispatch SalesOrderClosedEvent | | |
| TASK-078 | Create event `SalesOrderCancelledEvent` with properties: SalesOrder $order, User $cancelledBy, string $reason | | |
| TASK-079 | Create event `SalesOrderClosedEvent` with properties: SalesOrder $order, User $closedBy | | |
| TASK-080 | Update SalesOrderObserver with updated() method: if status changed, record in OrderStatusHistory via OrderStatusService; validate transition allowed; prevent status change if not allowed | | |
| TASK-081 | Create API controller routes in SalesOrderController: cancel (POST /orders/{id}/cancel), close (POST /orders/{id}/close), statusHistory (GET /orders/{id}/status-history) | | |
| TASK-082 | Create form request `CancelOrderRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-083 | Create API resource `OrderStatusHistoryResource.php` with fields: old_status, new_status, changed_by (nested UserResource), reason, changed_at, metadata | | |
| TASK-084 | Update SalesOrderResource to include: status_history (collection of OrderStatusHistoryResource), can_be_cancelled, can_be_closed | | |
| TASK-085 | Write unit tests for OrderStatusService: test canTransition with all valid/invalid transitions, test recordStatusChange creates history record | | |
| TASK-086 | Write feature tests for order lifecycle: test DRAFT→CONFIRMED→PARTIAL→FULFILLED→INVOICED→CLOSED, test cannot skip statuses, test cancellation from various statuses, test status history recorded | | |

### GOAL-005: Integration, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-SD-002 | Integrate with Accounts Receivable for invoice generation | | |
| PR-SD-001 | Order processing < 2 seconds for < 100 items | | |
| ARCH-SD-001 | Optimistic locking for concurrent modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-087 | Create listener `GenerateInvoiceListener.php` listening to OrderFulfilledEvent (from PLAN03); when order fully fulfilled: dispatch GenerateARInvoiceJob->asJob() with order_id; update order status to INVOICED when invoice created | | |
| TASK-088 | Create listener `UpdateOrderStatusOnInvoiceListener.php` listening to InvoiceCreatedEvent (from SUB12); update order status to INVOICED; record status history | | |
| TASK-089 | Create listener `CloseOrderOnPaymentListener.php` listening to InvoiceFullyPaidEvent (from SUB12); dispatch CloseSalesOrderAction with order | | |
| TASK-090 | Create job `GenerateARInvoiceJob.php` using AsJob; inject ARInvoiceRepositoryContract (from SUB12); get order details; create AR invoice header; create invoice lines from order lines; link invoice to order; dispatch InvoiceGeneratedFromOrderEvent | | |
| TASK-091 | Create event `InvoiceGeneratedFromOrderEvent` with properties: SalesOrder $order, ARInvoice $invoice | | |
| TASK-092 | Write comprehensive unit tests for all models: test SalesOrder status transitions, test OrderApproval workflow, test BackOrder fulfillment logic | | |
| TASK-093 | Write comprehensive unit tests for all services: test OrderApprovalService approval logic, test BackOrderService fulfillment FIFO, test OrderStatusService transition validation | | |
| TASK-094 | Write comprehensive unit tests for all actions: test CreateSalesOrderAction with credit check, test ConvertQuotationToOrderAction, test approval workflow actions, test stock reservation actions | | |
| TASK-095 | Write feature tests for complete order workflows: test quotation→order→approval→confirmation→stock reservation→fulfillment→invoice→close; verify all status changes, all events dispatched | | |
| TASK-096 | Write integration tests: test credit limit checking with CreditCheckService, test stock reservation with StockReservationServiceContract (mocked), test invoice generation with AR module (mocked) | | |
| TASK-097 | Write performance test: create order with 100 items, test complete processing (validation, creation, calculation, stock reservation) completes in < 2 seconds (PR-SD-001); test with concurrent orders | | |
| TASK-098 | Write optimistic locking test: simulate concurrent order modifications, verify StaleRecordException thrown, verify version field incremented correctly (ARCH-SD-001) | | |
| TASK-099 | Write security tests: test users can only access orders in their tenant, test approval permissions enforced, test credit limit override requires permission | | |
| TASK-100 | Set up Pest configuration for sales order tests; configure database transactions, tenant seeding | | |
| TASK-101 | Achieve minimum 80% code coverage for order management; run `./vendor/bin/pest --coverage`; add tests for uncovered paths | | |
| TASK-102 | Create API documentation for order endpoints: document approval workflow, document status transitions, document back order handling, include sequence diagrams | | |
| TASK-103 | Create user guide: order creation from quotation, approval workflow guide, handling back orders, cancellation procedures | | |
| TASK-104 | Update sales package README with order management features, configuration options, integration points | | |
| TASK-105 | Validate all acceptance criteria: order creation working, approval workflow functional, back orders handled correctly, credit limits enforced, status tracking accurate | | |
| TASK-106 | Conduct code review: verify all business rules implemented (BR-SD-001, BR-SD-002, BR-SD-003), verify performance requirements met (PR-SD-001), verify optimistic locking works (ARCH-SD-001) | | |
| TASK-107 | Run full test suite for order management; verify all tests pass; fix flaky tests; ensure consistent results | | |
| TASK-108 | Deploy to staging; test complete order lifecycle end-to-end; test approval workflow with multiple approvers; test back order fulfillment; verify performance with large orders | | |

## 3. Alternatives

- **ALT-001**: Use database transactions instead of optimistic locking - rejected as concurrent modifications are expected in multi-user environment
- **ALT-002**: Store status history in JSON field on orders table - rejected for better query performance and separation of concerns (DR-SD-001)
- **ALT-003**: Synchronous invoice generation on order fulfillment - rejected due to potential performance impact; using async job queue
- **ALT-004**: Hard approval thresholds instead of configurable - rejected for flexibility across different tenants
- **ALT-005**: FIFO back order fulfillment - accepted and implemented

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: PLAN01 (Customer & Quotation Management) - Customer and SalesQuotation models
- **DEP-002**: SUB14 (Inventory Management) - InventoryItem model, StockReservationServiceContract
- **DEP-003**: SUB15 (Backoffice) - Credit limit data (via Customer from PLAN01)
- **DEP-004**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking
- **DEP-005**: SUB04 (Serial Numbering) - Order number generation

### Optional Dependencies
- **DEP-006**: SUB12 (Accounts Receivable) - ARInvoice generation (can be implemented later)
- **DEP-007**: SUB22 (Notifications) - Email notifications for approvals

### Package Dependencies
- **DEP-008**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-009**: Laravel Queue system - Async job processing

## 5. Files

### Models & Enums
- `packages/sales/src/Models/SalesOrder.php` - Sales order header model
- `packages/sales/src/Models/SalesOrderLine.php` - Order line items model
- `packages/sales/src/Models/OrderApproval.php` - Approval workflow model
- `packages/sales/src/Models/BackOrder.php` - Back order tracking model
- `packages/sales/src/Models/OrderStatusHistory.php` - Status change history model
- `packages/sales/src/Enums/SalesOrderStatus.php` - Order status enumeration
- `packages/sales/src/Enums/ApprovalStatus.php` - Approval status enumeration
- `packages/sales/src/Enums/BackOrderStatus.php` - Back order status enumeration

### Repositories & Contracts
- `packages/sales/src/Contracts/SalesOrderRepositoryContract.php` - Order repository interface
- `packages/sales/src/Repositories/SalesOrderRepository.php` - Order repository implementation
- `packages/sales/src/Contracts/StockReservationServiceContract.php` - Stock reservation interface (from Inventory)

### Services
- `packages/sales/src/Services/OrderCalculationService.php` - Order total calculations
- `packages/sales/src/Services/OrderApprovalService.php` - Approval workflow logic
- `packages/sales/src/Services/BackOrderService.php` - Back order management
- `packages/sales/src/Services/OrderStatusService.php` - Status transition validation

### Actions
- `packages/sales/src/Actions/CreateSalesOrderAction.php` - Create order
- `packages/sales/src/Actions/ConvertQuotationToOrderAction.php` - Convert quotation
- `packages/sales/src/Actions/UpdateSalesOrderAction.php` - Update order
- `packages/sales/src/Actions/SubmitOrderForApprovalAction.php` - Submit for approval
- `packages/sales/src/Actions/ApproveSalesOrderAction.php` - Approve order
- `packages/sales/src/Actions/RejectSalesOrderAction.php` - Reject order
- `packages/sales/src/Actions/ConfirmSalesOrderAction.php` - Confirm order
- `packages/sales/src/Actions/OverrideCreditLimitAction.php` - Override credit limit
- `packages/sales/src/Actions/ReserveStockForOrderAction.php` - Reserve stock
- `packages/sales/src/Actions/FulfillBackOrdersAction.php` - Fulfill back orders
- `packages/sales/src/Actions/ReleaseStockReservationAction.php` - Release reservation
- `packages/sales/src/Actions/CancelSalesOrderAction.php` - Cancel order
- `packages/sales/src/Actions/CloseSalesOrderAction.php` - Close order

### Jobs
- `packages/sales/src/Jobs/GenerateARInvoiceJob.php` - Generate invoice async

### Controllers & Requests
- `packages/sales/src/Http/Controllers/SalesOrderController.php` - Order API controller (extended)
- `packages/sales/src/Http/Requests/StoreSalesOrderRequest.php` - Order validation
- `packages/sales/src/Http/Requests/ApproveOrderRequest.php` - Approval validation
- `packages/sales/src/Http/Requests/RejectOrderRequest.php` - Rejection validation
- `packages/sales/src/Http/Requests/OverrideCreditLimitRequest.php` - Override validation
- `packages/sales/src/Http/Requests/CancelOrderRequest.php` - Cancellation validation

### Resources
- `packages/sales/src/Http/Resources/SalesOrderResource.php` - Order transformation
- `packages/sales/src/Http/Resources/SalesOrderLineResource.php` - Line transformation
- `packages/sales/src/Http/Resources/OrderApprovalResource.php` - Approval transformation
- `packages/sales/src/Http/Resources/BackOrderResource.php` - Back order transformation
- `packages/sales/src/Http/Resources/OrderStatusHistoryResource.php` - Status history

### Events & Listeners
- `packages/sales/src/Events/SalesOrderCreatedEvent.php`
- `packages/sales/src/Events/SalesOrderConfirmedEvent.php`
- `packages/sales/src/Events/SalesOrderCancelledEvent.php`
- `packages/sales/src/Events/CustomerCreditLimitExceededEvent.php`
- `packages/sales/src/Events/StockReservedEvent.php`
- `packages/sales/src/Events/BackOrderCreatedEvent.php`
- `packages/sales/src/Listeners/CheckCreditLimitListener.php`
- `packages/sales/src/Listeners/ReserveStockOnConfirmationListener.php`
- `packages/sales/src/Listeners/FulfillBackOrdersListener.php`
- `packages/sales/src/Listeners/GenerateInvoiceListener.php`

### Observers & Policies
- `packages/sales/src/Observers/SalesOrderObserver.php` - Order model observer
- `packages/sales/src/Policies/SalesOrderPolicy.php` - Order authorization

### Database
- `packages/sales/database/migrations/2025_01_01_000006_create_sales_orders_table.php`
- `packages/sales/database/migrations/2025_01_01_000007_create_sales_order_lines_table.php`
- `packages/sales/database/migrations/2025_01_01_000008_create_order_approvals_table.php`
- `packages/sales/database/migrations/2025_01_01_000009_create_back_orders_table.php`
- `packages/sales/database/migrations/2025_01_01_000010_create_order_status_history_table.php`
- `packages/sales/database/factories/SalesOrderFactory.php`
- `packages/sales/database/factories/OrderApprovalFactory.php`
- `packages/sales/database/factories/BackOrderFactory.php`

### Tests (Total: 108 tasks with testing components)
- `packages/sales/tests/Unit/Models/SalesOrderTest.php`
- `packages/sales/tests/Unit/Services/OrderApprovalServiceTest.php`
- `packages/sales/tests/Unit/Services/BackOrderServiceTest.php`
- `packages/sales/tests/Feature/SalesOrderWorkflowTest.php`
- `packages/sales/tests/Feature/ApprovalWorkflowTest.php`
- `packages/sales/tests/Integration/StockReservationTest.php`
- `packages/sales/tests/Performance/OrderProcessingPerformanceTest.php`

## 6. Testing

### Unit Tests (40 tests)
- **TEST-001**: SalesOrder model status transitions and validations
- **TEST-002**: SalesOrderLine fulfillment calculations
- **TEST-003**: OrderCalculationService totals with various scenarios
- **TEST-004**: OrderApprovalService requiresApproval logic
- **TEST-005**: OrderApprovalService getApprovers with hierarchy
- **TEST-006**: BackOrderService FIFO fulfillment logic
- **TEST-007**: OrderStatusService transition validation
- **TEST-008**: All action classes with mocked dependencies

### Feature Tests (45 tests)
- **TEST-009**: Create order directly via API
- **TEST-010**: Convert quotation to order via API
- **TEST-011**: Cannot update confirmed order (BR-SD-002)
- **TEST-012**: Complete approval workflow (submit→approve→confirm)
- **TEST-013**: Multi-level approval with multiple approvers
- **TEST-014**: Order rejection returns to draft
- **TEST-015**: Credit limit enforcement
- **TEST-016**: Credit limit override with permission
- **TEST-017**: Stock reservation on confirmation
- **TEST-018**: Back order creation when out of stock
- **TEST-019**: Automatic back order fulfillment on restock
- **TEST-020**: Order cancellation and stock release
- **TEST-021**: Order status history tracking
- **TEST-022**: Complete order lifecycle (draft→closed)

### Integration Tests (10 tests)
- **TEST-023**: Credit check integration with CreditCheckService
- **TEST-024**: Stock reservation with Inventory module (mocked)
- **TEST-025**: Invoice generation with AR module (mocked)
- **TEST-026**: Back order fulfillment on stock replenishment

### Performance Tests (3 tests)
- **TEST-027**: Order processing < 2 seconds for 100 items (PR-SD-001)
- **TEST-028**: Concurrent order creation performance
- **TEST-029**: Stock reservation performance

### Security Tests (5 tests)
- **TEST-030**: Tenant isolation for orders
- **TEST-031**: Approval permission enforcement
- **TEST-032**: Credit limit override permission required
- **TEST-033**: Cannot approve own order
- **TEST-034**: Optimistic locking prevents concurrent modifications (ARCH-SD-001)

### Acceptance Tests (5 tests)
- **TEST-035**: Order creation functional
- **TEST-036**: Approval workflow complete
- **TEST-037**: Back orders handled correctly
- **TEST-038**: Credit limits enforced (BR-SD-001)
- **TEST-039**: Confirmed orders immutable (BR-SD-002)

**Total Test Coverage:** 108 tests (40 unit + 45 feature + 10 integration + 3 performance + 5 security + 5 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Stock reservation race conditions with concurrent orders - Mitigation: use pessimistic locking for inventory updates
- **RISK-002**: Complex approval hierarchies may cause delays - Mitigation: implement approval delegation, escalation rules
- **RISK-003**: Back order fulfillment may not be truly FIFO across warehouses - Mitigation: document warehouse-specific FIFO behavior
- **RISK-004**: Invoice generation failures may leave orders in inconsistent state - Mitigation: use job retry logic, implement compensating transactions
- **RISK-005**: Performance degradation with large number of back orders - Mitigation: index optimization, batch processing

### Assumptions
- **ASSUMPTION-001**: Inventory module provides StockReservationServiceContract interface
- **ASSUMPTION-002**: Credit limits are checked at order confirmation, not real-time during entry
- **ASSUMPTION-003**: Approval workflow is sequential (level 1 → level 2 → ...); parallel approvals not supported
- **ASSUMPTION-004**: Back orders fulfilled in strict FIFO order per warehouse
- **ASSUMPTION-005**: Single warehouse per order line (multi-warehouse fulfillment in future)
- **ASSUMPTION-006**: Order modifications create new order versions rather than in-place updates for confirmed orders

## 8. KIV for Future Implementations

- **KIV-001**: Partial order cancellation (cancel specific lines)
- **KIV-002**: Order modification workflow for confirmed orders (change orders)
- **KIV-003**: Split shipment support (fulfill from multiple warehouses)
- **KIV-004**: Parallel approval workflow (multiple approvers at same level)
- **KIV-005**: Approval delegation and escalation rules
- **KIV-006**: Configurable approval matrices (by amount, customer, item category)
- **KIV-007**: Order consolidation (merge multiple orders for same customer)
- **KIV-008**: Recurring orders and subscriptions
- **KIV-009**: Order templates for frequently ordered items
- **KIV-010**: Dynamic credit limit adjustment based on payment history
- **KIV-011**: Advanced back order allocation strategies (priority-based, date-based)
- **KIV-012**: Integration with shipping carriers for real-time rate calculation

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB17-SALES.md](../prd/prd-01/PRD01-SUB17-SALES.md)
- **Related Plans**:
  - PRD01-SUB17-PLAN01 (Customer & Quotation Management) - Foundation
  - PRD01-SUB17-PLAN03 (Order Fulfillment & Delivery) - Fulfillment process
- **Integration Documentation**:
  - SUB14 (Inventory Management) - Stock reservation
  - SUB12 (Accounts Receivable) - Invoice generation
  - SUB15 (Backoffice) - Credit limit checking
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
