---
plan: Tax Calculation Engine (Automatic Calculation, Reverse Charge, Rule-Based Engine)
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, taxation, tax-calculation, reverse-charge, calculation-engine, performance]
---

# PRD01-SUB19-PLAN02: Implement Tax Calculation Engine

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan establishes the tax calculation engine with automatic tax determination, reverse charge mechanism for cross-border transactions, and rule-based configuration. This plan depends on PLAN01 tax master data foundation.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-TAX-003**: Automatically calculate applicable tax on transactions based on jurisdiction, customer/vendor type, item category
- **FR-TAX-006**: Support reverse charge mechanism for cross-border transactions

### Data Requirements
- **DR-TAX-001**: Store complete tax calculation details for each transaction line including tax type, rate, amount, justification

### Performance Requirements
- **PR-TAX-001**: Tax calculation must complete within 50 milliseconds per transaction line

### Event Requirements
- **EV-TAX-001**: Emit TaxCalculatedEvent when tax is calculated with transaction details

### Architecture Requirements
- **ARCH-TAX-001**: Use tax calculation engine with rule-based configuration for determination logic

### Scalability Requirements
- **SCR-TAX-001**: Support multiple tax jurisdictions simultaneously in a single transaction

### Constraints
- **CON-001**: Depends on PLAN01 (Tax Master Data) for rates, types, authorities, exemptions
- **CON-002**: Depends on SUB15 (Backoffice) for organization structure and entity types
- **CON-003**: Tax calculation must respect effective date ranges from DR-TAX-002
- **CON-004**: Calculation must be deterministic for audit purposes per SR-TAX-001
- **CON-005**: Must achieve < 50ms calculation time per PR-TAX-001

### Guidelines
- **GUD-001**: Cache tax determination rules in Redis per ARCH-TAX-002
- **GUD-002**: Use repository pattern for all data access
- **GUD-003**: Use Laravel Actions pattern for calculation logic
- **GUD-004**: Use brick/math for precise decimal calculations
- **GUD-005**: Emit TaxCalculatedEvent for all calculations per EV-TAX-001

### Patterns
- **PAT-001**: Strategy pattern for different calculation methods
- **PAT-002**: Chain of responsibility for rule evaluation per ARCH-TAX-001
- **PAT-003**: Repository pattern for calculation detail persistence
- **PAT-004**: Event-driven pattern for calculation notifications per EV-TAX-001
- **PAT-005**: Caching pattern for rule storage per ARCH-TAX-002

## 2. Implementation Steps

### GOAL-001: Tax Calculation Detail Model

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-TAX-001 | Store complete tax calculation details | | |
| FR-TAX-003 | Support automatic tax calculation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000005_create_tax_calculation_details_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), transaction_type (VARCHAR 50: sales_invoice/purchase_invoice/sales_order/purchase_order), transaction_id (BIGINT: polymorphic to transaction), line_number (INT: transaction line), tax_type_id (BIGINT FK tax_types), tax_rate_id (BIGINT FK tax_rates nullable), exemption_id (BIGINT FK tax_exemptions nullable), tax_authority_id (BIGINT FK tax_authorities nullable), base_amount (DECIMAL 15,2: amount before tax), tax_amount (DECIMAL 15,2: calculated tax), tax_percentage (DECIMAL 5,4: rate used), is_reverse_charge (BOOLEAN default false: FR-TAX-006), is_compound_tax (BOOLEAN default false), calculation_method (VARCHAR 50: percentage/fixed/progressive), justification (TEXT: why this tax applied per DR-TAX-001), calculated_at (TIMESTAMP), timestamps; indexes: tenant_id, (transaction_type + transaction_id), tax_type_id, calculated_at; supports DR-TAX-001 | | |
| TASK-002 | Create enum `TransactionType` with values: SALES_INVOICE, PURCHASE_INVOICE, SALES_ORDER, PURCHASE_ORDER, PAYMENT, RECEIPT, JOURNAL_ENTRY; methods: label(), requiresTaxCalculation(): bool, getModelClass(): string | | |
| TASK-003 | Create model `TaxCalculationDetail.php` with traits: BelongsToTenant; fillable: transaction_type, transaction_id, line_number, tax_type_id, tax_rate_id, exemption_id, tax_authority_id, base_amount, tax_amount, tax_percentage, is_reverse_charge, is_compound_tax, calculation_method, justification, calculated_at; casts: transaction_type → TransactionType enum, tax_type_id → int, tax_rate_id → int nullable, exemption_id → int nullable, tax_authority_id → int nullable, base_amount → float, tax_amount → float, tax_percentage → float, is_reverse_charge → boolean, is_compound_tax → boolean, calculated_at → datetime; relationships: tenant (belongsTo), taxType (belongsTo), taxRate (belongsTo nullable), exemption (belongsTo TaxExemption nullable), taxAuthority (belongsTo TaxAuthority nullable), transaction (morphTo); scopes: forTransaction(string $type, int $id), byTaxType(int $typeId), reverseCharge(), compoundTax(), calculatedInPeriod(Carbon $from, Carbon $to); methods: getEffectiveTaxRate(): float (considering exemptions), isReverseCharge(): bool per FR-TAX-006, getTaxJustification(): string per DR-TAX-001; no activity logging (immutable audit records) | | |
| TASK-004 | Create factory `TaxCalculationDetailFactory.php` with states: forSalesInvoice(int $id), forPurchaseInvoice(int $id), withExemption(TaxExemption $exemption), reverseCharge(), compoundTax(), standard(), reduced(), zero() | | |
| TASK-005 | Create contract `TaxCalculationDetailRepositoryContract.php` with methods: create(array $data): TaxCalculationDetail, findById(int $id): ?TaxCalculationDetail, getByTransaction(string $type, int $id): Collection, getByTaxType(int $taxTypeId, Carbon $from, Carbon $to): Collection, getReverseChargeTransactions(Carbon $from, Carbon $to): Collection per FR-TAX-006, getTaxSummary(Carbon $from, Carbon $to): array (aggregate by tax_type), getCalculationAudit(string $transactionType, int $transactionId): Collection per DR-TAX-001 | | |
| TASK-006 | Create repository `TaxCalculationDetailRepository.php` implementing TaxCalculationDetailRepositoryContract; implement all methods; apply tenant scoping; optimize date range queries; getTaxSummary aggregates base_amount, tax_amount by tax_type_id; getCalculationAudit returns details with justification per DR-TAX-001 | | |

