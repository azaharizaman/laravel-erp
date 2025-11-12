---
plan: Order Fulfillment & Delivery Management
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, sales, order-fulfillment, delivery-notes, picking, packing, shipping, inventory-integration, logistics]
---

# PRD01-SUB17-PLAN03: Implement Order Fulfillment & Delivery Management

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers order fulfillment workflows, delivery note generation, picking and packing processes, shipping documentation, serial/lot number tracking, and carrier integration. This plan completes the sales order-to-cash cycle by handling the physical fulfillment and delivery of goods.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-SD-004**: Support order fulfillment with picking, packing, and shipping workflows
- **FR-SD-005**: Apply pricing rules during order fulfillment (validate pricing consistency)
- **FR-SD-008**: Generate delivery notes and packing lists automatically

### Business Rules
- **BR-SD-003**: Fulfilled quantity cannot exceed ordered quantity without authorization
- **BR-SD-005**: Delivery notes must reference valid sales orders
- **BR-SD-006**: Cannot ship without delivery note

### Data Requirements
- **DR-SD-003**: Track serial numbers and lot numbers for delivered items
- **DR-SD-004**: Store complete fulfillment history with timestamps

### Integration Requirements
- **IR-SD-001**: Integrate with Inventory for stock movements and serial/lot tracking
- **IR-SD-004**: Update order status in real-time during fulfillment process

### Performance Requirements
- **PR-SD-003**: Delivery note generation must complete in < 1 second

### Security Requirements
- **SR-SD-003**: Only authorized warehouse staff can fulfill orders

### Event Requirements
- **EV-SD-005**: OrderFulfilledEvent when order fulfillment completed
- **EV-SD-006**: DeliveryShippedEvent when goods shipped to customer

### Constraints
- **CON-001**: Depends on PLAN02 (Sales Order Management)
- **CON-002**: Must integrate with SUB14 (Inventory Management) for stock movements
- **CON-003**: Serial/lot numbers must be unique per item and tracked throughout

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Generate unique delivery note numbers automatically
- **GUD-004**: Log all fulfillment activities with user and timestamp

### Patterns
- **PAT-001**: State machine pattern for fulfillment status transitions
- **PAT-002**: Observer pattern for automatic inventory updates
- **PAT-003**: Strategy pattern for different fulfillment methods (full/partial)
- **PAT-004**: Queue jobs for async carrier tracking updates

## 2. Implementation Steps

