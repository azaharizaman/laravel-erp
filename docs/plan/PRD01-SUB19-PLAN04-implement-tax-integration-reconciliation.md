---
plan: Tax Integration & Reconciliation (GL, AP/AR, Sales/Purchasing, Reconciliation)
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, taxation, tax-integration, tax-reconciliation, gl-integration, cross-module]
---

# PRD01-SUB19-PLAN04: Implement Tax Integration & Reconciliation

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan establishes integration between the taxation module and other ERP modules (GL, AP, AR, Sales, Purchasing) with comprehensive tax reconciliation. This plan ensures accurate tax posting, automatic tax determination on transactions, and reconciliation between GL and tax reports.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-TAX-008**: Reconcile tax amounts between GL and tax reports to identify discrepancies

### Integration Requirements
- **IR-TAX-001**: Integrate with General Ledger for automatic tax posting to control accounts
- **IR-TAX-002**: Integrate with AP and AR for automatic tax calculation on invoices
- **IR-TAX-003**: Integrate with Sales and Purchasing modules for automatic tax determination

### Business Rules
- **BR-TAX-002**: Negative tax amounts not allowed without explicit reversal document reference
- **BR-TAX-003**: Tax configuration changes cannot be backdated after period closing

### Scalability Requirements
- **SCR-TAX-001**: Support multiple tax jurisdictions simultaneously in a single transaction

### Constraints
- **CON-001**: Depends on PLAN01 (Tax Master Data) for rates, types, authorities, exemptions
- **CON-002**: Depends on PLAN02 (Tax Calculation Engine) for calculation logic per FR-TAX-003
- **CON-003**: Depends on PLAN03 (Tax Period & Filing) for period tracking per FR-TAX-007
- **CON-004**: Depends on SUB08 (General Ledger) for GL accounts and posting
- **CON-005**: Depends on SUB11 (AP) and SUB12 (AR) for invoice integration
- **CON-006**: Depends on SUB16 (Purchasing) and SUB17 (Sales) for transaction integration
- **CON-007**: Negative tax requires reversal_document_type and reversal_document_id per BR-TAX-002
- **CON-008**: Cannot backdate tax changes after period close per BR-TAX-003

### Guidelines
- **GUD-001**: Use repository pattern for all data access
- **GUD-002**: Use Laravel Actions pattern for integration operations
- **GUD-003**: Use event-driven architecture for cross-module communication
- **GUD-004**: Use listener pattern for external module events
- **GUD-005**: Emit events for tax posting and reconciliation

### Patterns
- **PAT-001**: Repository pattern for reconciliation data access
- **PAT-002**: Listener pattern for external module events per IR-TAX-001, IR-TAX-002, IR-TAX-003
- **PAT-003**: Event-driven pattern for cross-module integration
- **PAT-004**: Strategy pattern for different reconciliation types
- **PAT-005**: Observer pattern for period closure validation per BR-TAX-003

## 2. Implementation Steps

### GOAL-001: General Ledger Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-TAX-001 | GL integration for tax posting | | |
| BR-TAX-002 | Negative tax validation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000010_create_tax_gl_postings_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), tax_calculation_detail_id (BIGINT FK tax_calculation_details), gl_entry_id (BIGINT FK gl_entries nullable per IR-TAX-001), posting_date (DATE not null), tax_type_id (BIGINT FK tax_types), debit_account_id (BIGINT FK gl_accounts: tax receivable/payable), credit_account_id (BIGINT FK gl_accounts: tax expense/revenue), amount (DECIMAL 15,2), is_reversal (BOOLEAN default false per BR-TAX-002), reversal_document_type (VARCHAR 50 nullable: credit_note/debit_note), reversal_document_id (BIGINT nullable: reference to reversal doc), posted_at (TIMESTAMP nullable), posted_by (BIGINT FK users nullable), timestamps; indexes: tenant_id, tax_calculation_detail_id, gl_entry_id, posting_date, is_reversal; check: (is_reversal = false) OR (reversal_document_type IS NOT NULL AND reversal_document_id IS NOT NULL) enforcing BR-TAX-002; supports IR-TAX-001 | | |
| TASK-002 | Create model `TaxGLPosting.php` with traits: BelongsToTenant; fillable: tax_calculation_detail_id, gl_entry_id, posting_date, tax_type_id, debit_account_id, credit_account_id, amount, is_reversal, reversal_document_type, reversal_document_id, posted_at, posted_by; casts: tax_calculation_detail_id → int, gl_entry_id → int nullable, posting_date → date, tax_type_id → int, debit_account_id → int, credit_account_id → int, amount → float, is_reversal → boolean, posted_at → datetime nullable, posted_by → int nullable; relationships: tenant (belongsTo), taxCalculationDetail (belongsTo), glEntry (belongsTo GLEntry nullable), taxType (belongsTo), debitAccount (belongsTo GLAccount), creditAccount (belongsTo GLAccount), postedByUser (belongsTo User nullable), reversalDocument (morphTo); scopes: posted(), pending(), reversals(), byTaxType(int $typeId), inPeriod(Carbon $from, Carbon $to); methods: isPosted(): bool, isReversal(): bool per BR-TAX-002, hasReversalReference(): bool per BR-TAX-002, canPost(): bool; no activity logging (immutable audit records) per IR-TAX-001 | | |
| TASK-003 | Create factory `TaxGLPostingFactory.php` with states: posted(), pending(), reversal(), withReversalReference(string $type, int $id), forTaxType(TaxType $type), forCalculationDetail(TaxCalculationDetail $detail) | | |
| TASK-004 | Create contract `TaxGLPostingRepositoryContract.php` with methods: create(array $data): TaxGLPosting, findById(int $id): ?TaxGLPosting, getByCalculationDetail(int $detailId): ?TaxGLPosting, getPendingPostings(): Collection, getPostedInPeriod(Carbon $from, Carbon $to): Collection per IR-TAX-001, getReversals(): Collection per BR-TAX-002, getTotalPostedByTaxType(int $taxTypeId, Carbon $from, Carbon $to): float | | |
| TASK-005 | Create repository `TaxGLPostingRepository.php` implementing TaxGLPostingRepositoryContract; implement all methods; apply tenant scoping; optimize date range queries; getTotalPostedByTaxType aggregates amount by tax_type_id per IR-TAX-001 | | |
| TASK-006 | Create action `PostTaxToGLAction.php` using AsAction per IR-TAX-001; inject TaxGLPostingRepositoryContract, GLEntryRepositoryContract (from SUB08); handle(TaxCalculationDetail $detail): TaxGLPosting; validate detail not already posted; validate tax_type has gl_account configured; determine debit/credit accounts based on transaction type (sales: debit AR, credit tax payable; purchase: debit tax receivable, credit AP); if amount < 0: validate has reversal reference per BR-TAX-002, set is_reversal = true; create GL entry via GLEntryRepositoryContract with debit/credit lines; create TaxGLPosting with gl_entry_id, set posted_at = now(), posted_by = auth user; emit TaxPostedToGLEvent; return TaxGLPosting per IR-TAX-001 | | |
| TASK-007 | Create action `ReverseTaxGLPostingAction.php` using AsAction per BR-TAX-002; inject TaxGLPostingRepositoryContract, GLEntryRepositoryContract; handle(int $postingId, string $reversalType, int $reversalDocumentId): TaxGLPosting; retrieve original TaxGLPosting; validate not already reversed; create reversal GL entry (reverse debit/credit); create new TaxGLPosting with negative amount, is_reversal = true, reversal_document_type, reversal_document_id per BR-TAX-002; emit TaxReversalPostedEvent; return reversal posting | | |
| TASK-008 | Create event `TaxPostedToGLEvent.php` per IR-TAX-001; properties: tenant_id (int), tax_gl_posting_id (int), tax_calculation_detail_id (int), gl_entry_id (int), tax_type_id (int), amount (float), posting_date (Carbon), posted_at (Carbon); implements ShouldBroadcast | | |
| TASK-009 | Create listener `PostTaxToGLListener.php` per IR-TAX-001; listen to TaxCalculatedEvent from PLAN02; inject PostTaxToGLAction; handle(TaxCalculatedEvent $event); if auto_post_to_gl enabled in settings: call PostTaxToGLAction with calculation detail; used for automatic GL posting per IR-TAX-001 | | |