### GOAL-002: Rule-Based Tax Determination Engine

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| ARCH-TAX-001 | Rule-based configuration | | |
| FR-TAX-003 | Automatic tax determination | | |
| SCR-TAX-001 | Multiple jurisdiction support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create migration `2025_01_01_000006_create_tax_determination_rules_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), rule_name (VARCHAR 255), rule_code (VARCHAR 50), priority (INT default 100: higher runs first), is_active (BOOLEAN default true), conditions (JSONB: matching criteria), tax_type_id (BIGINT FK tax_types), tax_authority_id (BIGINT FK tax_authorities nullable), apply_exemption (BOOLEAN default false), timestamps; indexes: tenant_id, is_active, priority DESC; unique: (tenant_id + rule_code); conditions JSON contains: jurisdiction_codes (array), customer_types (array), vendor_types (array), item_categories (array), transaction_types (array); supports ARCH-TAX-001 and SCR-TAX-001 | | |
| TASK-008 | Create model `TaxDeterminationRule.php` with traits: BelongsToTenant; fillable: rule_name, rule_code, priority, is_active, conditions, tax_type_id, tax_authority_id, apply_exemption; casts: priority → int, is_active → boolean, conditions → array, tax_type_id → int, tax_authority_id → int nullable, apply_exemption → boolean; relationships: tenant (belongsTo), taxType (belongsTo), taxAuthority (belongsTo nullable); scopes: active(), highPriority(), forJurisdiction(string $code), forCustomer(), forVendor(); methods: matchesConditions(array $context): bool (evaluate conditions against transaction context per ARCH-TAX-001), getMatchScore(array $context): int (how well rule matches), hasJurisdictionCondition(): bool per SCR-TAX-001 | | |
| TASK-009 | Create factory `TaxDeterminationRuleFactory.php` with states: active(), inactive(), highPriority(int $priority), forJurisdiction(array $codes), forCustomerType(string $type), forItemCategory(string $category), withExemption() | | |
| TASK-010 | Create contract `TaxDeterminationRuleRepositoryContract.php` with methods: findById(int $id): ?TaxDeterminationRule, getActiveRules(): Collection, getMatchingRules(array $context): Collection (get rules matching context per ARCH-TAX-001), getByPriority(): Collection, getRulesByJurisdiction(string $jurisdictionCode): Collection per SCR-TAX-001 | | |
| TASK-011 | Create repository `TaxDeterminationRuleRepository.php` implementing TaxDeterminationRuleRepositoryContract; cache active rules (15-minute TTL per ARCH-TAX-002); getMatchingRules filters by conditions, sorts by priority DESC per ARCH-TAX-001; supports SCR-TAX-001 multiple jurisdictions | | |
| TASK-012 | Create service `TaxDeterminationEngineService.php`; inject TaxDeterminationRuleRepositoryContract, TaxRateRepositoryContract, TaxExemptionRepositoryContract; method determineApplicableTax(array $context): array with context keys: transaction_type, transaction_date, jurisdiction_code (supports SCR-TAX-001), customer_id/vendor_id, item_id, item_category, base_amount; algorithm: (1) load matching rules from repository using getMatchingRules (ARCH-TAX-001), (2) evaluate each rule via matchesConditions, (3) sort by priority, (4) select highest priority match, (5) get effective tax rate for transaction_date from PLAN01, (6) check exemptions per FR-TAX-004, (7) return tax determination: {tax_type_id, tax_rate_id, exemption_id, authority_id, effective_rate, justification per DR-TAX-001}; cache determination result per ARCH-TAX-002 | | |
| TASK-013 | Create action `CreateTaxDeterminationRuleAction.php` using AsAction; validate unique rule_code per tenant; validate conditions JSON structure; validate tax_type exists; validate tax_authority exists if provided; validate priority >= 0; create TaxDeterminationRule; clear rule cache; return rule per ARCH-TAX-001 | | |
| TASK-014 | Create action `UpdateTaxDeterminationRuleAction.php` using AsAction; validate rule_code uniqueness if changed; validate conditions structure; update TaxDeterminationRule; clear rule cache; return rule | | |
| TASK-015 | Create action `DeleteTaxDeterminationRuleAction.php` using AsAction; soft delete by setting is_active = false; clear rule cache | | |

### GOAL-003: Tax Calculation Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-003 | Automatic tax calculation | | |
| PR-TAX-001 | Calculation < 50ms | | |
| EV-TAX-001 | TaxCalculatedEvent emission | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create action `CalculateTaxAction.php` using AsAction; inject TaxDeterminationEngineService, TaxRateRepositoryContract, TaxExemptionRepositoryContract, TaxCalculationDetailRepositoryContract; handle(array $params): TaxCalculationResult with params: transaction_type, transaction_id, line_number, transaction_date, jurisdiction_code (SCR-TAX-001), entity_type (customer/vendor), entity_id, item_id, item_category, base_amount; algorithm: (1) build context from params, (2) call TaxDeterminationEngineService->determineApplicableTax(context) per ARCH-TAX-001 and FR-TAX-003, (3) retrieve effective tax rate from tax_rate_id, (4) calculate tax_amount = base_amount * effective_rate using brick/math for precision, (5) check reverse charge applicability per FR-TAX-006 (entity in different jurisdiction), (6) check compound tax, (7) create TaxCalculationDetail with justification per DR-TAX-001, (8) emit TaxCalculatedEvent per EV-TAX-001, (9) return TaxCalculationResult; must complete < 50ms per PR-TAX-001 | | |
| TASK-017 | Create value object `TaxCalculationResult.php` with properties: success (bool), tax_amount (float), tax_percentage (float), tax_type_id (int), tax_rate_id (int nullable), exemption_id (int nullable), is_reverse_charge (bool per FR-TAX-006), calculation_detail_id (int), justification (string per DR-TAX-001), errors (array); methods: isSuccessful(): bool, getTaxAmount(): float, hasExemption(): bool, isReverseCharge(): bool per FR-TAX-006, toArray(): array | | |
| TASK-018 | Create event `TaxCalculatedEvent.php` per EV-TAX-001; properties: tenant_id (int), transaction_type (TransactionType), transaction_id (int), line_number (int), tax_calculation_detail_id (int), tax_type_id (int), tax_rate_id (int nullable), tax_amount (float), base_amount (float), tax_percentage (float), is_reverse_charge (bool per FR-TAX-006), jurisdiction_code (string per SCR-TAX-001), calculated_at (Carbon); implements ShouldBroadcast for real-time updates | | |
| TASK-019 | Create action `RecalculateTaxAction.php` using AsAction; inject TaxCalculationDetailRepositoryContract, CalculateTaxAction; handle(string $transactionType, int $transactionId): Collection; algorithm: (1) retrieve existing calculation details, (2) delete existing details, (3) re-run CalculateTaxAction for each line with current rates/rules, (4) return new calculation details; used when rates or rules change | | |
| TASK-020 | Create action `BulkCalculateTaxAction.php` using AsAction; inject CalculateTaxAction; handle(array $transactions): array; for each transaction: call CalculateTaxAction, collect results; dispatch jobs to queue if > 100 lines; return summary: {total_lines, calculated, failed, errors}; optimize for PR-TAX-001 < 50ms per line using parallel processing | | |

### GOAL-004: Reverse Charge Mechanism

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-006 | Reverse charge for cross-border | | |
| SCR-TAX-001 | Multiple jurisdiction support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create migration `2025_01_01_000007_create_reverse_charge_configurations_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), config_name (VARCHAR 255), from_jurisdiction (VARCHAR 50: seller jurisdiction), to_jurisdiction (VARCHAR 50: buyer jurisdiction), tax_type_id (BIGINT FK tax_types), applies_to (VARCHAR 50: goods/services/both), is_active (BOOLEAN default true), effective_from (DATE), effective_to (DATE nullable), timestamps; indexes: tenant_id, (from_jurisdiction + to_jurisdiction), is_active, (effective_from, effective_to); supports FR-TAX-006 and SCR-TAX-001 | | |
| TASK-022 | Create enum `ReverseChargeAppliesTo` with values: GOODS, SERVICES, BOTH; methods: label(), appliesToGoods(): bool, appliesToServices(): bool | | |
| TASK-023 | Create model `ReverseChargeConfiguration.php` with traits: BelongsToTenant; fillable: config_name, from_jurisdiction, to_jurisdiction, tax_type_id, applies_to, is_active, effective_from, effective_to; casts: tax_type_id → int, applies_to → ReverseChargeAppliesTo enum, is_active → boolean, effective_from → date, effective_to → date nullable; relationships: tenant (belongsTo), taxType (belongsTo); scopes: active(), effectiveOn(Carbon $date), forJurisdictions(string $from, string $to), forGoods(), forServices(); methods: isEffectiveOn(Carbon $date): bool, appliesToTransaction(string $itemType): bool per FR-TAX-006, matchesJurisdictions(string $from, string $to): bool per SCR-TAX-001 | | |
| TASK-024 | Create factory `ReverseChargeConfigurationFactory.php` with states: active(), inactive(), forJurisdictions(string $from, string $to), forGoods(), forServices(), current(), future() | | |
| TASK-025 | Create contract `ReverseChargeConfigurationRepositoryContract.php` with methods: findById(int $id): ?ReverseChargeConfiguration, getActiveConfigurations(): Collection, findApplicableConfiguration(string $fromJurisdiction, string $toJurisdiction, string $itemType, Carbon $date): ?ReverseChargeConfiguration per FR-TAX-006 and SCR-TAX-001, getByTaxType(int $taxTypeId): Collection | | |
| TASK-026 | Create repository `ReverseChargeConfigurationRepository.php` implementing ReverseChargeConfigurationRepositoryContract; cache configurations (15-minute TTL per ARCH-TAX-002); findApplicableConfiguration checks from_jurisdiction, to_jurisdiction, applies_to, effective_from/effective_to per FR-TAX-006; supports SCR-TAX-001 | | |
| TASK-027 | Create action `ApplyReverseChargeAction.php` using AsAction per FR-TAX-006; inject ReverseChargeConfigurationRepositoryContract; handle(TaxCalculationDetail $detail, string $fromJurisdiction, string $toJurisdiction, string $itemType): bool; algorithm: (1) find applicable reverse charge config via repository per SCR-TAX-001, (2) if config exists and matches per FR-TAX-006: set is_reverse_charge = true, adjust tax_amount = 0 (buyer responsible), update justification "Reverse charge applied: {config_name}" per DR-TAX-001, (3) emit TaxCalculatedEvent with is_reverse_charge = true per EV-TAX-001, (4) return true if applied | | |
| TASK-028 | Create action `CreateReverseChargeConfigurationAction.php` using AsAction; validate jurisdictions exist; validate tax_type exists; validate effective_to > effective_from if provided; validate no overlapping configs for same jurisdiction pair; create ReverseChargeConfiguration; clear config cache; return configuration per FR-TAX-006 | | |
| TASK-029 | Create action `UpdateReverseChargeConfigurationAction.php` using AsAction; validate date range; validate no overlaps excluding self; update ReverseChargeConfiguration; clear cache; return configuration | | |
| TASK-030 | Create action `DeleteReverseChargeConfigurationAction.php` using AsAction; check for calculation details using this config; if used: set is_active = false, effective_to = today; else: delete; clear cache | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-003, FR-TAX-006 | Complete API for calculation | | |
| PR-TAX-001 | Performance verification | | |
| EV-TAX-001 | Event testing | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-031 | Create policy `TaxCalculationPolicy.php` with methods: calculate requiring 'calculate-tax', recalculate requiring 'recalculate-tax', viewDetails requiring 'view-tax-calculations', deleteDetails requiring 'delete-tax-calculations'; enforce tenant scope | | |
| TASK-032 | Create policy `TaxDeterminationRulePolicy.php` with methods: viewAny requiring 'view-tax-rules', create requiring 'manage-tax-rules' per ARCH-TAX-001, update requiring 'manage-tax-rules', delete requiring 'manage-tax-rules'; enforce tenant scope | | |
| TASK-033 | Create policy `ReverseChargeConfigurationPolicy.php` with methods: viewAny requiring 'view-reverse-charge-configs', create requiring 'manage-reverse-charge-configs' per FR-TAX-006, update requiring 'manage-reverse-charge-configs', delete requiring 'manage-reverse-charge-configs'; enforce tenant scope | | |
| TASK-034 | Create API controller `TaxCalculationController.php` with routes: calculate (POST /api/v1/taxation/calculate) runs CalculateTaxAction per FR-TAX-003, recalculate (POST /calculate/recalculate) runs RecalculateTaxAction, bulkCalculate (POST /calculate/bulk) runs BulkCalculateTaxAction, details (GET /calculate/details?transaction_type=&transaction_id=) gets calculation details per DR-TAX-001, summary (GET /calculate/summary?from=&to=) gets tax summary; authorize via TaxCalculationPolicy; use TaxCalculationDetailResource | | |
| TASK-035 | Create API controller `TaxDeterminationRuleController.php` with routes: index (GET /api/v1/taxation/determination-rules), store (POST), show (GET /determination-rules/{id}), update (PATCH /determination-rules/{id}), destroy (DELETE /determination-rules/{id}), test (POST /determination-rules/test) per ARCH-TAX-001 (test rule matching); authorize via TaxDeterminationRulePolicy; use TaxDeterminationRuleResource | | |
| TASK-036 | Create API controller `ReverseChargeConfigurationController.php` with routes: index (GET /api/v1/taxation/reverse-charge-configs), store (POST), show (GET /reverse-charge-configs/{id}), update (PATCH /reverse-charge-configs/{id}), destroy (DELETE /reverse-charge-configs/{id}), check (GET /reverse-charge-configs/check?from=&to=&item_type=) per FR-TAX-006; authorize via ReverseChargeConfigurationPolicy; use ReverseChargeConfigurationResource | | |
| TASK-037 | Create form request `CalculateTaxRequest.php` with validation: transaction_type (required, in), transaction_id (required, integer), line_number (required, integer), transaction_date (required, date), jurisdiction_code (required, string per SCR-TAX-001), entity_type (required, in:customer,vendor), entity_id (required, integer), item_id (nullable, integer), item_category (nullable, string), base_amount (required, numeric, min:0); must provide all context for FR-TAX-003 | | |
| TASK-038 | Create form request `CreateTaxDeterminationRuleRequest.php` with validation: rule_name (required, string, max:255), rule_code (required, string, max:50, unique per tenant), priority (nullable, integer, min:0), conditions (required, array per ARCH-TAX-001), conditions.jurisdiction_codes (nullable, array per SCR-TAX-001), conditions.customer_types (nullable, array), conditions.item_categories (nullable, array), tax_type_id (required, exists:tax_types,id), tax_authority_id (nullable, exists:tax_authorities,id) | | |
| TASK-039 | Create form request `CreateReverseChargeConfigurationRequest.php` with validation: config_name (required, string, max:255), from_jurisdiction (required, string, max:50 per SCR-TAX-001), to_jurisdiction (required, string, max:50 per SCR-TAX-001), tax_type_id (required, exists:tax_types,id), applies_to (required, in per FR-TAX-006), effective_from (required, date), effective_to (nullable, date, after:effective_from) | | |
| TASK-040 | Create API resource `TaxCalculationDetailResource.php` with fields: id, transaction_type, transaction_id, line_number, taxType (nested TaxTypeResource minimal), taxRate (nested TaxRateResource minimal), exemption (nested TaxExemptionResource minimal per FR-TAX-004), taxAuthority (nested TaxAuthorityResource minimal), base_amount, tax_amount, tax_percentage, is_reverse_charge per FR-TAX-006, is_compound_tax, calculation_method, justification per DR-TAX-001, calculated_at | | |
| TASK-041 | Create API resource `TaxDeterminationRuleResource.php` with fields: id, rule_name, rule_code, priority, is_active, conditions per ARCH-TAX-001, taxType (nested TaxTypeResource minimal), taxAuthority (nested TaxAuthorityResource minimal), apply_exemption, created_at, updated_at | | |
| TASK-042 | Create API resource `ReverseChargeConfigurationResource.php` with fields: id, config_name, from_jurisdiction, to_jurisdiction per SCR-TAX-001, taxType (nested TaxTypeResource minimal), applies_to per FR-TAX-006, is_effective_now (computed via isEffectiveOn(now())), is_active, effective_from, effective_to, created_at, updated_at | | |
| TASK-043 | Write comprehensive unit tests for models: test TaxCalculationDetail getEffectiveTaxRate() with exemptions, test TaxCalculationDetail isReverseCharge() per FR-TAX-006, test TaxDeterminationRule matchesConditions() with various contexts per ARCH-TAX-001, test TaxDeterminationRule getMatchScore(), test ReverseChargeConfiguration appliesToTransaction() per FR-TAX-006, test ReverseChargeConfiguration matchesJurisdictions() per SCR-TAX-001 | | |
| TASK-044 | Write comprehensive unit tests for actions: test CalculateTaxAction with standard rate, test CalculateTaxAction with exemption per FR-TAX-004, test CalculateTaxAction with reverse charge per FR-TAX-006, test RecalculateTaxAction, test BulkCalculateTaxAction, test ApplyReverseChargeAction per FR-TAX-006 | | |
| TASK-045 | Write comprehensive unit tests for services: test TaxDeterminationEngineService->determineApplicableTax() with matching rules per ARCH-TAX-001, test rule priority handling, test jurisdiction matching per SCR-TAX-001, test exemption checking per FR-TAX-004, test cache effectiveness per ARCH-TAX-002 | | |
| TASK-046 | Write feature tests for tax calculation: test calculate tax via API per FR-TAX-003, test calculation with standard rate, test calculation with exemption per FR-TAX-004, test calculation with multiple jurisdictions per SCR-TAX-001, test recalculate tax, test bulk calculate | | |
| TASK-047 | Write feature tests for reverse charge: test create reverse charge config per FR-TAX-006, test apply reverse charge per FR-TAX-006, test reverse charge for cross-border transaction (different jurisdictions) per SCR-TAX-001, test reverse charge for goods vs services, test reverse charge in calculation per FR-TAX-006 | | |
| TASK-048 | Write feature tests for determination rules: test create determination rule per ARCH-TAX-001, test rule matching with jurisdiction per SCR-TAX-001, test rule priority evaluation, test rule with customer type filter, test rule with item category filter, test rule cache per ARCH-TAX-002 | | |
| TASK-049 | Write integration tests: test complete tax calculation workflow per FR-TAX-003, test calculation with determination rules per ARCH-TAX-001, test calculation with reverse charge per FR-TAX-006, test calculation with exemption per FR-TAX-004, test calculation detail persistence per DR-TAX-001, test TaxCalculatedEvent emission per EV-TAX-001 | | |
| TASK-050 | Write performance tests: test CalculateTaxAction completes < 50ms per PR-TAX-001, test TaxDeterminationEngineService < 30ms, test rule cache hit rate > 90% per ARCH-TAX-002, test bulk calculation of 1000 lines, test concurrent calculations | | |
| TASK-051 | Write security tests: test TaxCalculationPolicy authorization, test tenant isolation for calculation details per DR-TAX-001, test non-authorized user cannot calculate tax, test cannot view other tenant's calculations | | |
| TASK-052 | Write event tests: test TaxCalculatedEvent dispatched per EV-TAX-001, test event contains all required data (transaction details, jurisdiction per SCR-TAX-001, reverse charge flag per FR-TAX-006), test event listeners receive data, test event broadcasting | | |
| TASK-053 | Write acceptance tests: test automatic tax determination per FR-TAX-003, test reverse charge workflow per FR-TAX-006, test multi-jurisdiction transaction per SCR-TAX-001, test determination rule creation and usage per ARCH-TAX-001, test calculation performance per PR-TAX-001, test calculation detail audit trail per DR-TAX-001 | | |
| TASK-054 | Set up Pest configuration for tax calculation tests; configure database transactions; seed test data (rules, reverse charge configs); mock Redis cache | | |
| TASK-055 | Achieve minimum 80% code coverage for tax calculation module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-056 | Create API documentation: document calculate tax endpoint per FR-TAX-003, document calculation parameters (jurisdiction per SCR-TAX-001), document reverse charge behavior per FR-TAX-006, document determination rule endpoints per ARCH-TAX-001, document calculation detail structure per DR-TAX-001, document TaxCalculatedEvent schema per EV-TAX-001 | | |
| TASK-057 | Create user guide: how to calculate tax automatically per FR-TAX-003, configuring determination rules per ARCH-TAX-001, setting up reverse charge per FR-TAX-006, handling multiple jurisdictions per SCR-TAX-001, applying exemptions per FR-TAX-004, viewing calculation details per DR-TAX-001 | | |
| TASK-058 | Create technical documentation: tax calculation engine architecture per ARCH-TAX-001, determination rule evaluation algorithm, reverse charge implementation per FR-TAX-006, calculation performance optimization per PR-TAX-001, rule caching strategy per ARCH-TAX-002, event-driven design per EV-TAX-001 | | |
| TASK-059 | Create admin guide: managing determination rules per ARCH-TAX-001, configuring reverse charge per FR-TAX-006, monitoring calculation performance per PR-TAX-001, troubleshooting calculation errors, auditing calculation details per DR-TAX-001, optimizing rule priority | | |
| TASK-060 | Update package README with tax calculation features: automatic tax determination per FR-TAX-003, rule-based engine per ARCH-TAX-001, reverse charge support per FR-TAX-006, multi-jurisdiction handling per SCR-TAX-001, calculation performance per PR-TAX-001, complete audit trail per DR-TAX-001 | | |
| TASK-061 | Validate acceptance criteria: automatic calculation working per FR-TAX-003, reverse charge functional per FR-TAX-006, calculation details stored per DR-TAX-001, calculation < 50ms per PR-TAX-001, TaxCalculatedEvent emitted per EV-TAX-001, rule-based engine operational per ARCH-TAX-001, multiple jurisdictions supported per SCR-TAX-001 | | |
| TASK-062 | Conduct code review: verify FR-TAX-003 implementation, verify FR-TAX-006 reverse charge, verify DR-TAX-001 detail storage, verify PR-TAX-001 performance, verify EV-TAX-001 event emission, verify ARCH-TAX-001 rule engine, verify SCR-TAX-001 multi-jurisdiction | | |
| TASK-063 | Run full test suite for tax calculation module; verify all tests pass; verify calculation < 50ms per PR-TAX-001; verify TaxCalculatedEvent dispatched per EV-TAX-001; verify reverse charge working per FR-TAX-006 | | |
| TASK-064 | Deploy to staging; test automatic calculation per FR-TAX-003; test determination rules per ARCH-TAX-001; test reverse charge per FR-TAX-006; test multiple jurisdictions per SCR-TAX-001; verify performance per PR-TAX-001; verify events per EV-TAX-001; monitor calculation success rate | | |
| TASK-065 | Create seeder `TaxDeterminationRuleSeeder.php` with sample rules: standard VAT for domestic sales, reduced VAT for essential goods, reverse charge for cross-border services per FR-TAX-006, zero rate for exports per ARCH-TAX-001 | | |
| TASK-066 | Create seeder `ReverseChargeConfigurationSeeder.php` with sample configs: UK to EU services, US cross-state sales, Singapore to international services per FR-TAX-006 and SCR-TAX-001 | | |