### GOAL-001: Delivery Note Foundation & Generation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-008 | Generate delivery notes automatically from orders | | |
| BR-SD-005 | Validate delivery notes reference valid orders | | |
| DR-SD-004 | Store complete fulfillment history | | |
| PR-SD-003 | Delivery note generation < 1 second | | |
| EV-SD-005 | Dispatch OrderFulfilledEvent | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000011_create_delivery_notes_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), delivery_note_number (VARCHAR 100 unique per tenant), delivery_date (DATE), order_id (BIGINT FK sales_orders cascade), customer_id (BIGINT FK customers), delivery_address (TEXT), contact_person (VARCHAR 255 nullable), contact_phone (VARCHAR 50 nullable), warehouse_id (BIGINT FK warehouses), carrier (VARCHAR 255 nullable), tracking_number (VARCHAR 255 nullable), status (VARCHAR 20 default 'draft': draft/ready/picked/packed/shipped/delivered/cancelled), notes (TEXT nullable), prepared_by (BIGINT FK users), packed_by (BIGINT nullable FK users), shipped_by (BIGINT nullable FK users), timestamps, soft deletes; indexes: tenant_id, delivery_note_number, order_id, customer_id, status, delivery_date, warehouse_id, tracking_number | | |
| TASK-002 | Create migration `2025_01_01_000012_create_delivery_note_lines_table.php` with columns: id (BIGSERIAL), delivery_note_id (BIGINT FK delivery_notes cascade), order_line_id (BIGINT FK sales_order_lines), line_number (INT), item_id (BIGINT FK inventory_items), item_description (TEXT), ordered_quantity (DECIMAL 15,4), delivered_quantity (DECIMAL 15,4), uom_id (BIGINT FK uoms), bin_location (VARCHAR 100 nullable), picked_at (TIMESTAMP nullable), packed_at (TIMESTAMP nullable), timestamps; indexes: delivery_note_id, order_line_id, item_id | | |
| TASK-003 | Create migration `2025_01_01_000013_create_delivery_note_serials_table.php` with columns: id (BIGSERIAL), delivery_note_line_id (BIGINT FK delivery_note_lines cascade), serial_number (VARCHAR 255), lot_number (VARCHAR 255 nullable), expiry_date (DATE nullable), timestamps; unique constraint: serial_number per tenant; indexes: delivery_note_line_id, serial_number, lot_number | | |
| TASK-004 | Create enum `DeliveryNoteStatus` with values: DRAFT, READY, PICKED, PACKED, SHIPPED, DELIVERED, CANCELLED; define allowed transitions: DRAFT→READY, READY→PICKED, PICKED→PACKED, PACKED→SHIPPED, SHIPPED→DELIVERED, any→CANCELLED | | |
| TASK-005 | Create model `DeliveryNote.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: delivery_note_number, delivery_date, order_id, customer_id, delivery_address, contact_person, contact_phone, warehouse_id, carrier, tracking_number, notes; casts: delivery_date → date, status → DeliveryNoteStatus enum, shipped_at → datetime; relationships: order (belongsTo), customer (belongsTo), warehouse (belongsTo), lines (hasMany DeliveryNoteLine), preparedBy (belongsTo User), packedBy (belongsTo User), shippedBy (belongsTo User); scopes: byStatus(DeliveryNoteStatus $status), shipped(), delivered(), byWarehouse(int $warehouseId), byCarrier(string $carrier); computed: is_fully_delivered, is_partially_delivered, can_be_cancelled, total_items (sum of line quantities) | | |
| TASK-006 | Create model `DeliveryNoteLine.php` with fillable: order_line_id, line_number, item_id, item_description, ordered_quantity, delivered_quantity, uom_id, bin_location; casts: ordered_quantity → float, delivered_quantity → float, picked_at → datetime, packed_at → datetime; relationships: deliveryNote (belongsTo), orderLine (belongsTo), item (belongsTo), uom (belongsTo), serials (hasMany DeliveryNoteSerial); computed: is_fully_delivered (delivered_quantity >= ordered_quantity), short_quantity (ordered_quantity - delivered_quantity > 0) | | |
| TASK-007 | Create model `DeliveryNoteSerial.php` with fillable: delivery_note_line_id, serial_number, lot_number, expiry_date; casts: expiry_date → date; relationships: deliveryNoteLine (belongsTo); scopes: bySerial(string $serial), byLot(string $lot), expiringSoon(int $days = 30) | | |
| TASK-008 | Create factory `DeliveryNoteFactory.php` with states: draft(), ready(), picked(), packed(), shipped(), delivered(), cancelled(), withLines(int $count = 3), partiallyDelivered() | | |
| TASK-009 | Create factory `DeliveryNoteLineFactory.php` with states: fullyDelivered(), partiallyDelivered(), withSerials(int $count = 1) | | |
| TASK-010 | Create factory `DeliveryNoteSerialFactory.php` with states: withLot(), withExpiry() | | |
| TASK-011 | Create contract `DeliveryNoteRepositoryContract.php` with methods: findById(int $id): ?DeliveryNote, findByNumber(string $number, string $tenantId): ?DeliveryNote, create(array $data): DeliveryNote, update(DeliveryNote $note, array $data): DeliveryNote, delete(DeliveryNote $note): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getByOrder(int $orderId): Collection, getPendingDeliveries(int $warehouseId = null): Collection, getShippedDeliveries(array $filters = []): Collection | | |
| TASK-012 | Implement `DeliveryNoteRepository.php` with eager loading for order, customer, lines, items; implement filters: status, warehouse_id, customer_id, date_range, carrier, tracking_number | | |
| TASK-013 | Create action `CreateDeliveryNoteAction.php` using AsAction; inject DeliveryNoteRepositoryContract, SalesOrderRepositoryContract, ActivityLoggerContract; validate order status is CONFIRMED or PARTIAL; validate order has unfulfilled lines; generate delivery_note_number; create delivery note header; create delivery note lines from unfulfilled order lines; set status to DRAFT; log activity "Delivery note created"; dispatch DeliveryNoteCreatedEvent; return DeliveryNote; measure execution time (must be < 1s per PR-SD-003) | | |
| TASK-014 | Create action `UpdateDeliveryNoteAction.php`; validate status is DRAFT or READY; update delivery note and lines; log activity "Delivery note updated"; dispatch DeliveryNoteUpdatedEvent | | |
| TASK-015 | Create action `GeneratePackingListAction.php`; generate PDF packing list from delivery note; include: delivery note number, customer details, item list with quantities, bin locations, special instructions; return PDF path or stream | | |
| TASK-016 | Create event `DeliveryNoteCreatedEvent` with properties: DeliveryNote $note, SalesOrder $order, User $createdBy | | |
| TASK-017 | Create event `DeliveryNoteUpdatedEvent` with properties: DeliveryNote $note, array $changes, User $updatedBy | | |
| TASK-018 | Create observer `DeliveryNoteObserver.php` with creating() to generate delivery_note_number; updating() to validate status transitions; deleting() to prevent deletion of shipped notes | | |
| TASK-019 | Create policy `DeliveryNotePolicy.php` requiring 'manage-deliveries' permission for CRUD, 'fulfill-orders' for status updates; enforce tenant scope; validate warehouse access | | |
| TASK-020 | Create API controller `DeliveryNoteController.php` with routes: index (GET /sales/delivery-notes), store (POST /sales/delivery-notes), show (GET /sales/delivery-notes/{id}), update (PATCH /sales/delivery-notes/{id}), destroy (DELETE /sales/delivery-notes/{id}), packingList (GET /sales/delivery-notes/{id}/packing-list); authorize actions; inject DeliveryNoteRepositoryContract | | |
| TASK-021 | Create form request `StoreDeliveryNoteRequest.php` with validation: order_id (required, exists:sales_orders), delivery_date (required, date, after_or_equal:today), warehouse_id (required, exists:warehouses), delivery_address (required, string), lines (required, array, min:1), lines.*.order_line_id (required, exists:sales_order_lines), lines.*.delivered_quantity (required, numeric, min:0.0001) | | |
| TASK-022 | Create form request `UpdateDeliveryNoteRequest.php` extending StoreDeliveryNoteRequest; add status validation (must be DRAFT or READY) | | |
| TASK-023 | Create API resource `DeliveryNoteResource.php` with fields: id, delivery_note_number, delivery_date, order (nested reference), customer (nested), warehouse, lines (nested collection), status, carrier, tracking_number, total_items, is_fully_delivered, prepared_by, packed_by, shipped_by, created_at | | |
| TASK-024 | Create API resource `DeliveryNoteLineResource.php` with fields: line_number, item, item_description, ordered_quantity, delivered_quantity, short_quantity, is_fully_delivered, uom, bin_location, serials (nested collection), picked_at, packed_at | | |
| TASK-025 | Create API resource `DeliveryNoteSerialResource.php` with fields: serial_number, lot_number, expiry_date | | |

### GOAL-002: Picking & Packing Workflow

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-004 | Implement picking and packing workflows | | |
| DR-SD-003 | Track serial/lot numbers during fulfillment | | |
| SR-SD-003 | Authorize only warehouse staff for fulfillment | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create service `PickingService.php` with methods: startPicking(DeliveryNote $note): void (update status to PICKED, set picked_at), validatePickQuantity(DeliveryNoteLine $line, float $quantity): bool (check quantity ≤ ordered), recordPick(DeliveryNoteLine $line, float $quantity, ?string $binLocation = null): void, completeLinePick(DeliveryNoteLine $line): void, isPickingComplete(DeliveryNote $note): bool (all lines picked) | | |
| TASK-027 | Create service `PackingService.php` with methods: startPacking(DeliveryNote $note): void (update status to PACKED, set packed_at), validatePackQuantity(DeliveryNoteLine $line, float $quantity): bool, recordPack(DeliveryNoteLine $line, float $quantity): void, addSerial(DeliveryNoteLine $line, string $serialNumber, ?string $lotNumber = null, ?Carbon $expiryDate = null): void, completeLinePack(DeliveryNoteLine $line): void, isPackingComplete(DeliveryNote $note): bool (all lines packed) | | |
| TASK-028 | Create action `StartPickingAction.php`; validate delivery note status is READY; validate user has 'fulfill-orders' permission; update status to PICKED; log activity "Picking started"; dispatch PickingStartedEvent | | |
| TASK-029 | Create action `RecordPickAction.php`; validate line belongs to delivery note; validate quantity ≤ ordered_quantity (BR-SD-003); validate item available in bin_location; update delivered_quantity; set picked_at timestamp; log activity "Line picked: {item} qty {quantity}"; dispatch LinePickedEvent | | |
| TASK-030 | Create action `CompletePickingAction.php`; validate all lines picked; update status to PICKED; set picked_by user; log activity "Picking completed"; dispatch PickingCompletedEvent | | |
| TASK-031 | Create action `StartPackingAction.php`; validate status is PICKED; validate user has 'fulfill-orders' permission; update status to PACKING; log activity "Packing started"; dispatch PackingStartedEvent | | |
| TASK-032 | Create action `RecordPackAction.php`; validate line picked; validate quantity matches picked; for serialized items: validate serial numbers provided; create DeliveryNoteSerial records; set packed_at timestamp; log activity "Line packed: {item} with serials {serials}"; dispatch LinePackedEvent | | |
| TASK-033 | Create action `CompletePackingAction.php`; validate all lines packed; validate serial/lot tracking complete for required items; update status to PACKED; set packed_by user; generate packing list PDF; log activity "Packing completed"; dispatch PackingCompletedEvent | | |
| TASK-034 | Create event `PickingStartedEvent` with properties: DeliveryNote $note, User $picker | | |
| TASK-035 | Create event `LinePickedEvent` with properties: DeliveryNote $note, DeliveryNoteLine $line, float $quantity, User $picker | | |
| TASK-036 | Create event `PickingCompletedEvent` with properties: DeliveryNote $note, User $picker | | |
| TASK-037 | Create event `PackingStartedEvent` with properties: DeliveryNote $note, User $packer | | |
| TASK-038 | Create event `LinePackedEvent` with properties: DeliveryNote $note, DeliveryNoteLine $line, Collection $serials, User $packer | | |
| TASK-039 | Create event `PackingCompletedEvent` with properties: DeliveryNote $note, User $packer | | |
| TASK-040 | Create API controller routes in DeliveryNoteController: startPicking (POST /delivery-notes/{id}/start-picking), recordPick (POST /delivery-notes/{id}/lines/{lineId}/pick), completePicking (POST /delivery-notes/{id}/complete-picking), startPacking (POST /delivery-notes/{id}/start-packing), recordPack (POST /delivery-notes/{id}/lines/{lineId}/pack), completePacking (POST /delivery-notes/{id}/complete-packing) | | |
| TASK-041 | Create form request `RecordPickRequest.php` with validation: quantity (required, numeric, min:0.0001, max:ordered_quantity), bin_location (nullable, string, max:100) | | |
| TASK-042 | Create form request `RecordPackRequest.php` with validation: quantity (required, numeric, min:0.0001), serials (nullable, array), serials.*.serial_number (required_with:serials, string, unique per item), serials.*.lot_number (nullable, string), serials.*.expiry_date (nullable, date, after:today) | | |
| TASK-043 | Create middleware `ValidateWarehouseAccess.php` to ensure user has access to specified warehouse; check user's assigned warehouses or role permissions | | |
| TASK-044 | Write unit tests for PickingService: test validatePickQuantity with various scenarios, test isPickingComplete logic | | |
| TASK-045 | Write unit tests for PackingService: test serial number uniqueness validation, test isPackingComplete logic | | |
| TASK-046 | Write feature tests for picking workflow: test start→pick lines→complete cycle, test cannot exceed ordered quantity (BR-SD-003), test authorization checks (SR-SD-003) | | |
| TASK-047 | Write feature tests for packing workflow: test serial number tracking (DR-SD-003), test lot number tracking, test expiry date tracking | | |

### GOAL-003: Shipping & Delivery Completion

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-SD-004 | Complete shipping workflow with carrier integration | | |
| BR-SD-006 | Validate shipping prerequisites | | |
| EV-SD-006 | Dispatch DeliveryShippedEvent | | |
| IR-SD-004 | Update order status in real-time | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-048 | Create service `ShippingService.php` with methods: validateShipping(DeliveryNote $note): void (check status PACKED, BR-SD-006), assignCarrier(DeliveryNote $note, string $carrier, ?string $trackingNumber = null): void, calculateShippingWeight(DeliveryNote $note): float, generateShippingLabel(DeliveryNote $note): string (returns label path), recordShipment(DeliveryNote $note, string $carrier, string $trackingNumber): void, markAsDelivered(DeliveryNote $note, Carbon $deliveredAt): void | | |
| TASK-049 | Create action `ShipDeliveryNoteAction.php`; validate status is PACKED (BR-SD-006); validate carrier and tracking_number provided; validate packing list generated; update status to SHIPPED; set shipped_by user and shipped_at timestamp; update carrier and tracking_number; log activity "Delivery note shipped: {carrier} {tracking_number}"; dispatch DeliveryShippedEvent | | |
| TASK-050 | Create action `MarkAsDeliveredAction.php`; validate status is SHIPPED; validate delivery confirmation (signature, photo, etc.); update status to DELIVERED; set delivered_at timestamp; update all order lines fulfilled_quantity; check if order fully fulfilled; if fully fulfilled: update order status to FULFILLED, dispatch OrderFulfilledEvent; log activity "Delivery confirmed"; dispatch DeliveryConfirmedEvent | | |
| TASK-051 | Create action `CancelDeliveryNoteAction.php`; validate not yet shipped (can cancel DRAFT, READY, PICKED, PACKED); validate cancellation reason; release any reserved stock; update status to CANCELLED; log activity "Delivery cancelled: {reason}"; dispatch DeliveryCancelledEvent | | |
| TASK-052 | Create action `PartialDeliveryAction.php`; handle partial deliveries (delivered < ordered); update order status to PARTIAL; create new delivery note for remaining quantity; log activity "Partial delivery completed"; dispatch PartialDeliveryEvent | | |
| TASK-053 | Create event `DeliveryShippedEvent` with properties: DeliveryNote $note, SalesOrder $order, string $carrier, string $trackingNumber, User $shippedBy | | |
| TASK-054 | Create event `DeliveryConfirmedEvent` with properties: DeliveryNote $note, SalesOrder $order, Carbon $deliveredAt | | |
| TASK-055 | Create event `DeliveryCancelledEvent` with properties: DeliveryNote $note, string $reason, User $cancelledBy | | |
| TASK-056 | Create event `PartialDeliveryEvent` with properties: DeliveryNote $note, SalesOrder $order, float $deliveredPercentage | | |
| TASK-057 | Create event `OrderFulfilledEvent` with properties: SalesOrder $order, Collection $deliveryNotes, Carbon $fulfilledAt | | |
| TASK-058 | Create listener `UpdateOrderStatusOnShipmentListener.php` listening to DeliveryShippedEvent; check if all order lines covered by shipped delivery notes; if partial: update order to PARTIAL; if complete and all shipped: keep CONFIRMED until delivered | | |
| TASK-059 | Create listener `UpdateOrderStatusOnDeliveryListener.php` listening to DeliveryConfirmedEvent; update order line fulfilled_quantity; recalculate order fulfillment percentage; if fully fulfilled: update order status to FULFILLED, dispatch OrderFulfilledEvent (trigger invoice generation in PLAN02) | | |
| TASK-060 | Create listener `NotifyCustomerOnShipmentListener.php` listening to DeliveryShippedEvent; send shipment notification email with tracking details; create activity log | | |
| TASK-061 | Create API controller routes in DeliveryNoteController: ship (POST /delivery-notes/{id}/ship), markDelivered (POST /delivery-notes/{id}/mark-delivered), cancel (POST /delivery-notes/{id}/cancel), trackingStatus (GET /delivery-notes/{id}/tracking) | | |
| TASK-062 | Create form request `ShipDeliveryNoteRequest.php` with validation: carrier (required, string, max:255), tracking_number (required, string, max:255), shipping_date (nullable, date, after_or_equal:today), notes (nullable, string, max:1000) | | |
| TASK-063 | Create form request `MarkDeliveredRequest.php` with validation: delivered_at (required, date, before_or_equal:today), confirmation_signature (nullable, string), confirmation_photo (nullable, file, image, max:5120), recipient_name (required, string, max:255) | | |
| TASK-064 | Create form request `CancelDeliveryNoteRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-065 | Write unit tests for ShippingService: test validateShipping checks status and completeness, test calculateShippingWeight | | |
| TASK-066 | Write feature tests for shipping workflow: test ship→deliver cycle, test cannot ship without packing (BR-SD-006), test order status updates (IR-SD-004), test partial deliveries | | |
| TASK-067 | Write integration tests: test order fulfillment updates order status correctly, test invoice generation triggered on fulfillment (mocked) | | |