### GOAL-002: Accounts Payable & Receivable Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-TAX-002 | AP/AR integration for invoices | | |
| BR-TAX-002, BR-TAX-003 | Business rule enforcement | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-010 | Create listener `CalculateTaxOnPurchaseInvoiceListener.php` per IR-TAX-002; listen to PurchaseInvoiceCreatedEvent from SUB11; inject CalculateTaxAction from PLAN02; handle(PurchaseInvoiceCreatedEvent $event); for each invoice line: build calculation context (transaction_type = purchase_invoice, transaction_id = invoice.id, line_number, vendor jurisdiction, item_id, base_amount); call CalculateTaxAction per FR-TAX-003; update invoice line with calculated tax_amount; emit InvoiceTaxCalculatedEvent; supports IR-TAX-002 | | |
| TASK-011 | Create listener `CalculateTaxOnSalesInvoiceListener.php` per IR-TAX-002; listen to SalesInvoiceCreatedEvent from SUB12; inject CalculateTaxAction from PLAN02; handle(SalesInvoiceCreatedEvent $event); for each invoice line: build calculation context (transaction_type = sales_invoice, transaction_id = invoice.id, line_number, customer jurisdiction, item_id, base_amount); call CalculateTaxAction per FR-TAX-003; update invoice line with calculated tax_amount; emit InvoiceTaxCalculatedEvent; supports IR-TAX-002 | | |
| TASK-012 | Create listener `PostInvoiceTaxToGLListener.php` per IR-TAX-001 and IR-TAX-002; listen to InvoiceConfirmedEvent from SUB11/SUB12; inject PostTaxToGLAction; handle(InvoiceConfirmedEvent $event); retrieve all TaxCalculationDetails for invoice; for each detail: call PostTaxToGLAction; aggregate posting IDs; emit InvoiceTaxPostedEvent; supports automatic GL posting for invoices per IR-TAX-001 and IR-TAX-002 | | |
| TASK-013 | Create listener `HandleInvoiceReversalListener.php` per BR-TAX-002; listen to InvoiceReversedEvent (credit note/debit note) from SUB11/SUB12; inject ReverseTaxGLPostingAction; handle(InvoiceReversedEvent $event); retrieve original invoice tax postings; for each posting: call ReverseTaxGLPostingAction with reversal_document_type = event.reversal_type, reversal_document_id = event.reversal_id per BR-TAX-002; ensures negative tax requires reversal reference | | |
| TASK-014 | Create listener `ValidateTaxRateChangesListener.php` per BR-TAX-003; listen to TaxRateUpdatedEvent from PLAN01; inject TaxPeriodRepositoryContract from PLAN03; handle(TaxRateUpdatedEvent $event); if effective_from is backdated: check if any periods for that date range are closed per BR-TAX-003; if closed period affected: throw CannotBackdateTaxRateException, rollback update; enforces BR-TAX-003 | | |

### GOAL-003: Sales & Purchasing Module Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-TAX-003 | Sales/Purchasing integration | | |
| SCR-TAX-001 | Multiple jurisdiction support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create listener `CalculateTaxOnSalesOrderListener.php` per IR-TAX-003; listen to SalesOrderConfirmedEvent from SUB17; inject CalculateTaxAction from PLAN02; handle(SalesOrderConfirmedEvent $event); for each order line: build calculation context (transaction_type = sales_order, customer jurisdiction per SCR-TAX-001, item_id, base_amount); call CalculateTaxAction per FR-TAX-003; store calculation details for order; emit OrderTaxCalculatedEvent; supports automatic tax determination per IR-TAX-003 | | |
| TASK-016 | Create listener `CalculateTaxOnPurchaseOrderListener.php` per IR-TAX-003; listen to PurchaseOrderConfirmedEvent from SUB16; inject CalculateTaxAction from PLAN02; handle(PurchaseOrderConfirmedEvent $event); for each order line: build calculation context (transaction_type = purchase_order, vendor jurisdiction per SCR-TAX-001, item_id, base_amount); call CalculateTaxAction per FR-TAX-003; store calculation details for order; emit OrderTaxCalculatedEvent; supports automatic tax determination per IR-TAX-003 | | |
| TASK-017 | Create action `RecalculateTaxOnPriceChangeAction.php` using AsAction per IR-TAX-003; inject CalculateTaxAction, TaxCalculationDetailRepositoryContract; handle(string $transactionType, int $transactionId, array $lineChanges); for each changed line: delete existing calculation details, recalculate tax with new base_amount; return updated details; used when prices change on orders/invoices per IR-TAX-003 | | |
| TASK-018 | Create action `ApplyTaxExemptionToTransactionAction.php` using AsAction per IR-TAX-003; inject TaxExemptionRepositoryContract, RecalculateTaxAction; handle(string $transactionType, int $transactionId, int $exemptionId); validate exemption applicable; recalculate all line taxes with exemption; return updated details; used to apply exemptions retrospectively per FR-TAX-004 and IR-TAX-003 | | |