## 3. Alternatives

- **ALT-001**: Hard-code tax determination logic - rejected; violates ARCH-TAX-001 rule-based requirement and reduces flexibility
- **ALT-002**: No reverse charge support - rejected; violates FR-TAX-006 cross-border requirement
- **ALT-003**: Store calculation details in transaction tables - rejected; violates DR-TAX-001 complete detail requirement and increases coupling
- **ALT-004**: No determination rule caching - rejected; violates ARCH-TAX-002 performance requirement
- **ALT-005**: Synchronous event dispatching - rejected; impacts PR-TAX-001 50ms performance target
- **ALT-006**: Single jurisdiction per transaction - rejected; violates SCR-TAX-001 multiple jurisdiction requirement

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: PLAN01 (Tax Master Data) - Tax rates, types, authorities, exemptions
- **DEP-002**: SUB15 (Backoffice) - Organization structure for entity types
- **DEP-003**: Laravel Events - For TaxCalculatedEvent per EV-TAX-001
- **DEP-004**: Laravel Cache (Redis) - Rule caching per ARCH-TAX-002
- **DEP-005**: brick/math - Precise decimal calculations

### Optional Dependencies
- **DEP-006**: Laravel Queue - For bulk calculation jobs
- **DEP-007**: Laravel Broadcasting - For real-time event updates