### GOAL-004: Inventory Integration & Stock Movements

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-SD-001 | Integrate with Inventory for stock movements | | |
| FR-SD-005 | Apply pricing rules (validate during fulfillment) | | |
| DR-SD-003 | Track serial/lot movements in inventory | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-068 | Create contract `StockMovementServiceContract.php` (in Inventory package) with methods: createOutboundMovement(DeliveryNote $note): void, recordSerialMovement(string $serialNumber, int $fromLocation, int $toLocation): void, updateStockLevel(int $itemId, int $warehouseId, float $quantity, string $movementType): void, getItemLocation(int $itemId, int $warehouseId): ?string (bin location) | | |
| TASK-069 | Create service `FulfillmentInventoryService.php` with methods: validateStockAvailability(DeliveryNote $note): bool, processStockMovement(DeliveryNote $note): void (create outbound movements), releaseReservation(DeliveryNote $note): void, validateSerialNumbers(array $serials, int $itemId): bool (check serials exist and available) | | |
| TASK-070 | Create action `ProcessInventoryMovementAction.php` using AsAction with asJob(); listen to DeliveryShippedEvent; inject StockMovementServiceContract, FulfillmentInventoryService; validate stock levels; create outbound stock movements for each line; update inventory balance; record serial/lot movements; release stock reservations; log activity "Stock movement processed for delivery {note_number}"; dispatch StockMovementProcessedEvent | | |
| TASK-071 | Create action `ValidatePricingAction.php`; inject PricingService from PLAN01; compare delivery note line prices with current pricing; if price changed since order: flag for review or auto-adjust based on config; log activity "Pricing validated" or "Price discrepancy detected"; dispatch PriceValidatedEvent or PriceDiscrepancyEvent | | |
| TASK-072 | Create event `StockMovementProcessedEvent` with properties: DeliveryNote $note, Collection $movements, float $totalQuantity | | |
| TASK-073 | Create event `PriceValidatedEvent` with properties: DeliveryNote $note, bool $pricesMatch | | |
| TASK-074 | Create event `PriceDiscrepancyEvent` with properties: DeliveryNote $note, array $discrepancies (line_id, old_price, new_price) | | |
| TASK-075 | Create listener `ProcessStockMovementOnShipmentListener.php` listening to DeliveryShippedEvent; dispatch ProcessInventoryMovementAction->asJob() for async processing | | |
| TASK-076 | Create listener `ValidatePricingOnPackingListener.php` listening to PackingCompletedEvent; dispatch ValidatePricingAction to ensure pricing hasn't changed significantly (FR-SD-005) | | |
| TASK-077 | Update DeliveryNoteResource to include: stock_movement_status (pending/processed/failed), pricing_validated (bool), price_discrepancies (array) | | |
| TASK-078 | Write integration tests: test stock movement creation on shipment (mocked StockMovementServiceContract), test serial number movement tracking, test stock reservation release | | |
| TASK-079 | Write integration tests: test pricing validation during fulfillment (FR-SD-005), test price discrepancy detection and handling | | |