### GOAL-004: Tax Reconciliation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-008 | Tax reconciliation between GL and reports | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create migration `2025_01_01_000011_create_tax_reconciliations_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), tax_period_id (BIGINT FK tax_periods), reconciliation_date (DATE not null), tax_type_id (BIGINT FK tax_types), gl_tax_collected (DECIMAL 15,2: from GL postings per IR-TAX-001), report_tax_collected (DECIMAL 15,2: from tax report per FR-TAX-005), gl_tax_paid (DECIMAL 15,2: from GL postings), report_tax_paid (DECIMAL 15,2: from tax report), collection_variance (DECIMAL 15,2: gl_tax_collected - report_tax_collected per FR-TAX-008), payment_variance (DECIMAL 15,2: gl_tax_paid - report_tax_paid per FR-TAX-008), is_reconciled (BOOLEAN default false), reconciled_at (TIMESTAMP nullable), reconciled_by (BIGINT FK users nullable), reconciliation_notes (TEXT nullable), timestamps; indexes: tenant_id, tax_period_id, tax_type_id, is_reconciled; supports FR-TAX-008 | | |
| TASK-020 | Create model `TaxReconciliation.php` with traits: BelongsToTenant, LogsActivity; fillable: tax_period_id, reconciliation_date, tax_type_id, gl_tax_collected, report_tax_collected, gl_tax_paid, report_tax_paid, collection_variance, payment_variance, is_reconciled, reconciled_at, reconciled_by, reconciliation_notes; casts: tax_period_id → int, reconciliation_date → date, tax_type_id → int, gl_tax_collected → float, report_tax_collected → float, gl_tax_paid → float, report_tax_paid → float, collection_variance → float, payment_variance → float, is_reconciled → boolean, reconciled_at → datetime nullable, reconciled_by → int nullable; relationships: tenant (belongsTo), taxPeriod (belongsTo), taxType (belongsTo), reconciledByUser (belongsTo User nullable); scopes: reconciled(), pending(), withVariance(), byPeriod(int $periodId), byTaxType(int $typeId); methods: hasVariance(): bool per FR-TAX-008, getVariancePercentage(): float, isReconciled(): bool, canReconcile(): bool, markReconciled(): void; Spatie activity log: log is_reconciled, reconciliation_notes changes per FR-TAX-008 | | |
| TASK-021 | Create factory `TaxReconciliationFactory.php` with states: reconciled(), pending(), withVariance(), noVariance(), forPeriod(TaxPeriod $period), forTaxType(TaxType $type) | | |
| TASK-022 | Create contract `TaxReconciliationRepositoryContract.php` with methods: create(array $data): TaxReconciliation, findById(int $id): ?TaxReconciliation, getByPeriod(int $periodId): Collection, getByTaxType(int $taxTypeId, Carbon $from, Carbon $to): Collection, getPendingReconciliations(): Collection per FR-TAX-008, getReconciliationsWithVariance(): Collection per FR-TAX-008, getReconciliationSummary(int $periodId): array | | |
| TASK-023 | Create repository `TaxReconciliationRepository.php` implementing TaxReconciliationRepositoryContract; implement all methods; apply tenant scoping; getReconciliationsWithVariance filters where collection_variance != 0 OR payment_variance != 0 per FR-TAX-008; getReconciliationSummary aggregates variances by tax_type | | |
| TASK-024 | Create service `TaxReconciliationService.php` per FR-TAX-008; inject TaxReconciliationRepositoryContract, TaxGLPostingRepositoryContract, TaxReportGeneratorContract from PLAN03, TaxPeriodRepositoryContract from PLAN03; method reconcilePeriod(int $periodId): Collection per FR-TAX-008; algorithm: (1) validate period is closed, (2) get all tax types for period's authority, (3) for each tax_type: (a) get GL totals from TaxGLPostingRepository->getTotalPostedByTaxType() per IR-TAX-001, (b) generate tax report for period per FR-TAX-005, (c) extract report totals (collected/paid), (d) calculate variances: collection_variance = gl_collected - report_collected, payment_variance = gl_paid - report_paid per FR-TAX-008, (e) create TaxReconciliation record, (4) return Collection of reconciliations; identifies discrepancies per FR-TAX-008 | | |
| TASK-025 | Create action `ReconcileTaxPeriodAction.php` using AsAction per FR-TAX-008; inject TaxReconciliationService; handle(int $periodId): Collection; call TaxReconciliationService->reconcilePeriod(); emit TaxPeriodReconciledEvent; return reconciliations per FR-TAX-008 | | |
| TASK-026 | Create action `MarkReconciliationCompleteAction.php` using AsAction per FR-TAX-008; inject TaxReconciliationRepositoryContract; handle(int $reconciliationId, string $notes): TaxReconciliation; validate reconciliation exists; update is_reconciled = true, reconciled_at = now(), reconciled_by = auth user, reconciliation_notes; emit ReconciliationCompletedEvent; return reconciliation per FR-TAX-008 | | |
| TASK-027 | Create action `InvestigateVarianceAction.php` using AsAction per FR-TAX-008; inject TaxCalculationDetailRepositoryContract, TaxGLPostingRepositoryContract; handle(int $reconciliationId): array; retrieve TaxReconciliation; get all calculation details for period and tax_type; get all GL postings for period and tax_type; compare line-by-line; identify missing GL postings, orphan GL postings, amount mismatches; return investigation report: {missing_postings: array, orphan_postings: array, amount_mismatches: array, suggested_corrections: array} per FR-TAX-008 | | |
| TASK-028 | Create event `TaxPeriodReconciledEvent.php` per FR-TAX-008; properties: tenant_id (int), tax_period_id (int), reconciliation_ids (array), total_collection_variance (float), total_payment_variance (float), has_variance (bool per FR-TAX-008), reconciled_at (Carbon); implements ShouldBroadcast | | |
| TASK-029 | Create listener `NotifyOnReconciliationVarianceListener.php` per FR-TAX-008; listen to TaxPeriodReconciledEvent; inject NotificationService; handle(TaxPeriodReconciledEvent $event); if has_variance: notify tax administrators via email/notification; include variance details, investigation link; supports proactive variance resolution per FR-TAX-008 | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| All integration requirements | Complete API and testing | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-030 | Create policy `TaxGLPostingPolicy.php` with methods: viewAny requiring 'view-tax-gl-postings', view requiring 'view-tax-gl-postings', post requiring 'post-tax-to-gl' per IR-TAX-001, reverse requiring 'reverse-tax-gl-posting' per BR-TAX-002; enforce tenant scope | | |
| TASK-031 | Create policy `TaxReconciliationPolicy.php` with methods: viewAny requiring 'view-tax-reconciliations', view requiring 'view-tax-reconciliations', reconcile requiring 'reconcile-tax-periods' per FR-TAX-008, investigate requiring 'investigate-tax-variances' per FR-TAX-008, markComplete requiring 'complete-tax-reconciliations'; enforce tenant scope | | |
| TASK-032 | Create API controller `TaxGLPostingController.php` with routes: index (GET /api/v1/taxation/gl-postings) per IR-TAX-001, show (GET /gl-postings/{id}), post (POST /gl-postings/post) runs PostTaxToGLAction per IR-TAX-001, reverse (POST /gl-postings/{id}/reverse) runs ReverseTaxGLPostingAction per BR-TAX-002, pending (GET /gl-postings/pending), byPeriod (GET /gl-postings/by-period?from=&to=); authorize via TaxGLPostingPolicy; use TaxGLPostingResource | | |
| TASK-033 | Create API controller `TaxReconciliationController.php` with routes: index (GET /api/v1/taxation/reconciliations) per FR-TAX-008, show (GET /reconciliations/{id}), reconcile (POST /reconciliations/reconcile) runs ReconcileTaxPeriodAction per FR-TAX-008, investigate (POST /reconciliations/{id}/investigate) runs InvestigateVarianceAction per FR-TAX-008, complete (POST /reconciliations/{id}/complete) runs MarkReconciliationCompleteAction per FR-TAX-008, pending (GET /reconciliations/pending), withVariance (GET /reconciliations/with-variance) per FR-TAX-008; authorize via TaxReconciliationPolicy; use TaxReconciliationResource | | |
| TASK-034 | Create API controller `TaxIntegrationController.php` with routes: recalculateTax (POST /api/v1/taxation/integration/recalculate) runs RecalculateTaxOnPriceChangeAction per IR-TAX-003, applyExemption (POST /integration/apply-exemption) runs ApplyTaxExemptionToTransactionAction per IR-TAX-003, postToGL (POST /integration/post-to-gl) manual GL posting per IR-TAX-001, syncWithGL (POST /integration/sync-with-gl) bulk sync per IR-TAX-001; authorize via TaxIntegrationPolicy | | |
| TASK-035 | Create form request `PostTaxToGLRequest.php` with validation: tax_calculation_detail_id (required, exists:tax_calculation_details,id per IR-TAX-001), posting_date (required, date), force_post (nullable, boolean: override checks) | | |
| TASK-036 | Create form request `ReverseTaxGLPostingRequest.php` with validation: posting_id (required, exists:tax_gl_postings,id), reversal_document_type (required, in:credit_note,debit_note per BR-TAX-002), reversal_document_id (required, integer per BR-TAX-002), notes (nullable, string) | | |
| TASK-037 | Create form request `ReconcileTaxPeriodRequest.php` with validation: period_id (required, exists:tax_periods,id per FR-TAX-008), include_tax_types (nullable, array: specific tax types to reconcile), auto_complete_if_no_variance (nullable, boolean per FR-TAX-008) | | |
| TASK-038 | Create form request `RecalculateTaxRequest.php` with validation: transaction_type (required, in per IR-TAX-003), transaction_id (required, integer), line_changes (required, array), line_changes.*.line_number (required, integer), line_changes.*.new_base_amount (required, numeric) | | |
| TASK-039 | Create API resource `TaxGLPostingResource.php` with fields: id, taxCalculationDetail (nested TaxCalculationDetailResource minimal), glEntry (nested GLEntryResource minimal from SUB08 per IR-TAX-001), posting_date, taxType (nested TaxTypeResource minimal), debitAccount (nested GLAccountResource minimal), creditAccount (nested GLAccountResource minimal), amount, is_reversal per BR-TAX-002, reversal_document_type per BR-TAX-002, reversal_document_id per BR-TAX-002, is_posted (computed), posted_at, postedByUser (nested UserResource minimal), created_at | | |
| TASK-040 | Create API resource `TaxReconciliationResource.php` with fields: id, taxPeriod (nested TaxPeriodResource minimal from PLAN03), reconciliation_date, taxType (nested TaxTypeResource minimal), gl_tax_collected per IR-TAX-001, report_tax_collected per FR-TAX-005, gl_tax_paid per IR-TAX-001, report_tax_paid per FR-TAX-005, collection_variance per FR-TAX-008, payment_variance per FR-TAX-008, has_variance (computed per FR-TAX-008), variance_percentage (computed), is_reconciled per FR-TAX-008, reconciled_at, reconciledByUser (nested UserResource minimal), reconciliation_notes, created_at, updated_at | | |
| TASK-041 | Write comprehensive unit tests for models: test TaxGLPosting isReversal() per BR-TAX-002, test TaxGLPosting hasReversalReference() per BR-TAX-002, test TaxReconciliation hasVariance() per FR-TAX-008, test TaxReconciliation getVariancePercentage() per FR-TAX-008 | | |
| TASK-042 | Write comprehensive unit tests for actions: test PostTaxToGLAction per IR-TAX-001, test ReverseTaxGLPostingAction validates reversal reference per BR-TAX-002, test ReconcileTaxPeriodAction per FR-TAX-008, test InvestigateVarianceAction per FR-TAX-008, test RecalculateTaxOnPriceChangeAction per IR-TAX-003 | | |
| TASK-043 | Write comprehensive unit tests for services: test TaxReconciliationService->reconcilePeriod() per FR-TAX-008, test variance calculation accuracy per FR-TAX-008, test GL total aggregation per IR-TAX-001, test report total extraction per FR-TAX-005 | | |
| TASK-044 | Write comprehensive unit tests for listeners: test PostTaxToGLListener per IR-TAX-001, test CalculateTaxOnPurchaseInvoiceListener per IR-TAX-002, test CalculateTaxOnSalesInvoiceListener per IR-TAX-002, test HandleInvoiceReversalListener per BR-TAX-002, test ValidateTaxRateChangesListener per BR-TAX-003 | | |
| TASK-045 | Write feature tests for GL integration: test post tax to GL via API per IR-TAX-001, test automatic GL posting on invoice confirmation per IR-TAX-002, test reverse tax GL posting per BR-TAX-002, test reversal requires document reference per BR-TAX-002, test GL posting creates GL entry in SUB08 per IR-TAX-001 | | |
| TASK-046 | Write feature tests for AP/AR integration: test automatic tax calculation on purchase invoice per IR-TAX-002, test automatic tax calculation on sales invoice per IR-TAX-002, test tax posting on invoice confirmation per IR-TAX-002, test invoice reversal creates tax reversal per BR-TAX-002 | | |
| TASK-047 | Write feature tests for Sales/Purchasing integration: test automatic tax on sales order per IR-TAX-003, test automatic tax on purchase order per IR-TAX-003, test tax recalculation on price change per IR-TAX-003, test apply exemption to order per IR-TAX-003 | | |
| TASK-048 | Write feature tests for reconciliation: test reconcile tax period via API per FR-TAX-008, test reconciliation calculates variances per FR-TAX-008, test investigate variance per FR-TAX-008, test mark reconciliation complete per FR-TAX-008, test reconciliation with no variance | | |
| TASK-049 | Write integration tests: test complete invoice-to-GL-to-reconciliation workflow per IR-TAX-001, IR-TAX-002, FR-TAX-008, test sales order tax calculation and posting per IR-TAX-003, test period reconciliation with GL postings per FR-TAX-008, test reversal workflow per BR-TAX-002, test backdate prevention per BR-TAX-003 | | |
| TASK-050 | Write security tests: test TaxGLPostingPolicy authorization per IR-TAX-001, test TaxReconciliationPolicy authorization per FR-TAX-008, test tenant isolation for GL postings per IR-TAX-001, test tenant isolation for reconciliations per FR-TAX-008, test non-authorized cannot post to GL per IR-TAX-001 | | |
| TASK-051 | Write event tests: test TaxPostedToGLEvent dispatched per IR-TAX-001, test TaxPeriodReconciledEvent dispatched per FR-TAX-008, test event contains variance data per FR-TAX-008, test NotifyOnReconciliationVarianceListener per FR-TAX-008 | | |
| TASK-052 | Write business rule tests: test negative tax requires reversal reference per BR-TAX-002, test cannot create negative tax without reversal_document_type per BR-TAX-002, test cannot backdate tax rate after period close per BR-TAX-003, test ValidateTaxRateChangesListener prevents backdating per BR-TAX-003 | | |
| TASK-053 | Write acceptance tests: test complete tax lifecycle with GL integration per IR-TAX-001, test invoice tax calculation and reconciliation per IR-TAX-002 and FR-TAX-008, test order tax determination per IR-TAX-003, test reconciliation variance investigation per FR-TAX-008, test reversal workflow per BR-TAX-002, test business rule enforcement per BR-TAX-002 and BR-TAX-003 | | |
| TASK-054 | Set up Pest configuration for tax integration tests; configure database transactions; seed test data (GL accounts from SUB08, invoices from SUB11/SUB12, orders from SUB16/SUB17); mock external module events | | |
| TASK-055 | Achieve minimum 80% code coverage for tax integration module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-056 | Create API documentation: document GL posting endpoints per IR-TAX-001, document reconciliation endpoints per FR-TAX-008, document integration endpoints per IR-TAX-003, document reversal process per BR-TAX-002, document event schemas (TaxPostedToGLEvent, TaxPeriodReconciledEvent) | | |
| TASK-057 | Create user guide: understanding GL integration per IR-TAX-001, automatic tax on invoices per IR-TAX-002, automatic tax on orders per IR-TAX-003, performing tax reconciliation per FR-TAX-008, investigating variances per FR-TAX-008, handling reversals per BR-TAX-002 | | |
| TASK-058 | Create technical documentation: GL integration architecture per IR-TAX-001, event-driven integration design per IR-TAX-002 and IR-TAX-003, reconciliation algorithm per FR-TAX-008, business rule enforcement per BR-TAX-002 and BR-TAX-003, listener pattern implementation, cross-module communication | | |
| TASK-059 | Create integration guide: setting up GL accounts for tax per IR-TAX-001, configuring automatic tax posting per IR-TAX-001, enabling invoice tax calculation per IR-TAX-002, enabling order tax determination per IR-TAX-003, scheduling reconciliation jobs per FR-TAX-008, troubleshooting integration issues | | |
| TASK-060 | Create admin guide: managing GL postings per IR-TAX-001, handling reconciliation variances per FR-TAX-008, processing reversals per BR-TAX-002, monitoring integration health, auditing tax transactions, enforcing business rules per BR-TAX-002 and BR-TAX-003 | | |
| TASK-061 | Update package README with tax integration features: GL integration per IR-TAX-001, AP/AR integration per IR-TAX-002, Sales/Purchasing integration per IR-TAX-003, tax reconciliation per FR-TAX-008, business rule enforcement per BR-TAX-002 and BR-TAX-003 | | |
| TASK-062 | Validate acceptance criteria: GL integration functional per IR-TAX-001, AP/AR integration functional per IR-TAX-002, Sales/Purchasing integration functional per IR-TAX-003, tax reconciliation working per FR-TAX-008, negative tax requires reversal per BR-TAX-002, cannot backdate after period close per BR-TAX-003, multiple jurisdictions supported per SCR-TAX-001 | | |
| TASK-063 | Conduct code review: verify IR-TAX-001 implementation, verify IR-TAX-002 implementation, verify IR-TAX-003 implementation, verify FR-TAX-008 implementation, verify BR-TAX-002 enforcement, verify BR-TAX-003 enforcement, verify SCR-TAX-001 multi-jurisdiction | | |
| TASK-064 | Run full test suite for tax integration module; verify all tests pass; verify GL posting creates entries per IR-TAX-001; verify reconciliation identifies variances per FR-TAX-008; verify business rules enforced per BR-TAX-002 and BR-TAX-003 | | |
| TASK-065 | Deploy to staging; test GL posting per IR-TAX-001; test invoice tax calculation per IR-TAX-002; test order tax determination per IR-TAX-003; test period reconciliation per FR-TAX-008; test reversal workflow per BR-TAX-002; verify business rules per BR-TAX-003; monitor integration success rate | | |
| TASK-066 | Create seeder `TaxGLPostingSeeder.php` with sample postings: sales tax posting, purchase tax posting, reversal posting per BR-TAX-002 | | |
| TASK-067 | Create seeder `TaxReconciliationSeeder.php` with sample reconciliations: reconciled with no variance, pending with variance per FR-TAX-008, investigated variance | | |