### Package Dependencies
- **DEP-008**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-009**: brick/math ^0.12 - Decimal precision
- **DEP-010**: predis/predis ^2.0 - Redis caching per ARCH-TAX-002

## 5. Files

### Models & Enums
- `packages/taxation/src/Models/TaxCalculationDetail.php` - Calculation details per DR-TAX-001
- `packages/taxation/src/Models/TaxDeterminationRule.php` - Determination rules per ARCH-TAX-001
- `packages/taxation/src/Models/ReverseChargeConfiguration.php` - Reverse charge configs per FR-TAX-006
- `packages/taxation/src/Enums/TransactionType.php` - Transaction types
- `packages/taxation/src/Enums/ReverseChargeAppliesTo.php` - Reverse charge scope per FR-TAX-006

### Value Objects
- `packages/taxation/src/ValueObjects/TaxCalculationResult.php` - Calculation result wrapper

### Services
- `packages/taxation/src/Services/TaxDeterminationEngineService.php` - Rule-based engine per ARCH-TAX-001

### Contracts & Repositories
- `packages/taxation/src/Contracts/TaxCalculationDetailRepositoryContract.php`
- `packages/taxation/src/Repositories/TaxCalculationDetailRepository.php` - Detail persistence per DR-TAX-001
- `packages/taxation/src/Contracts/TaxDeterminationRuleRepositoryContract.php`
- `packages/taxation/src/Repositories/TaxDeterminationRuleRepository.php` - Rule storage with caching per ARCH-TAX-002
- `packages/taxation/src/Contracts/ReverseChargeConfigurationRepositoryContract.php`
- `packages/taxation/src/Repositories/ReverseChargeConfigurationRepository.php` - Reverse charge config per FR-TAX-006