### GOAL-005: Testing, Documentation & Deployment

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-SD-003 | Delivery note generation < 1 second | | |
| SR-SD-003 | Authorization for warehouse operations | | |
| All FRs | Complete test coverage for fulfillment workflows | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-080 | Write comprehensive unit tests for all models: test DeliveryNote status transitions, test DeliveryNoteLine calculations, test DeliveryNoteSerial validations | | |
| TASK-081 | Write comprehensive unit tests for all services: test PickingService quantity validation, test PackingService serial uniqueness, test ShippingService weight calculation, test FulfillmentInventoryService stock checks | | |
| TASK-082 | Write comprehensive unit tests for all actions: test CreateDeliveryNoteAction performance (< 1s per PR-SD-003), test picking/packing actions, test shipping actions, test pricing validation | | |
| TASK-083 | Write feature tests for complete fulfillment workflows: test delivery note creation→picking→packing→shipping→delivery; verify status transitions, verify events dispatched, verify order status updates | | |
| TASK-084 | Write feature tests for serial/lot tracking: test serial number assignment during packing (DR-SD-003), test lot number tracking, test expiry date tracking, test serial uniqueness enforcement | | |
| TASK-085 | Write feature tests for partial deliveries: test split shipments, test multiple delivery notes per order, test order status tracking with partials | | |
| TASK-086 | Write integration tests: test complete order-to-delivery cycle (order→delivery note→pick→pack→ship→deliver→invoice); verify all status updates across modules | | |
| TASK-087 | Write integration tests: test stock movement integration with Inventory module (mocked), test reservation release on cancellation, test serial tracking in inventory | | |
| TASK-088 | Write integration tests: test pricing validation during fulfillment (FR-SD-005), test invoice generation trigger on fulfillment (mocked AR integration) | | |
| TASK-089 | Write performance test: create delivery note with 100 lines, test generation completes in < 1 second (PR-SD-003); test with concurrent deliveries | | |
| TASK-090 | Write security tests: test warehouse staff authorization (SR-SD-003), test tenant isolation for deliveries, test users cannot fulfill orders outside their warehouse access | | |
| TASK-091 | Write acceptance tests: test delivery note generation functional, test picking workflow complete, test packing with serials working, test shipping process functional, test order fulfillment status updates correctly | | |
| TASK-092 | Set up Pest configuration for delivery/fulfillment tests; configure database transactions, warehouse seeding, item setup with serialized tracking | | |
| TASK-093 | Achieve minimum 80% code coverage for fulfillment module; run `./vendor/bin/pest --coverage`; add tests for uncovered paths | | |
| TASK-094 | Create API documentation for delivery endpoints: document picking workflow, document packing with serials, document shipping process, include sequence diagrams for complete fulfillment flow | | |
| TASK-095 | Create user guide for warehouse staff: picking instructions, packing procedures, serial number entry, shipping documentation, delivery confirmation | | |
| TASK-096 | Create technical documentation: serial/lot tracking architecture, stock movement integration, pricing validation logic, carrier integration patterns | | |
| TASK-097 | Update sales package README with fulfillment features: delivery note generation, picking/packing workflows, serial tracking, carrier integration | | |
| TASK-098 | Create operational runbook: handling partial deliveries, managing delivery exceptions, cancellation procedures, serial number corrections | | |
| TASK-099 | Validate all acceptance criteria: delivery notes generated correctly, picking workflow functional, packing with serials working, shipping process complete, order status updates accurate, inventory movements processed | | |
| TASK-100 | Conduct code review: verify all business rules implemented (BR-SD-003, BR-SD-005, BR-SD-006), verify performance requirements met (PR-SD-003), verify security controls enforced (SR-SD-003), verify data requirements met (DR-SD-003, DR-SD-004) | | |
| TASK-101 | Run full test suite for fulfillment module; verify all tests pass; fix flaky tests; ensure consistent results across environments | | |
| TASK-102 | Deploy to staging; test complete fulfillment workflow end-to-end; test with serialized and non-serialized items; test partial deliveries; test carrier integration; verify performance with large delivery notes | | |
| TASK-103 | Train warehouse staff: picking procedures, packing with serial entry, shipping documentation, mobile device usage (if applicable), troubleshooting common issues | | |
| TASK-104 | Create monitoring dashboard: track delivery note metrics (created, shipped, delivered), picking efficiency, packing accuracy, on-time delivery rate, serial tracking compliance | | |