## 3. Alternatives

- **ALT-001**: Manual GL posting only - rejected; violates IR-TAX-001 automatic posting requirement
- **ALT-002**: No tax reconciliation - rejected; violates FR-TAX-008 reconciliation requirement
- **ALT-003**: Allow negative tax without reversal reference - rejected; violates BR-TAX-002 audit trail requirement
- **ALT-004**: Allow backdating after period close - rejected; violates BR-TAX-003 integrity requirement
- **ALT-005**: Synchronous invoice tax calculation - rejected; impacts performance for large invoices
- **ALT-006**: Single jurisdiction per transaction - rejected; violates SCR-TAX-001 multi-jurisdiction requirement

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: PLAN01 (Tax Master Data) - Tax rates, types, authorities, exemptions
- **DEP-002**: PLAN02 (Tax Calculation Engine) - CalculateTaxAction per FR-TAX-003
- **DEP-003**: PLAN03 (Tax Period & Filing) - Tax periods per FR-TAX-007, report generation per FR-TAX-005
- **DEP-004**: SUB08 (General Ledger) - GL accounts, GL entries per IR-TAX-001
- **DEP-005**: SUB11 (Accounts Payable) - Purchase invoices per IR-TAX-002
- **DEP-006**: SUB12 (Accounts Receivable) - Sales invoices per IR-TAX-002
- **DEP-007**: SUB16 (Purchasing) - Purchase orders per IR-TAX-003
- **DEP-008**: SUB17 (Sales) - Sales orders per IR-TAX-003
- **DEP-009**: Laravel Events - For cross-module communication