### Actions
- `packages/taxation/src/Actions/CalculateTaxAction.php` - Main calculation per FR-TAX-003
- `packages/taxation/src/Actions/RecalculateTaxAction.php` - Recalculation
- `packages/taxation/src/Actions/BulkCalculateTaxAction.php` - Bulk processing
- `packages/taxation/src/Actions/ApplyReverseChargeAction.php` - Reverse charge per FR-TAX-006
- `packages/taxation/src/Actions/CreateTaxDeterminationRuleAction.php` - Rule creation per ARCH-TAX-001
- `packages/taxation/src/Actions/UpdateTaxDeterminationRuleAction.php`
- `packages/taxation/src/Actions/DeleteTaxDeterminationRuleAction.php`
- `packages/taxation/src/Actions/CreateReverseChargeConfigurationAction.php` - Config creation per FR-TAX-006
- `packages/taxation/src/Actions/UpdateReverseChargeConfigurationAction.php`
- `packages/taxation/src/Actions/DeleteReverseChargeConfigurationAction.php`

### Events
- `packages/taxation/src/Events/TaxCalculatedEvent.php` - Tax calculated event per EV-TAX-001

### Controllers, Requests & Resources
- `packages/taxation/src/Http/Controllers/TaxCalculationController.php` - Calculation API per FR-TAX-003
- `packages/taxation/src/Http/Controllers/TaxDeterminationRuleController.php` - Rule API per ARCH-TAX-001
- `packages/taxation/src/Http/Controllers/ReverseChargeConfigurationController.php` - Reverse charge API per FR-TAX-006
- `packages/taxation/src/Http/Requests/CalculateTaxRequest.php` - Calculation validation
- `packages/taxation/src/Http/Requests/CreateTaxDeterminationRuleRequest.php` - Rule validation per ARCH-TAX-001
- `packages/taxation/src/Http/Requests/CreateReverseChargeConfigurationRequest.php` - Config validation per FR-TAX-006
- `packages/taxation/src/Http/Resources/TaxCalculationDetailResource.php` - Detail transformation per DR-TAX-001
- `packages/taxation/src/Http/Resources/TaxDeterminationRuleResource.php` - Rule transformation
- `packages/taxation/src/Http/Resources/ReverseChargeConfigurationResource.php` - Config transformation per FR-TAX-006