## 3. Alternatives

- **ALT-001**: Combined picking and packing into single step - rejected for better workflow control and audit trail
- **ALT-002**: Manual serial number entry vs barcode scanning - implementing both, barcode scanning as optional enhancement
- **ALT-003**: Real-time carrier API integration vs manual tracking number entry - implementing manual first, carrier APIs as future enhancement
- **ALT-004**: Single delivery note per order vs multiple - implemented multiple delivery notes to support partial shipments
- **ALT-005**: PDF generation server-side vs client-side - selected server-side for consistency and control

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: PLAN02 (Sales Order Management) - SalesOrder model with fulfillment tracking
- **DEP-002**: PLAN01 (Customer & Quotation Management) - Customer model, PricingService
- **DEP-003**: SUB14 (Inventory Management) - InventoryItem model, StockMovementServiceContract, serial/lot tracking
- **DEP-004**: SUB04 (Serial Numbering) - Delivery note number generation
- **DEP-005**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking

### Optional Dependencies
- **DEP-006**: SUB22 (Notifications) - Email notifications for shipment tracking
- **DEP-007**: Carrier APIs - FedEx, UPS, DHL integration for tracking (future)
- **DEP-008**: Barcode scanning system - Mobile app or handheld devices (future)

### Package Dependencies
- **DEP-009**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-010**: Laravel Queue system - Async stock movement processing
- **DEP-011**: PDF generation library - barryvdh/laravel-dompdf or similar