### Optional Dependencies
- **DEP-010**: Notification service - For variance alerts per FR-TAX-008

### Package Dependencies
- **DEP-011**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-012**: spatie/laravel-activitylog ^4.0 - Audit logging per FR-TAX-008

## 5. Files

### Models & Enums
- `packages/taxation/src/Models/TaxGLPosting.php` - GL posting model per IR-TAX-001
- `packages/taxation/src/Models/TaxReconciliation.php` - Reconciliation model per FR-TAX-008

### Services
- `packages/taxation/src/Services/TaxReconciliationService.php` - Reconciliation service per FR-TAX-008

### Contracts & Repositories
- `packages/taxation/src/Contracts/TaxGLPostingRepositoryContract.php`
- `packages/taxation/src/Repositories/TaxGLPostingRepository.php` - GL posting persistence per IR-TAX-001
- `packages/taxation/src/Contracts/TaxReconciliationRepositoryContract.php`
- `packages/taxation/src/Repositories/TaxReconciliationRepository.php` - Reconciliation persistence per FR-TAX-008

### Actions
- `packages/taxation/src/Actions/PostTaxToGLAction.php` - Post to GL per IR-TAX-001
- `packages/taxation/src/Actions/ReverseTaxGLPostingAction.php` - Reverse GL posting per BR-TAX-002
- `packages/taxation/src/Actions/RecalculateTaxOnPriceChangeAction.php` - Recalculate on price change per IR-TAX-003
- `packages/taxation/src/Actions/ApplyTaxExemptionToTransactionAction.php` - Apply exemption per IR-TAX-003
- `packages/taxation/src/Actions/ReconcileTaxPeriodAction.php` - Reconcile period per FR-TAX-008
- `packages/taxation/src/Actions/MarkReconciliationCompleteAction.php` - Complete reconciliation per FR-TAX-008
- `packages/taxation/src/Actions/InvestigateVarianceAction.php` - Investigate variance per FR-TAX-008