### Policies
- `packages/taxation/src/Policies/TaxCalculationPolicy.php` - Calculation authorization
- `packages/taxation/src/Policies/TaxDeterminationRulePolicy.php` - Rule authorization per ARCH-TAX-001
- `packages/taxation/src/Policies/ReverseChargeConfigurationPolicy.php` - Config authorization per FR-TAX-006

### Database
- `packages/taxation/database/migrations/2025_01_01_000005_create_tax_calculation_details_table.php` - Details table per DR-TAX-001
- `packages/taxation/database/migrations/2025_01_01_000006_create_tax_determination_rules_table.php` - Rules table per ARCH-TAX-001
- `packages/taxation/database/migrations/2025_01_01_000007_create_reverse_charge_configurations_table.php` - Reverse charge table per FR-TAX-006
- `packages/taxation/database/factories/*Factory.php` - All factories
- `packages/taxation/database/seeders/TaxDeterminationRuleSeeder.php` - Rule samples per ARCH-TAX-001
- `packages/taxation/database/seeders/ReverseChargeConfigurationSeeder.php` - Config samples per FR-TAX-006

### Tests
- `packages/taxation/tests/Unit/Models/*Test.php` - Model tests
- `packages/taxation/tests/Unit/Actions/*Test.php` - Action tests
- `packages/taxation/tests/Unit/Services/TaxDeterminationEngineServiceTest.php` - Engine tests per ARCH-TAX-001
- `packages/taxation/tests/Feature/TaxCalculationTest.php` - Calculation tests per FR-TAX-003
- `packages/taxation/tests/Feature/ReverseChargeTest.php` - Reverse charge tests per FR-TAX-006
- `packages/taxation/tests/Feature/TaxDeterminationRuleTest.php` - Rule tests per ARCH-TAX-001
- `packages/taxation/tests/Integration/TaxCalculationIntegrationTest.php` - Integration tests
- `packages/taxation/tests/Performance/CalculationPerformanceTest.php` - Performance tests per PR-TAX-001
- `packages/taxation/tests/Security/TaxCalculationAuthorizationTest.php` - Authorization tests
- `packages/taxation/tests/Event/TaxCalculatedEventTest.php` - Event tests per EV-TAX-001