## 5. Files

### Models & Enums
- `packages/sales/src/Models/DeliveryNote.php` - Delivery note header model
- `packages/sales/src/Models/DeliveryNoteLine.php` - Delivery line items model
- `packages/sales/src/Models/DeliveryNoteSerial.php` - Serial/lot tracking model
- `packages/sales/src/Enums/DeliveryNoteStatus.php` - Delivery status enumeration

### Repositories & Contracts
- `packages/sales/src/Contracts/DeliveryNoteRepositoryContract.php` - Delivery repository interface
- `packages/sales/src/Repositories/DeliveryNoteRepository.php` - Delivery repository implementation
- `packages/sales/src/Contracts/StockMovementServiceContract.php` - Stock movement interface (from Inventory)

### Services
- `packages/sales/src/Services/PickingService.php` - Picking workflow logic
- `packages/sales/src/Services/PackingService.php` - Packing workflow logic
- `packages/sales/src/Services/ShippingService.php` - Shipping workflow logic
- `packages/sales/src/Services/FulfillmentInventoryService.php` - Inventory integration

### Actions
- `packages/sales/src/Actions/CreateDeliveryNoteAction.php` - Create delivery note
- `packages/sales/src/Actions/UpdateDeliveryNoteAction.php` - Update delivery note
- `packages/sales/src/Actions/GeneratePackingListAction.php` - Generate packing list PDF
- `packages/sales/src/Actions/StartPickingAction.php` - Start picking
- `packages/sales/src/Actions/RecordPickAction.php` - Record picked quantity
- `packages/sales/src/Actions/CompletePickingAction.php` - Complete picking
- `packages/sales/src/Actions/StartPackingAction.php` - Start packing
- `packages/sales/src/Actions/RecordPackAction.php` - Record packed quantity with serials
- `packages/sales/src/Actions/CompletePackingAction.php` - Complete packing
- `packages/sales/src/Actions/ShipDeliveryNoteAction.php` - Ship delivery
- `packages/sales/src/Actions/MarkAsDeliveredAction.php` - Confirm delivery
- `packages/sales/src/Actions/CancelDeliveryNoteAction.php` - Cancel delivery
- `packages/sales/src/Actions/PartialDeliveryAction.php` - Handle partial delivery
- `packages/sales/src/Actions/ProcessInventoryMovementAction.php` - Process stock movement
- `packages/sales/src/Actions/ValidatePricingAction.php` - Validate pricing