### Events
- `packages/taxation/src/Events/TaxPostedToGLEvent.php` - Tax posted event per IR-TAX-001
- `packages/taxation/src/Events/TaxPeriodReconciledEvent.php` - Period reconciled event per FR-TAX-008

### Listeners
- `packages/taxation/src/Listeners/PostTaxToGLListener.php` - Auto post to GL per IR-TAX-001
- `packages/taxation/src/Listeners/CalculateTaxOnPurchaseInvoiceListener.php` - Purchase invoice tax per IR-TAX-002
- `packages/taxation/src/Listeners/CalculateTaxOnSalesInvoiceListener.php` - Sales invoice tax per IR-TAX-002
- `packages/taxation/src/Listeners/PostInvoiceTaxToGLListener.php` - Invoice tax GL posting per IR-TAX-001 and IR-TAX-002
- `packages/taxation/src/Listeners/HandleInvoiceReversalListener.php` - Invoice reversal per BR-TAX-002
- `packages/taxation/src/Listeners/ValidateTaxRateChangesListener.php` - Validate backdating per BR-TAX-003
- `packages/taxation/src/Listeners/CalculateTaxOnSalesOrderListener.php` - Sales order tax per IR-TAX-003
- `packages/taxation/src/Listeners/CalculateTaxOnPurchaseOrderListener.php` - Purchase order tax per IR-TAX-003
- `packages/taxation/src/Listeners/NotifyOnReconciliationVarianceListener.php` - Variance notification per FR-TAX-008