## 6. Testing

### Unit Tests (15 tests)
- **TEST-001**: TaxCalculationDetail getEffectiveTaxRate() with exemptions per FR-TAX-004
- **TEST-002**: TaxCalculationDetail isReverseCharge() per FR-TAX-006
- **TEST-003**: TaxDeterminationRule matchesConditions() per ARCH-TAX-001
- **TEST-004**: TaxDeterminationRule getMatchScore() priority
- **TEST-005**: ReverseChargeConfiguration appliesToTransaction() per FR-TAX-006
- **TEST-006**: ReverseChargeConfiguration matchesJurisdictions() per SCR-TAX-001
- **TEST-007**: CalculateTaxAction with standard rate per FR-TAX-003
- **TEST-008**: CalculateTaxAction with exemption per FR-TAX-004
- **TEST-009**: CalculateTaxAction with reverse charge per FR-TAX-006
- **TEST-010**: ApplyReverseChargeAction per FR-TAX-006
- **TEST-011**: TaxDeterminationEngineService->determineApplicableTax() per ARCH-TAX-001
- **TEST-012**: Engine rule priority handling per ARCH-TAX-001
- **TEST-013**: Engine jurisdiction matching per SCR-TAX-001
- **TEST-014**: Engine exemption checking per FR-TAX-004
- **TEST-015**: Engine cache effectiveness per ARCH-TAX-002