### Jobs
- `packages/sales/src/Jobs/ProcessInventoryMovementJob.php` - Async stock movement

### Controllers & Requests
- `packages/sales/src/Http/Controllers/DeliveryNoteController.php` - Delivery API controller
- `packages/sales/src/Http/Requests/StoreDeliveryNoteRequest.php` - Delivery validation
- `packages/sales/src/Http/Requests/RecordPickRequest.php` - Picking validation
- `packages/sales/src/Http/Requests/RecordPackRequest.php` - Packing validation
- `packages/sales/src/Http/Requests/ShipDeliveryNoteRequest.php` - Shipping validation
- `packages/sales/src/Http/Requests/MarkDeliveredRequest.php` - Delivery confirmation validation
- `packages/sales/src/Http/Requests/CancelDeliveryNoteRequest.php` - Cancellation validation

### Resources
- `packages/sales/src/Http/Resources/DeliveryNoteResource.php` - Delivery transformation
- `packages/sales/src/Http/Resources/DeliveryNoteLineResource.php` - Line transformation
- `packages/sales/src/Http/Resources/DeliveryNoteSerialResource.php` - Serial transformation

### Events & Listeners
- `packages/sales/src/Events/DeliveryNoteCreatedEvent.php`
- `packages/sales/src/Events/PickingStartedEvent.php`
- `packages/sales/src/Events/LinePickedEvent.php`
- `packages/sales/src/Events/PickingCompletedEvent.php`
- `packages/sales/src/Events/PackingStartedEvent.php`
- `packages/sales/src/Events/LinePackedEvent.php`
- `packages/sales/src/Events/PackingCompletedEvent.php`
- `packages/sales/src/Events/DeliveryShippedEvent.php`
- `packages/sales/src/Events/DeliveryConfirmedEvent.php`
- `packages/sales/src/Events/DeliveryCancelledEvent.php`
- `packages/sales/src/Events/OrderFulfilledEvent.php`
- `packages/sales/src/Events/StockMovementProcessedEvent.php`
- `packages/sales/src/Listeners/UpdateOrderStatusOnShipmentListener.php`
- `packages/sales/src/Listeners/UpdateOrderStatusOnDeliveryListener.php`
- `packages/sales/src/Listeners/NotifyCustomerOnShipmentListener.php`
- `packages/sales/src/Listeners/ProcessStockMovementOnShipmentListener.php`
- `packages/sales/src/Listeners/ValidatePricingOnPackingListener.php`

### Observers, Policies & Middleware
- `packages/sales/src/Observers/DeliveryNoteObserver.php` - Delivery model observer
- `packages/sales/src/Policies/DeliveryNotePolicy.php` - Delivery authorization
- `packages/sales/src/Http/Middleware/ValidateWarehouseAccess.php` - Warehouse authorization

### Database
- `packages/sales/database/migrations/2025_01_01_000011_create_delivery_notes_table.php`
- `packages/sales/database/migrations/2025_01_01_000012_create_delivery_note_lines_table.php`
- `packages/sales/database/migrations/2025_01_01_000013_create_delivery_note_serials_table.php`
- `packages/sales/database/factories/DeliveryNoteFactory.php`
- `packages/sales/database/factories/DeliveryNoteLineFactory.php`
- `packages/sales/database/factories/DeliveryNoteSerialFactory.php`

### Tests (Total: 104 tasks with testing components)
- `packages/sales/tests/Unit/Models/DeliveryNoteTest.php`
- `packages/sales/tests/Unit/Services/PickingServiceTest.php`
- `packages/sales/tests/Unit/Services/PackingServiceTest.php`
- `packages/sales/tests/Unit/Services/ShippingServiceTest.php`
- `packages/sales/tests/Feature/DeliveryNoteWorkflowTest.php`
- `packages/sales/tests/Feature/PickingWorkflowTest.php`
- `packages/sales/tests/Feature/PackingWorkflowTest.php`
- `packages/sales/tests/Feature/ShippingWorkflowTest.php`
- `packages/sales/tests/Feature/SerialTrackingTest.php`
- `packages/sales/tests/Integration/InventoryIntegrationTest.php`
- `packages/sales/tests/Integration/OrderFulfillmentIntegrationTest.php`
- `packages/sales/tests/Performance/DeliveryNotePerformanceTest.php`

## 6. Testing

### Unit Tests (35 tests)
- **TEST-001**: DeliveryNote model status transitions and validations
- **TEST-002**: DeliveryNoteLine quantity calculations
- **TEST-003**: DeliveryNoteSerial uniqueness validation
- **TEST-004**: PickingService quantity validation logic
- **TEST-005**: PickingService isPickingComplete logic
- **TEST-006**: PackingService serial uniqueness checks
- **TEST-007**: PackingService isPackingComplete logic
- **TEST-008**: ShippingService weight calculation
- **TEST-009**: ShippingService validateShipping prerequisites
- **TEST-010**: FulfillmentInventoryService stock availability checks
- **TEST-011**: All action classes with mocked dependencies