### Controllers, Requests & Resources
- `packages/taxation/src/Http/Controllers/TaxGLPostingController.php` - GL posting API per IR-TAX-001
- `packages/taxation/src/Http/Controllers/TaxReconciliationController.php` - Reconciliation API per FR-TAX-008
- `packages/taxation/src/Http/Controllers/TaxIntegrationController.php` - Integration endpoints per IR-TAX-003
- `packages/taxation/src/Http/Requests/PostTaxToGLRequest.php` - GL posting validation per IR-TAX-001
- `packages/taxation/src/Http/Requests/ReverseTaxGLPostingRequest.php` - Reversal validation per BR-TAX-002
- `packages/taxation/src/Http/Requests/ReconcileTaxPeriodRequest.php` - Reconciliation validation per FR-TAX-008
- `packages/taxation/src/Http/Requests/RecalculateTaxRequest.php` - Recalculation validation per IR-TAX-003
- `packages/taxation/src/Http/Resources/TaxGLPostingResource.php` - GL posting transformation per IR-TAX-001
- `packages/taxation/src/Http/Resources/TaxReconciliationResource.php` - Reconciliation transformation per FR-TAX-008

### Policies
- `packages/taxation/src/Policies/TaxGLPostingPolicy.php` - GL posting authorization per IR-TAX-001
- `packages/taxation/src/Policies/TaxReconciliationPolicy.php` - Reconciliation authorization per FR-TAX-008

### Database
- `packages/taxation/database/migrations/2025_01_01_000010_create_tax_gl_postings_table.php` - GL postings table per IR-TAX-001
- `packages/taxation/database/migrations/2025_01_01_000011_create_tax_reconciliations_table.php` - Reconciliations table per FR-TAX-008
- `packages/taxation/database/factories/*Factory.php` - All factories
- `packages/taxation/database/seeders/TaxGLPostingSeeder.php` - GL posting samples per IR-TAX-001
- `packages/taxation/database/seeders/TaxReconciliationSeeder.php` - Reconciliation samples per FR-TAX-008

### Tests
- `packages/taxation/tests/Unit/Models/*Test.php` - Model tests
- `packages/taxation/tests/Unit/Actions/*Test.php` - Action tests
- `packages/taxation/tests/Unit/Services/TaxReconciliationServiceTest.php` - Service tests per FR-TAX-008
- `packages/taxation/tests/Unit/Listeners/*Test.php` - Listener tests
- `packages/taxation/tests/Feature/TaxGLIntegrationTest.php` - GL integration tests per IR-TAX-001
- `packages/taxation/tests/Feature/TaxAPARIntegrationTest.php` - AP/AR integration tests per IR-TAX-002
- `packages/taxation/tests/Feature/TaxSalesPurchasingIntegrationTest.php` - Sales/Purchasing tests per IR-TAX-003
- `packages/taxation/tests/Feature/TaxReconciliationTest.php` - Reconciliation tests per FR-TAX-008
- `packages/taxation/tests/Integration/TaxIntegrationWorkflowTest.php` - End-to-end integration tests
- `packages/taxation/tests/Security/TaxIntegrationAuthorizationTest.php` - Authorization tests
- `packages/taxation/tests/Event/TaxIntegrationEventTest.php` - Event tests per IR-TAX-001 and FR-TAX-008
- `packages/taxation/tests/BusinessRule/TaxBusinessRuleTest.php` - Business rule tests per BR-TAX-002 and BR-TAX-003

## 6. Testing

### Unit Tests (20 tests)
- **TEST-001**: TaxGLPosting isReversal() per BR-TAX-002
- **TEST-002**: TaxGLPosting hasReversalReference() per BR-TAX-002
- **TEST-003**: TaxReconciliation hasVariance() per FR-TAX-008
- **TEST-004**: TaxReconciliation getVariancePercentage() per FR-TAX-008
- **TEST-005**: PostTaxToGLAction per IR-TAX-001
- **TEST-006**: ReverseTaxGLPostingAction validates reversal per BR-TAX-002
- **TEST-007**: ReconcileTaxPeriodAction per FR-TAX-008
- **TEST-008**: InvestigateVarianceAction per FR-TAX-008
- **TEST-009**: RecalculateTaxOnPriceChangeAction per IR-TAX-003
- **TEST-010**: TaxReconciliationService->reconcilePeriod() per FR-TAX-008
- **TEST-011**: Variance calculation accuracy per FR-TAX-008
- **TEST-012**: GL total aggregation per IR-TAX-001
- **TEST-013**: PostTaxToGLListener per IR-TAX-001
- **TEST-014**: CalculateTaxOnPurchaseInvoiceListener per IR-TAX-002
- **TEST-015**: CalculateTaxOnSalesInvoiceListener per IR-TAX-002
- **TEST-016**: HandleInvoiceReversalListener per BR-TAX-002
- **TEST-017**: ValidateTaxRateChangesListener per BR-TAX-003
- **TEST-018**: CalculateTaxOnSalesOrderListener per IR-TAX-003
- **TEST-019**: CalculateTaxOnPurchaseOrderListener per IR-TAX-003
- **TEST-020**: NotifyOnReconciliationVarianceListener per FR-TAX-008