### Feature Tests (15 tests)
- **TEST-016**: Calculate tax via API per FR-TAX-003
- **TEST-017**: Calculation with standard rate
- **TEST-018**: Calculation with exemption per FR-TAX-004
- **TEST-019**: Calculation with multiple jurisdictions per SCR-TAX-001
- **TEST-020**: Recalculate tax
- **TEST-021**: Bulk calculate
- **TEST-022**: Create reverse charge config per FR-TAX-006
- **TEST-023**: Apply reverse charge per FR-TAX-006
- **TEST-024**: Reverse charge cross-border per SCR-TAX-001 + FR-TAX-006
- **TEST-025**: Create determination rule per ARCH-TAX-001
- **TEST-026**: Rule matching with jurisdiction per SCR-TAX-001
- **TEST-027**: Rule priority evaluation per ARCH-TAX-001
- **TEST-028**: Rule with customer type filter per ARCH-TAX-001
- **TEST-029**: Rule with item category filter per ARCH-TAX-001
- **TEST-030**: Rule cache effectiveness per ARCH-TAX-002

### Integration Tests (6 tests)
- **TEST-031**: Complete calculation workflow per FR-TAX-003
- **TEST-032**: Calculation with determination rules per ARCH-TAX-001
- **TEST-033**: Calculation with reverse charge per FR-TAX-006
- **TEST-034**: Calculation with exemption per FR-TAX-004
- **TEST-035**: Calculation detail persistence per DR-TAX-001
- **TEST-036**: TaxCalculatedEvent emission per EV-TAX-001

### Performance Tests (5 tests)
- **TEST-037**: CalculateTaxAction < 50ms per PR-TAX-001
- **TEST-038**: TaxDeterminationEngineService < 30ms
- **TEST-039**: Rule cache hit rate > 90% per ARCH-TAX-002
- **TEST-040**: Bulk calculation 1000 lines
- **TEST-041**: Concurrent calculations

### Security Tests (4 tests)
- **TEST-042**: TaxCalculationPolicy authorization
- **TEST-043**: Tenant isolation per DR-TAX-001
- **TEST-044**: Non-authorized cannot calculate
- **TEST-045**: Cannot view other tenant's calculations

### Event Tests (4 tests)
- **TEST-046**: TaxCalculatedEvent dispatched per EV-TAX-001
- **TEST-047**: Event contains transaction details
- **TEST-048**: Event contains jurisdiction per SCR-TAX-001
- **TEST-049**: Event broadcasting

### Acceptance Tests (6 tests)
- **TEST-050**: Automatic tax determination per FR-TAX-003
- **TEST-051**: Reverse charge workflow per FR-TAX-006
- **TEST-052**: Multi-jurisdiction transaction per SCR-TAX-001
- **TEST-053**: Determination rule usage per ARCH-TAX-001
- **TEST-054**: Calculation performance per PR-TAX-001
- **TEST-055**: Calculation audit trail per DR-TAX-001

**Total Test Coverage:** 55 tests (15 unit + 15 feature + 6 integration + 5 performance + 4 security + 4 event + 6 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Calculation exceeds 50ms - Mitigation: Optimize determination engine, cache rules per ARCH-TAX-002, use indexed queries
- **RISK-002**: Rule matching ambiguity - Mitigation: Priority-based evaluation per ARCH-TAX-001, log rule matching for audit
- **RISK-003**: Reverse charge misconfiguration - Mitigation: Validate jurisdiction codes, test cross-border scenarios per FR-TAX-006
- **RISK-004**: Event queue backlog - Mitigation: Use Laravel horizon, monitor queue depth

### Assumptions
- **ASSUMPTION-001**: Tax rates configured in PLAN01 before calculation per FR-TAX-003
- **ASSUMPTION-002**: Determination rules cover all transaction scenarios per ARCH-TAX-001
- **ASSUMPTION-003**: Redis available for rule caching per ARCH-TAX-002
- **ASSUMPTION-004**: Transaction provides complete context (jurisdiction, entity, item) per FR-TAX-003
- **ASSUMPTION-005**: Reverse charge configurations updated regularly per FR-TAX-006
- **ASSUMPTION-006**: brick/math provides sufficient precision for all currencies
- **ASSUMPTION-007**: Maximum 100 concurrent calculations per tenant

## 8. KIV for Future Implementations

- **KIV-001**: Machine learning for automatic rule generation from historical patterns
- **KIV-002**: Real-time tax rate updates from government APIs
- **KIV-003**: Multi-currency tax calculation
- **KIV-004**: Tax estimation for quotes (before transaction finalization)
- **KIV-005**: Automatic tax classification using AI per ARCH-TAX-001
- **KIV-006**: Configurable calculation precision beyond brick/math
- **KIV-007**: Visual rule builder UI for determination rules per ARCH-TAX-001
- **KIV-008**: Calculation simulation mode (test without persisting)
- **KIV-009**: Batch recalculation for historical transactions
- **KIV-010**: Tax calculation analytics dashboard

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB19-TAXATION.md](../prd/prd-01/PRD01-SUB19-TAXATION.md)
- **Related Plans**:
  - PRD01-SUB19-PLAN01 (Tax Master Data Foundation) - Provides rates, types, authorities, exemptions
  - PRD01-SUB19-PLAN03 (Tax Period & Filing Management) - Uses calculation details for reporting
  - PRD01-SUB19-PLAN04 (Tax Integration & Reconciliation) - Uses calculations for GL posting
- **Integration Documentation**:
  - SUB15 (Backoffice) - Organization structure
  - SUB17 (Sales) - Sales invoice calculations
  - SUB16 (Purchasing) - Purchase invoice calculations
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