### Feature Tests (40 tests)
- **TEST-012**: Create delivery note from order via API
- **TEST-013**: Cannot exceed ordered quantity during picking (BR-SD-003)
- **TEST-014**: Complete picking workflow (start→pick lines→complete)
- **TEST-015**: Picking authorization checks (SR-SD-003)
- **TEST-016**: Serial number assignment during packing (DR-SD-003)
- **TEST-017**: Lot number tracking during packing
- **TEST-018**: Expiry date tracking for lot-controlled items
- **TEST-019**: Serial number uniqueness enforcement
- **TEST-020**: Complete packing workflow (start→pack lines with serials→complete)
- **TEST-021**: Cannot ship without packing (BR-SD-006)
- **TEST-022**: Shipping workflow (pack→ship→deliver)
- **TEST-023**: Delivery confirmation updates order status
- **TEST-024**: Partial delivery creates new delivery note for remaining
- **TEST-025**: Order status updates from CONFIRMED→PARTIAL→FULFILLED
- **TEST-026**: Delivery cancellation releases stock reservation
- **TEST-027**: Multiple delivery notes per order

### Integration Tests (15 tests)
- **TEST-028**: Complete order-to-delivery cycle with all status updates
- **TEST-029**: Stock movement creation on shipment (mocked Inventory)
- **TEST-030**: Serial number movement tracking in inventory
- **TEST-031**: Stock reservation release on cancellation
- **TEST-032**: Invoice generation triggered on order fulfillment (mocked AR)
- **TEST-033**: Pricing validation during fulfillment (FR-SD-005)
- **TEST-034**: Price discrepancy detection and handling
- **TEST-035**: Order fulfillment percentage calculation with multiple deliveries

### Performance Tests (3 tests)
- **TEST-036**: Delivery note generation < 1 second for 100 lines (PR-SD-003)
- **TEST-037**: Concurrent delivery note creation performance
- **TEST-038**: Packing list PDF generation performance

### Security Tests (6 tests)
- **TEST-039**: Warehouse staff authorization enforced (SR-SD-003)
- **TEST-040**: Tenant isolation for delivery notes
- **TEST-041**: Users cannot fulfill orders outside warehouse access
- **TEST-042**: Only authorized users can cancel deliveries
- **TEST-043**: Serial number access restricted to warehouse staff

### Acceptance Tests (5 tests)
- **TEST-044**: Delivery note generation functional
- **TEST-045**: Picking workflow complete
- **TEST-046**: Packing with serials working
- **TEST-047**: Shipping process functional
- **TEST-048**: Order fulfillment status updates correctly

**Total Test Coverage:** 104 tests (35 unit + 40 feature + 15 integration + 3 performance + 6 security + 5 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Serial number scanning errors may cause inventory discrepancies - Mitigation: implement validation checks, barcode verification
- **RISK-002**: Partial delivery handling may confuse customers - Mitigation: clear communication, automated notifications
- **RISK-003**: Stock movement integration failures may leave inventory out of sync - Mitigation: implement retry logic, reconciliation processes
- **RISK-004**: PDF generation may be slow for large packing lists - Mitigation: async generation, caching
- **RISK-005**: Manual tracking number entry prone to errors - Mitigation: implement validation patterns, carrier API integration (future)

### Assumptions
- **ASSUMPTION-001**: Inventory module provides StockMovementServiceContract for outbound movements
- **ASSUMPTION-002**: Warehouse staff have basic barcode scanner training (or manual entry as fallback)
- **ASSUMPTION-003**: Single warehouse per delivery note (multi-warehouse split shipments in future)
- **ASSUMPTION-004**: Serial numbers are pre-assigned during goods receipt (not generated during packing)
- **ASSUMPTION-005**: Carrier tracking is manual entry initially; carrier API integration is future enhancement
- **ASSUMPTION-006**: Delivery confirmation can be recorded manually; electronic signature/photo optional
- **ASSUMPTION-007**: One delivery note can fulfill one or more order lines, but not partial lines (full line per delivery)

## 8. KIV for Future Implementations

- **KIV-001**: Mobile app for picking and packing with barcode scanning
- **KIV-002**: Real-time carrier API integration (FedEx, UPS, DHL) for tracking
- **KIV-003**: Electronic signature capture for delivery confirmation
- **KIV-004**: Photo documentation of delivered goods
- **KIV-005**: Route optimization for delivery planning
- **KIV-006**: Delivery appointment scheduling with customer
- **KIV-007**: Multi-warehouse split shipments (single order fulfilled from multiple warehouses)
- **KIV-008**: Cross-docking support (direct warehouse transfer)
- **KIV-009**: Automated label printing integration
- **KIV-010**: Packing optimization algorithms (box size, weight distribution)
- **KIV-011**: Return delivery notes (reverse logistics)
- **KIV-012**: Integration with third-party logistics (3PL) providers
- **KIV-013**: Real-time delivery tracking dashboard for customers
- **KIV-014**: Proof of delivery document management
- **KIV-015**: Delivery performance analytics (on-time delivery rate, picking accuracy)

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB17-SALES.md](../prd/prd-01/PRD01-SUB17-SALES.md)
- **Related Plans**:
  - PRD01-SUB17-PLAN01 (Customer & Quotation Management) - Customer foundation, pricing
  - PRD01-SUB17-PLAN02 (Sales Order Management) - Order processing, approval workflow
- **Integration Documentation**:
  - SUB14 (Inventory Management) - Stock movements, serial/lot tracking
  - SUB12 (Accounts Receivable) - Invoice generation on fulfillment
  - SUB15 (Backoffice) - Warehouse management
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