### Feature Tests (18 tests)
- **TEST-021**: Post tax to GL via API per IR-TAX-001
- **TEST-022**: Automatic GL posting on invoice confirmation per IR-TAX-002
- **TEST-023**: Reverse tax GL posting per BR-TAX-002
- **TEST-024**: Reversal requires document reference per BR-TAX-002
- **TEST-025**: GL posting creates GL entry per IR-TAX-001
- **TEST-026**: Automatic tax on purchase invoice per IR-TAX-002
- **TEST-027**: Automatic tax on sales invoice per IR-TAX-002
- **TEST-028**: Tax posting on invoice confirmation per IR-TAX-002
- **TEST-029**: Invoice reversal creates tax reversal per BR-TAX-002
- **TEST-030**: Automatic tax on sales order per IR-TAX-003
- **TEST-031**: Automatic tax on purchase order per IR-TAX-003
- **TEST-032**: Tax recalculation on price change per IR-TAX-003
- **TEST-033**: Apply exemption to order per IR-TAX-003
- **TEST-034**: Reconcile tax period via API per FR-TAX-008
- **TEST-035**: Reconciliation calculates variances per FR-TAX-008
- **TEST-036**: Investigate variance per FR-TAX-008
- **TEST-037**: Mark reconciliation complete per FR-TAX-008
- **TEST-038**: Reconciliation with no variance

### Integration Tests (5 tests)
- **TEST-039**: Invoice-to-GL-to-reconciliation workflow per IR-TAX-001, IR-TAX-002, FR-TAX-008
- **TEST-040**: Sales order tax and posting per IR-TAX-003
- **TEST-041**: Period reconciliation with GL postings per FR-TAX-008
- **TEST-042**: Reversal workflow per BR-TAX-002
- **TEST-043**: Backdate prevention per BR-TAX-003

### Security Tests (5 tests)
- **TEST-044**: TaxGLPostingPolicy authorization per IR-TAX-001
- **TEST-045**: TaxReconciliationPolicy authorization per FR-TAX-008
- **TEST-046**: Tenant isolation for GL postings per IR-TAX-001
- **TEST-047**: Tenant isolation for reconciliations per FR-TAX-008
- **TEST-048**: Non-authorized cannot post to GL per IR-TAX-001

### Event Tests (4 tests)
- **TEST-049**: TaxPostedToGLEvent dispatched per IR-TAX-001
- **TEST-050**: TaxPeriodReconciledEvent dispatched per FR-TAX-008
- **TEST-051**: Event contains variance data per FR-TAX-008
- **TEST-052**: NotifyOnReconciliationVarianceListener per FR-TAX-008

### Business Rule Tests (4 tests)
- **TEST-053**: Negative tax requires reversal reference per BR-TAX-002
- **TEST-054**: Cannot create negative tax without reversal_document_type per BR-TAX-002
- **TEST-055**: Cannot backdate tax rate after period close per BR-TAX-003
- **TEST-056**: ValidateTaxRateChangesListener prevents backdating per BR-TAX-003

### Acceptance Tests (6 tests)
- **TEST-057**: Complete tax lifecycle with GL per IR-TAX-001
- **TEST-058**: Invoice tax and reconciliation per IR-TAX-002 and FR-TAX-008
- **TEST-059**: Order tax determination per IR-TAX-003
- **TEST-060**: Reconciliation variance investigation per FR-TAX-008
- **TEST-061**: Reversal workflow per BR-TAX-002
- **TEST-062**: Business rule enforcement per BR-TAX-002 and BR-TAX-003

**Total Test Coverage:** 62 tests (20 unit + 18 feature + 5 integration + 5 security + 4 event + 4 business rule + 6 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: GL integration failures - Mitigation: Retry mechanism, queue failed postings, alert administrators per IR-TAX-001
- **RISK-002**: Reconciliation variance detection failure - Mitigation: Automated testing, manual verification process per FR-TAX-008
- **RISK-003**: Business rule bypass - Mitigation: Database constraints, observer validation per BR-TAX-002 and BR-TAX-003
- **RISK-004**: Cross-module event loss - Mitigation: Event persistence, queue reliability monitoring

### Assumptions
- **ASSUMPTION-001**: SUB08 (GL) module operational per IR-TAX-001
- **ASSUMPTION-002**: SUB11 (AP) and SUB12 (AR) emit events per IR-TAX-002
- **ASSUMPTION-003**: SUB16 (Purchasing) and SUB17 (Sales) emit events per IR-TAX-003
- **ASSUMPTION-004**: GL accounts configured for tax types per IR-TAX-001
- **ASSUMPTION-005**: Tax periods closed before reconciliation per FR-TAX-008
- **ASSUMPTION-006**: Reversal documents exist in source modules per BR-TAX-002
- **ASSUMPTION-007**: Maximum 5000 tax transactions per period for reconciliation performance

## 8. KIV for Future Implementations

- **KIV-001**: Real-time reconciliation (continuous rather than period-end) per FR-TAX-008
- **KIV-002**: Machine learning for variance pattern detection per FR-TAX-008
- **KIV-003**: Automatic variance correction suggestions per FR-TAX-008
- **KIV-004**: Multi-currency GL posting per IR-TAX-001
- **KIV-005**: Blockchain audit trail for GL postings per IR-TAX-001
- **KIV-006**: Predictive tax amount validation per IR-TAX-002 and IR-TAX-003
- **KIV-007**: Visual reconciliation dashboard per FR-TAX-008
- **KIV-008**: Batch GL posting optimization per IR-TAX-001
- **KIV-009**: Integration with external tax compliance systems
- **KIV-010**: Automatic reversal document matching per BR-TAX-002

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB19-TAXATION.md](../prd/prd-01/PRD01-SUB19-TAXATION.md)
- **Related Plans**:
  - PRD01-SUB19-PLAN01 (Tax Master Data Foundation) - Provides tax configuration
  - PRD01-SUB19-PLAN02 (Tax Calculation Engine) - Provides CalculateTaxAction per FR-TAX-003
  - PRD01-SUB19-PLAN03 (Tax Period & Filing Management) - Provides periods and reports per FR-TAX-007 and FR-TAX-005
- **Integration Documentation**:
  - SUB08 (General Ledger) - GL integration per IR-TAX-001
  - SUB11 (Accounts Payable) - AP integration per IR-TAX-002
  - SUB12 (Accounts Receivable) - AR integration per IR-TAX-002
  - SUB16 (Purchasing) - Purchasing integration per IR-TAX-003
  - SUB17 (Sales) - Sales integration per IR-TAX-003
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
