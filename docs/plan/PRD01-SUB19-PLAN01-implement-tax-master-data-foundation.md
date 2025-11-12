---
plan: Tax Master Data Foundation (Authorities, Types, Rates, Exemptions)
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, taxation, tax-master-data, tax-rates, tax-authorities, tax-exemptions, financial-management]
---

# PRD01-SUB19-PLAN01: Implement Tax Master Data Foundation

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan establishes the foundational tax master data including tax authorities, tax types, tax rates with effective dating, and tax exemptions. This plan provides the configuration layer that all tax calculations depend on.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-TAX-001**: Maintain tax authority master data (tax offices, jurisdictions, rates)
- **FR-TAX-002**: Support multiple tax types (VAT, GST, sales tax, withholding tax, excise duty)
- **FR-TAX-004**: Support tax exemptions and special tax rates for specific customers or items

### Business Rules
- **BR-TAX-001**: Tax rates must have effective date ranges and cannot overlap

### Data Requirements
- **DR-TAX-002**: Maintain tax rate history with effective dates for audit

### Security Requirements
- **SR-TAX-001**: Implement audit trail for all tax configuration changes
- **SR-TAX-002**: Restrict tax rate modifications to authorized tax administrators

### Architecture Requirements
- **ARCH-TAX-002**: Cache frequently used tax rates in Redis for performance

### Constraints
- **CON-001**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Depends on SUB02 (Authentication) for user access control
- **CON-003**: Depends on SUB03 (Audit Logging) for change tracking
- **CON-004**: Tax rates must be immutable after period closing
- **CON-005**: Tax rate effective dates cannot overlap for same tax type per BR-TAX-001

### Guidelines
- **GUD-001**: Use repository pattern for all data access
- **GUD-002**: Use observer pattern for audit logging per SR-TAX-001
- **GUD-003**: Cache tax rates in Redis with 15-minute TTL per ARCH-TAX-002
- **GUD-004**: Follow Laravel Actions pattern for business logic
- **GUD-005**: Use strict type declarations in all PHP files

### Patterns
- **PAT-001**: Repository pattern for tax data access
- **PAT-002**: Observer pattern for audit trail (SR-TAX-001)
- **PAT-003**: Factory pattern for tax rate creation
- **PAT-004**: Policy pattern for authorization (SR-TAX-002)
- **PAT-005**: Caching pattern for performance (ARCH-TAX-002)

## 2. Implementation Steps

### GOAL-001: Tax Authority Master Data

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-001 | Tax authority master data management | | |
| SR-TAX-001 | Audit trail for configuration changes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_tax_authorities_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), authority_code (VARCHAR 50), authority_name (VARCHAR 255), country_code (VARCHAR 10: ISO 3166-1 alpha-2), jurisdiction_type (VARCHAR 50: federal/state/county/city), filing_frequency (VARCHAR 20: monthly/quarterly/annual nullable), contact_person (VARCHAR 255 nullable), email (VARCHAR 255 nullable), phone (VARCHAR 50 nullable), website (VARCHAR 255 nullable), is_active (BOOLEAN default true), timestamps, soft deletes; indexes: tenant_id, country_code, is_active; unique: (tenant_id + authority_code) | | |
| TASK-002 | Create enum `JurisdictionType` with values: FEDERAL (federal level), STATE (state/province level), COUNTY (county level), CITY (city/municipal level); methods: label(), getHierarchy(): int (1=federal, 2=state, 3=county, 4=city) | | |
| TASK-003 | Create enum `FilingFrequency` with values: MONTHLY, QUARTERLY, ANNUAL, BIANNUAL; methods: label(), getDaysInPeriod(): int, getNextDueDate(Carbon $from): Carbon | | |
| TASK-004 | Create model `TaxAuthority.php` with traits: BelongsToTenant, SoftDeletes, LogsActivity (SR-TAX-001); fillable: authority_code, authority_name, country_code, jurisdiction_type, filing_frequency, contact_person, email, phone, website, is_active; casts: jurisdiction_type → JurisdictionType enum, filing_frequency → FilingFrequency enum nullable, is_active → boolean; relationships: tenant (belongsTo), taxTypes (hasMany TaxType), taxRates (hasMany TaxRate through taxTypes), taxPeriods (hasMany TaxPeriod); scopes: active(), byCountry(string $code), byJurisdiction(JurisdictionType $type), federal(), state(), needsFiling(Carbon $date); methods: getNextFilingDeadline(): ?Carbon, canFileTaxReturn(): bool; Spatie activity log: log authority_code, authority_name, jurisdiction_type, filing_frequency changes | | |
| TASK-005 | Create factory `TaxAuthorityFactory.php` with states: active(), inactive(), federal(), state(), county(), city(), monthly(), quarterly(), annual(), forCountry(string $code), withContact() | | |
| TASK-006 | Create contract `TaxAuthorityRepositoryContract.php` with methods: findById(int $id): ?TaxAuthority, findByCode(string $code): ?TaxAuthority, getActivities(): Collection, getByCountry(string $countryCode): Collection, getByJurisdiction(JurisdictionType $type): Collection, getFederalAuthorities(): Collection, getAuthoritiesNeedingFiling(Carbon $date): Collection | | |
| TASK-007 | Create repository `TaxAuthorityRepository.php` implementing TaxAuthorityRepositoryContract; implement all contract methods; apply tenant scoping; optimize queries with eager loading; cache frequently accessed authorities (15-minute TTL per ARCH-TAX-002) | | |
| TASK-008 | Create action `CreateTaxAuthorityAction.php` using AsAction; inject TaxAuthorityRepositoryContract; validate unique authority_code per tenant; validate email format if provided; validate phone format if provided; validate website URL if provided; create TaxAuthority; log activity "Tax authority created: {authority_code}" (SR-TAX-001); clear authority cache; return TaxAuthority | | |
| TASK-009 | Create action `UpdateTaxAuthorityAction.php` using AsAction; inject TaxAuthorityRepositoryContract; validate authority_code uniqueness if changed; update TaxAuthority; log activity "Tax authority updated: {authority_code}" (SR-TAX-001); clear authority cache; return TaxAuthority | | |
| TASK-010 | Create action `DeleteTaxAuthorityAction.php` using AsAction; check for active tax types or periods; if active dependencies: throw CannotDeleteTaxAuthorityException; soft delete TaxAuthority; log activity "Tax authority deleted: {authority_code}" (SR-TAX-001); clear authority cache | | |

### GOAL-002: Tax Types Configuration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-002 | Support multiple tax types | | |
| SR-TAX-001, SR-TAX-002 | Audit trail and authorization | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Create migration `2025_01_01_000002_create_tax_types_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), tax_type_code (VARCHAR 50), tax_type_name (VARCHAR 255), category (VARCHAR 50: VAT/GST/sales_tax/withholding/excise), calculation_method (VARCHAR 50: percentage/fixed_amount/progressive), is_compound (BOOLEAN default false: compound tax), is_inclusive (BOOLEAN default false: price includes tax), gl_account_id (BIGINT FK gl_accounts nullable), is_active (BOOLEAN default true), timestamps, soft deletes; indexes: tenant_id, category, is_active, gl_account_id; unique: (tenant_id + tax_type_code) supporting FR-TAX-002 | | |
| TASK-012 | Create enum `TaxCategory` with values: VAT (Value Added Tax), GST (Goods and Services Tax), SALES_TAX (Sales Tax), WITHHOLDING (Withholding Tax), EXCISE (Excise Duty), CUSTOMS (Customs Duty); methods: label(), requiresInvoice(): bool, supportsReverseCharge(): bool, getTypicalRate(): float (typical rate for category) per FR-TAX-002 | | |
| TASK-013 | Create enum `TaxCalculationMethod` with values: PERCENTAGE (percentage of base), FIXED_AMOUNT (fixed amount per unit), PROGRESSIVE (progressive rates based on brackets); methods: label(), requiresRatePercentage(): bool, requiresFixedAmount(): bool | | |
| TASK-014 | Create model `TaxType.php` with traits: BelongsToTenant, SoftDeletes, LogsActivity (SR-TAX-001); fillable: tax_type_code, tax_type_name, category, calculation_method, is_compound, is_inclusive, gl_account_id, is_active; casts: category → TaxCategory enum, calculation_method → TaxCalculationMethod enum, is_compound → boolean, is_inclusive → boolean, is_active → boolean; relationships: tenant (belongsTo), taxAuthority (belongsTo nullable), glAccount (belongsTo GL Account nullable), taxRates (hasMany TaxRate), exemptions (hasMany TaxExemption), calculationDetails (hasMany TaxCalculationDetail); scopes: active(), byCategory(TaxCategory $category), compound(), inclusive(), percentage(), fixed(); methods: getCurrentRate(Carbon $date): ?TaxRate, hasGLAccount(): bool, isCompoundTax(): bool, isPriceInclusive(): bool; Spatie activity log: log all fillable changes (SR-TAX-001) | | |
| TASK-015 | Create factory `TaxTypeFactory.php` with states: active(), inactive(), vat(), gst(), salesTax(), withholding(), excise(), compound(), inclusive(), percentage(), fixedAmount(), withGLAccount(int $accountId) | | |
| TASK-016 | Create contract `TaxTypeRepositoryContract.php` with methods: findById(int $id): ?TaxType, findByCode(string $code): ?TaxType, getActiveTypes(): Collection, getByCategory(TaxCategory $category): Collection, getVATTypes(): Collection, getWithholdingTaxTypes(): Collection, getCompoundTaxTypes(): Collection | | |
| TASK-017 | Create repository `TaxTypeRepository.php` implementing TaxTypeRepositoryContract; implement all contract methods; apply tenant scoping; cache tax types (15-minute TTL per ARCH-TAX-002); optimize with eager loading of gl_account | | |
| TASK-018 | Create action `CreateTaxTypeAction.php` using AsAction; inject TaxTypeRepositoryContract; validate unique tax_type_code per tenant; validate gl_account exists if provided; validate calculation_method matches category; create TaxType; log activity "Tax type created: {tax_type_code}" (SR-TAX-001); clear tax type cache; return TaxType | | |
| TASK-019 | Create action `UpdateTaxTypeAction.php` using AsAction; authorize via TaxTypePolicy (SR-TAX-002); validate gl_account exists if changed; check for active tax rates before category change; update TaxType; log activity "Tax type updated: {tax_type_code}" (SR-TAX-001); clear tax type cache; return TaxType | | |
| TASK-020 | Create action `DeleteTaxTypeAction.php` using AsAction; authorize via TaxTypePolicy (SR-TAX-002); check for active tax rates; if active rates: throw CannotDeleteTaxTypeException; soft delete TaxType; log activity "Tax type deleted: {tax_type_code}" (SR-TAX-001); clear cache | | |

### GOAL-003: Tax Rates with Effective Dating

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-001, FR-TAX-004 | Tax rates with special rates | | |
| BR-TAX-001 | No overlapping effective dates | | |
| DR-TAX-002 | Tax rate history with effective dates | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create migration `2025_01_01_000003_create_tax_rates_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), tax_type_id (BIGINT FK tax_types), tax_authority_id (BIGINT FK tax_authorities nullable), rate_name (VARCHAR 255), rate_percentage (DECIMAL 5,4: e.g., 20.0000 for 20%), effective_from (DATE not null), effective_to (DATE nullable), applies_to (VARCHAR 50: all/goods/services/specific_items nullable), is_active (BOOLEAN default true), timestamps; indexes: tenant_id, tax_type_id, tax_authority_id, (effective_from, effective_to), is_active; check constraint: effective_to IS NULL OR effective_to > effective_from; check constraint: rate_percentage >= 0 AND rate_percentage <= 100; supports DR-TAX-002 and BR-TAX-001 | | |
| TASK-022 | Create enum `TaxAppliesTo` with values: ALL (all items), GOODS (physical goods only), SERVICES (services only), SPECIFIC_ITEMS (specific items/categories); methods: label(), requiresItemFilter(): bool | | |
| TASK-023 | Create model `TaxRate.php` with traits: BelongsToTenant, LogsActivity (SR-TAX-001); fillable: tax_type_id, tax_authority_id, rate_name, rate_percentage, effective_from, effective_to, applies_to, is_active; casts: rate_percentage → float, effective_from → date, effective_to → date nullable, applies_to → TaxAppliesTo enum nullable, is_active → boolean; relationships: tenant (belongsTo), taxType (belongsTo), taxAuthority (belongsTo nullable), calculationDetails (hasMany TaxCalculationDetail); scopes: active(), effectiveOn(Carbon $date), current(), byTaxType(int $typeId), byAuthority(int $authorityId), forGoods(), forServices(); methods: isEffectiveOn(Carbon $date): bool, isCurrentlyEffective(): bool, getEffectiveDateRange(): string, overlapsWithRate(TaxRate $other): bool (check BR-TAX-001), calculateTaxAmount(float $baseAmount): float; Spatie activity log: log rate_percentage, effective_from, effective_to changes (DR-TAX-002) | | |
| TASK-024 | Create observer `TaxRateObserver.php`; on creating: validate no overlapping rates for same tax_type_id per BR-TAX-001 using overlapsWithRate(), throw TaxRateOverlapException if overlaps; on updating: validate overlaps excluding self, validate effective_to >= effective_from; log all changes (SR-TAX-001) | | |
| TASK-025 | Create factory `TaxRateFactory.php` with states: active(), inactive(), current(), future(), expired(), standard (20%), reduced (5%), zero(), forTaxType(TaxType $type), forAuthority(TaxAuthority $authority), effectiveFrom(Carbon $date), effectiveTo(Carbon $date) | | |
| TASK-026 | Create contract `TaxRateRepositoryContract.php` with methods: findById(int $id): ?TaxRate, findEffectiveRate(int $taxTypeId, Carbon $date): ?TaxRate, getRateHistory(int $taxTypeId): Collection (DR-TAX-002), getActiveRates(): Collection, getRatesByAuthority(int $authorityId): Collection, getCurrentRates(): Collection, validateNoOverlap(TaxRate $rate): bool (BR-TAX-001) | | |
| TASK-027 | Create repository `TaxRateRepository.php` implementing TaxRateRepositoryContract; implement all methods; cache effective rates by tax_type_id and date (15-minute TTL per ARCH-TAX-002); optimize date range queries with indexes; validateNoOverlap checks overlapping effective_from/effective_to per BR-TAX-001 | | |
| TASK-028 | Create action `CreateTaxRateAction.php` using AsAction; inject TaxRateRepositoryContract; authorize via TaxRatePolicy (SR-TAX-002); validate tax_type exists; validate tax_authority exists if provided; validate effective_to > effective_from if provided; validate no overlapping rates per BR-TAX-001 using validateNoOverlap; create TaxRate; log activity "Tax rate created: {rate_name} for {tax_type}" (SR-TAX-001); clear rate cache; return TaxRate | | |
| TASK-029 | Create action `UpdateTaxRateAction.php` using AsAction; authorize via TaxRatePolicy (SR-TAX-002); validate effective date changes per BR-TAX-001; validate no overlaps excluding self; update TaxRate; log activity "Tax rate updated: {rate_name}" (SR-TAX-001); clear rate cache; return TaxRate | | |
| TASK-030 | Create action `DeleteTaxRateAction.php` using AsAction; authorize via TaxRatePolicy (SR-TAX-002); check for calculation details using this rate; if used in calculations: soft delete with effective_to = today; else: hard delete; log activity "Tax rate deleted: {rate_name}" (SR-TAX-001); clear cache | | |
| TASK-031 | Create action `GetEffectiveTaxRateAction.php` using AsAction; inject TaxRateRepositoryContract; accept tax_type_id and effective_date; retrieve from cache first (ARCH-TAX-002); if not cached: query DB for rate where effective_from <= date AND (effective_to IS NULL OR effective_to >= date); cache result; return TaxRate or null; used for tax calculations | | |

### GOAL-004: Tax Exemptions & Special Rates

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-004 | Tax exemptions and special rates | | |
| SR-TAX-001 | Audit trail for exemptions | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-032 | Create migration `2025_01_01_000004_create_tax_exemptions_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK tenants cascade), exemption_code (VARCHAR 50), exemption_name (VARCHAR 255), tax_type_id (BIGINT FK tax_types), exemption_type (VARCHAR 50: full/partial/reduced_rate), reduced_rate (DECIMAL 5,4 nullable: for partial exemptions), entity_type (VARCHAR 50: customer/vendor/item/category nullable), entity_id (VARCHAR 255 nullable: polymorphic ID), certificate_number (VARCHAR 100 nullable), valid_from (DATE nullable), valid_to (DATE nullable), is_active (BOOLEAN default true), timestamps; indexes: tenant_id, tax_type_id, (entity_type, entity_id), is_active, (valid_from, valid_to); unique: (tenant_id + exemption_code); supports FR-TAX-004 | | |
| TASK-033 | Create enum `ExemptionType` with values: FULL (100% exempt), PARTIAL (specific percentage exempt), REDUCED_RATE (reduced tax rate), ZERO_RATED (0% rate but claimable); methods: label(), requiresReducedRate(): bool, isFullExemption(): bool | | |
| TASK-034 | Create enum `ExemptionEntityType` with values: CUSTOMER, VENDOR, ITEM, ITEM_CATEGORY; methods: label(), getModelClass(): string (return model FQCN) | | |
| TASK-035 | Create model `TaxExemption.php` with traits: BelongsToTenant, LogsActivity (SR-TAX-001); fillable: exemption_code, exemption_name, tax_type_id, exemption_type, reduced_rate, entity_type, entity_id, certificate_number, valid_from, valid_to, is_active; casts: tax_type_id → int, exemption_type → ExemptionType enum, reduced_rate → float nullable, entity_type → ExemptionEntityType enum nullable, valid_from → date nullable, valid_to → date nullable, is_active → boolean; relationships: tenant (belongsTo), taxType (belongsTo), entity (morphTo); scopes: active(), validOn(Carbon $date), byTaxType(int $typeId), fullExemption(), partialExemption(), forCustomer(int $customerId), forItem(int $itemId); methods: isValidOn(Carbon $date): bool, isFullExemption(): bool, getEffectiveRate(float $standardRate): float (apply exemption to standard rate), hasValidCertificate(): bool; Spatie activity log: log all fillable changes (SR-TAX-001) per FR-TAX-004 | | |
| TASK-036 | Create factory `TaxExemptionFactory.php` with states: active(), inactive(), full(), partial(float $rate), reducedRate(float $rate), forCustomer(int $customerId), forItem(int $itemId), withCertificate(string $number), validFrom(Carbon $date), validTo(Carbon $date), current() | | |
| TASK-037 | Create contract `TaxExemptionRepositoryContract.php` with methods: findById(int $id): ?TaxExemption, findByCode(string $code): ?TaxExemption, getActiveExemptions(): Collection, getByTaxType(int $taxTypeId): Collection, getByEntity(string $entityType, string $entityId): Collection, getValidExemptions(Carbon $date): Collection, findApplicableExemption(int $taxTypeId, string $entityType, string $entityId, Carbon $date): ?TaxExemption | | |
| TASK-038 | Create repository `TaxExemptionRepository.php` implementing TaxExemptionRepositoryContract; implement all methods; apply tenant scoping; cache exemptions by entity (15-minute TTL per ARCH-TAX-002); findApplicableExemption checks tax_type_id, entity_type, entity_id, and valid_from/valid_to dates | | |
| TASK-039 | Create action `CreateTaxExemptionAction.php` using AsAction; inject TaxExemptionRepositoryContract; validate unique exemption_code per tenant; validate tax_type exists; validate reduced_rate if exemption_type = partial/reduced_rate; validate entity exists if entity_type/entity_id provided; validate certificate_number if provided; validate valid_to > valid_from if both provided; create TaxExemption; log activity "Tax exemption created: {exemption_code}" (SR-TAX-001); clear exemption cache; return TaxExemption per FR-TAX-004 | | |
| TASK-040 | Create action `UpdateTaxExemptionAction.php` using AsAction; authorize via TaxExemptionPolicy (SR-TAX-002); validate exemption_code uniqueness if changed; validate dates; update TaxExemption; log activity "Tax exemption updated: {exemption_code}" (SR-TAX-001); clear cache; return TaxExemption | | |
| TASK-041 | Create action `ValidateExemptionCertificateAction.php` using AsAction; accept exemption_id and optional certificate_verification_data; retrieve TaxExemption; validate certificate_number not empty; optionally verify with external tax authority API; update validation status; log activity "Exemption certificate validated: {exemption_code}" (SR-TAX-001); return validation result | | |
| TASK-042 | Create action `DeleteTaxExemptionAction.php` using AsAction; authorize via TaxExemptionPolicy (SR-TAX-002); check for calculation details using this exemption; if used: set is_active = false, valid_to = today; else: delete; log activity "Tax exemption deleted: {exemption_code}" (SR-TAX-001); clear cache | | |

### GOAL-005: API Controllers, Authorization, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-TAX-001, FR-TAX-002, FR-TAX-004 | Complete API for master data | | |
| SR-TAX-001, SR-TAX-002 | Audit and authorization | | |
| ARCH-TAX-002 | Rate caching verification | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-043 | Create policy `TaxAuthorityPolicy.php` with methods: viewAny requiring 'view-tax-authorities', view requiring 'view-tax-authorities', create requiring 'manage-tax-authorities' (SR-TAX-002), update requiring 'manage-tax-authorities' (SR-TAX-002), delete requiring 'manage-tax-authorities' (SR-TAX-002); enforce tenant scope | | |
| TASK-044 | Create policy `TaxTypePolicy.php` with methods: viewAny requiring 'view-tax-types', view requiring 'view-tax-types', create requiring 'manage-tax-configuration' (SR-TAX-002), update requiring 'manage-tax-configuration' (SR-TAX-002), delete requiring 'manage-tax-configuration' (SR-TAX-002); enforce tenant scope | | |
| TASK-045 | Create policy `TaxRatePolicy.php` with methods: viewAny requiring 'view-tax-rates', view requiring 'view-tax-rates', create requiring 'manage-tax-rates' (SR-TAX-002), update requiring 'manage-tax-rates' (SR-TAX-002), delete requiring 'manage-tax-rates' (SR-TAX-002), viewHistory requiring 'view-tax-rate-history' (DR-TAX-002); enforce tenant scope; prevent updates if rate used in closed periods | | |
| TASK-046 | Create policy `TaxExemptionPolicy.php` with methods: viewAny requiring 'view-tax-exemptions', view requiring 'view-tax-exemptions', create requiring 'manage-tax-exemptions' (SR-TAX-002), update requiring 'manage-tax-exemptions' (SR-TAX-002), delete requiring 'manage-tax-exemptions' (SR-TAX-002), validateCertificate requiring 'validate-tax-certificates'; enforce tenant scope | | |
| TASK-047 | Create API controller `TaxAuthorityController.php` with routes: index (GET /api/v1/taxation/authorities), store (POST), show (GET /authorities/{id}), update (PATCH /authorities/{id}), destroy (DELETE /authorities/{id}), filingSchedule (GET /authorities/{id}/filing-schedule); authorize all actions via TaxAuthorityPolicy; use TaxAuthorityResource for responses | | |
| TASK-048 | Create API controller `TaxTypeController.php` with routes: index (GET /api/v1/taxation/types), store (POST), show (GET /types/{id}), update (PATCH /types/{id}), destroy (DELETE /types/{id}), byCategory (GET /types/category/{category}); authorize via TaxTypePolicy; use TaxTypeResource | | |
| TASK-049 | Create API controller `TaxRateController.php` with routes: index (GET /api/v1/taxation/rates), store (POST), show (GET /rates/{id}), update (PATCH /rates/{id}), destroy (DELETE /rates/{id}), effective (GET /rates/effective?tax_type_id=&date=), history (GET /rates/{id}/history) per DR-TAX-002, validateOverlap (POST /rates/validate-overlap) per BR-TAX-001; authorize via TaxRatePolicy; use TaxRateResource | | |
| TASK-050 | Create API controller `TaxExemptionController.php` with routes: index (GET /api/v1/taxation/exemptions), store (POST), show (GET /exemptions/{id}), update (PATCH /exemptions/{id}), destroy (DELETE /exemptions/{id}), validate (POST /exemptions/{id}/validate); authorize via TaxExemptionPolicy; use TaxExemptionResource | | |
| TASK-051 | Create form request `CreateTaxAuthorityRequest.php` with validation: authority_code (required, string, max:50, unique per tenant), authority_name (required, string, max:255), country_code (required, string, size:2, in:valid_country_codes), jurisdiction_type (required, in), filing_frequency (nullable, in), email (nullable, email), phone (nullable, string, max:50), website (nullable, url) | | |
| TASK-052 | Create form request `CreateTaxTypeRequest.php` with validation: tax_type_code (required, string, max:50, unique per tenant), tax_type_name (required, string, max:255), category (required, in per FR-TAX-002), calculation_method (required, in), is_compound (nullable, boolean), is_inclusive (nullable, boolean), gl_account_id (nullable, exists:gl_accounts,id) | | |
| TASK-053 | Create form request `CreateTaxRateRequest.php` with validation: tax_type_id (required, exists:tax_types,id), tax_authority_id (nullable, exists:tax_authorities,id), rate_name (required, string, max:255), rate_percentage (required, numeric, min:0, max:100), effective_from (required, date), effective_to (nullable, date, after:effective_from), applies_to (nullable, in); custom validation: check no overlapping rates per BR-TAX-001 | | |
| TASK-054 | Create form request `CreateTaxExemptionRequest.php` with validation: exemption_code (required, string, max:50, unique per tenant), exemption_name (required, string, max:255), tax_type_id (required, exists:tax_types,id), exemption_type (required, in per FR-TAX-004), reduced_rate (required_if:exemption_type,partial,reduced_rate, numeric, min:0, max:100), entity_type (nullable, in), entity_id (required_with:entity_type, string), certificate_number (nullable, string, max:100), valid_from (nullable, date), valid_to (nullable, date, after:valid_from) | | |
| TASK-055 | Create API resource `TaxAuthorityResource.php` with fields: id, authority_code, authority_name, country_code, jurisdiction_type, filing_frequency, contact_person, email, phone, website, is_active, created_at, updated_at | | |
| TASK-056 | Create API resource `TaxTypeResource.php` with fields: id, tax_type_code, tax_type_name, category, calculation_method, is_compound, is_inclusive, gl_account (nested GLAccountResource minimal), is_active, current_rate (nested TaxRateResource minimal via getCurrentRate()), created_at, updated_at | | |
| TASK-057 | Create API resource `TaxRateResource.php` with fields: id, taxType (nested TaxTypeResource minimal), taxAuthority (nested TaxAuthorityResource minimal), rate_name, rate_percentage, effective_from, effective_to, effective_date_range (computed via getEffectiveDateRange()), applies_to, is_currently_effective (computed via isCurrentlyEffective()), is_active, created_at, updated_at | | |
| TASK-058 | Create API resource `TaxExemptionResource.php` with fields: id, exemption_code, exemption_name, taxType (nested TaxTypeResource minimal), exemption_type, reduced_rate, entity_type, entity_id, entity (polymorphic nested minimal), certificate_number, valid_from, valid_to, is_valid_now (computed via isValidOn(now())), is_active, created_at, updated_at | | |
| TASK-059 | Write comprehensive unit tests for models: test TaxAuthority getNextFilingDeadline(), test TaxType getCurrentRate(), test TaxRate isEffectiveOn() with various dates, test TaxRate overlapsWithRate() for BR-TAX-001, test TaxRate calculateTaxAmount(), test TaxExemption isValidOn(), test TaxExemption getEffectiveRate() | | |
| TASK-060 | Write comprehensive unit tests for enums: test JurisdictionType getHierarchy(), test FilingFrequency getDaysInPeriod() and getNextDueDate(), test TaxCategory requiresInvoice() and supportsReverseCharge(), test ExemptionType requiresReducedRate() | | |
| TASK-061 | Write comprehensive unit tests for actions: test CreateTaxAuthorityAction, test CreateTaxTypeAction validates gl_account, test CreateTaxRateAction validates overlaps per BR-TAX-001, test UpdateTaxRateAction prevents overlaps, test CreateTaxExemptionAction validates entity | | |
| TASK-062 | Write feature tests for tax authorities: test create tax authority via API, test update authority contact info, test delete authority with dependencies check, test list authorities with filters | | |
| TASK-063 | Write feature tests for tax types: test create VAT tax type (FR-TAX-002), test create withholding tax type (FR-TAX-002), test create compound tax type, test update tax type gl_account, test delete tax type with rates check | | |
| TASK-064 | Write feature tests for tax rates: test create tax rate, test effective date validation per BR-TAX-001, test overlapping rate rejection per BR-TAX-001, test update rate percentage, test get effective rate for date, test rate history endpoint per DR-TAX-002, test current rates listing | | |
| TASK-065 | Write feature tests for tax exemptions: test create full exemption (FR-TAX-004), test create partial exemption with reduced_rate (FR-TAX-004), test exemption valid_from/valid_to validation, test validate exemption certificate, test apply exemption to rate calculation | | |
| TASK-066 | Write integration tests: test tax authority with tax types and rates, test tax rate history tracking per DR-TAX-002, test exemption application on tax calculation, test activity logging for all entities per SR-TAX-001 | | |
| TASK-067 | Write performance tests: test rate caching effectiveness (ARCH-TAX-002), test get effective rate < 10ms with cache, test concurrent rate lookups, test rate history query performance with 1000+ rates per DR-TAX-002 | | |
| TASK-068 | Write security tests: test TaxRatePolicy authorization per SR-TAX-002, test non-admin cannot create rates, test non-admin cannot update rates, test audit logging for all changes per SR-TAX-001, test tenant isolation for all entities | | |
| TASK-069 | Write acceptance tests: test complete tax authority setup, test tax type with multiple rates over time per DR-TAX-002, test rate effective date enforcement per BR-TAX-001, test exemption application workflow per FR-TAX-004, test rate caching per ARCH-TAX-002 | | |
| TASK-070 | Set up Pest configuration for taxation tests; configure database transactions; seed test data (authorities, types, rates); mock Redis cache for rate caching tests | | |
| TASK-071 | Achieve minimum 80% code coverage for tax master data module; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-072 | Create API documentation: document tax authority endpoints, document tax type endpoints, document tax rate endpoints with effective date examples per DR-TAX-002, document exemption endpoints per FR-TAX-004, document rate caching behavior per ARCH-TAX-002, document BR-TAX-001 overlap validation | | |
| TASK-073 | Create user guide: how to set up tax authorities, configuring tax types for VAT/GST/sales tax per FR-TAX-002, managing tax rates with effective dates per DR-TAX-002, creating tax exemptions per FR-TAX-004, validating exemption certificates, understanding rate history | | |
| TASK-074 | Create technical documentation: tax master data architecture, effective date range implementation per BR-TAX-001, rate caching strategy per ARCH-TAX-002, audit logging implementation per SR-TAX-001, authorization model per SR-TAX-002, performance optimization techniques | | |
| TASK-075 | Create admin guide: setting up tax jurisdictions, configuring tax authorities, managing tax rates lifecycle per DR-TAX-002, handling overlapping rate errors per BR-TAX-001, managing exemption certificates per FR-TAX-004, monitoring rate cache performance, troubleshooting tax configuration | | |
| TASK-076 | Update package README with tax master data features: tax authority management, multiple tax type support per FR-TAX-002, effective-dated rates per DR-TAX-002, exemption management per FR-TAX-004, rate caching per ARCH-TAX-002 | | |
| TASK-077 | Validate acceptance criteria: tax authority CRUD functional, multiple tax types supported per FR-TAX-002, tax rates with effective dating working per DR-TAX-002, no overlapping rates enforced per BR-TAX-001, exemptions functional per FR-TAX-004, audit trail working per SR-TAX-001, authorization enforced per SR-TAX-002, rate caching operational per ARCH-TAX-002 | | |
| TASK-078 | Conduct code review: verify FR-TAX-001 implementation, verify FR-TAX-002 tax types, verify FR-TAX-004 exemptions, verify BR-TAX-001 overlap prevention, verify DR-TAX-002 rate history, verify SR-TAX-001 audit trail, verify SR-TAX-002 authorization, verify ARCH-TAX-002 caching | | |
| TASK-079 | Run full test suite for tax master data module; verify all tests pass; verify overlap validation works per BR-TAX-001; verify rate history accessible per DR-TAX-002; verify cache hit rate > 90% per ARCH-TAX-002 | | |
| TASK-080 | Deploy to staging; test tax authority creation; test tax type setup for VAT, GST, sales tax per FR-TAX-002; test rate effective date ranges per DR-TAX-002; test exemption application per FR-TAX-004; verify audit logs per SR-TAX-001; verify authorization per SR-TAX-002; monitor rate cache performance per ARCH-TAX-002 | | |
| TASK-081 | Create seeder `TaxAuthoritySeeder.php` with sample authorities: IRAS (Singapore GST), HMRC (UK VAT), IRS (US Sales Tax), ATO (Australia GST) per FR-TAX-001 | | |
| TASK-082 | Create seeder `TaxTypeSeeder.php` with sample types: VAT Standard, VAT Reduced, GST, Sales Tax, Withholding Tax, Excise Duty per FR-TAX-002 | | |
| TASK-083 | Create seeder `TaxRateSeeder.php` with sample rates: UK VAT 20% (standard), UK VAT 5% (reduced), Singapore GST 9%, US Sales Tax 8.5% per DR-TAX-002 | | |
| TASK-084 | Create seeder `TaxExemptionSeeder.php` with sample exemptions: VAT exempt entities, zero-rated goods, reduced rate services per FR-TAX-004 | | |

## 3. Alternatives

- **ALT-001**: Store tax rates as JSON in tax_types table - rejected; separate table provides better history tracking per DR-TAX-002 and query performance
- **ALT-002**: Allow overlapping tax rates - rejected; violates BR-TAX-001 and creates calculation ambiguity
- **ALT-003**: No effective dating on rates - rejected; violates DR-TAX-002 audit requirement and prevents historical accuracy
- **ALT-004**: No rate caching - rejected; violates ARCH-TAX-002 performance requirement
- **ALT-005**: Manual audit logging - rejected; use Spatie activity log for automatic tracking per SR-TAX-001
- **ALT-006**: Single tax rate per tax type - rejected; violates FR-TAX-004 special rates requirement and DR-TAX-002 history requirement

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant isolation for all tax data
- **DEP-002**: SUB02 (Authentication & Authorization) - User access control per SR-TAX-002
- **DEP-003**: SUB03 (Audit Logging) - Spatie activity log for SR-TAX-001
- **DEP-004**: SUB08 (General Ledger) - GL account relationships for tax types
- **DEP-005**: Laravel Cache (Redis) - Rate caching per ARCH-TAX-002

### Optional Dependencies
- **DEP-006**: External tax authority APIs - For exemption certificate validation

### Package Dependencies
- **DEP-007**: lorisleiva/laravel-actions ^2.0 - Action pattern
- **DEP-008**: spatie/laravel-activitylog ^4.0 - Audit logging per SR-TAX-001
- **DEP-009**: brick/math ^0.12 - Precise tax calculations

## 5. Files

### Models & Enums
- `packages/taxation/src/Models/TaxAuthority.php` - Tax authority model
- `packages/taxation/src/Models/TaxType.php` - Tax type model per FR-TAX-002
- `packages/taxation/src/Models/TaxRate.php` - Tax rate model with effective dating per DR-TAX-002
- `packages/taxation/src/Models/TaxExemption.php` - Tax exemption model per FR-TAX-004
- `packages/taxation/src/Enums/JurisdictionType.php` - Jurisdiction types
- `packages/taxation/src/Enums/FilingFrequency.php` - Filing frequencies
- `packages/taxation/src/Enums/TaxCategory.php` - Tax categories per FR-TAX-002
- `packages/taxation/src/Enums/TaxCalculationMethod.php` - Calculation methods
- `packages/taxation/src/Enums/TaxAppliesTo.php` - Tax application scope
- `packages/taxation/src/Enums/ExemptionType.php` - Exemption types per FR-TAX-004
- `packages/taxation/src/Enums/ExemptionEntityType.php` - Exemption entity types

### Observers
- `packages/taxation/src/Observers/TaxRateObserver.php` - Validates BR-TAX-001

### Contracts & Repositories
- `packages/taxation/src/Contracts/TaxAuthorityRepositoryContract.php` - Authority repository interface
- `packages/taxation/src/Repositories/TaxAuthorityRepository.php` - Authority repository
- `packages/taxation/src/Contracts/TaxTypeRepositoryContract.php` - Type repository interface
- `packages/taxation/src/Repositories/TaxTypeRepository.php` - Type repository
- `packages/taxation/src/Contracts/TaxRateRepositoryContract.php` - Rate repository interface
- `packages/taxation/src/Repositories/TaxRateRepository.php` - Rate repository with caching per ARCH-TAX-002
- `packages/taxation/src/Contracts/TaxExemptionRepositoryContract.php` - Exemption repository interface
- `packages/taxation/src/Repositories/TaxExemptionRepository.php` - Exemption repository

### Actions
- `packages/taxation/src/Actions/CreateTaxAuthorityAction.php` - Create authority
- `packages/taxation/src/Actions/UpdateTaxAuthorityAction.php` - Update authority
- `packages/taxation/src/Actions/DeleteTaxAuthorityAction.php` - Delete authority
- `packages/taxation/src/Actions/CreateTaxTypeAction.php` - Create tax type per FR-TAX-002
- `packages/taxation/src/Actions/UpdateTaxTypeAction.php` - Update tax type
- `packages/taxation/src/Actions/DeleteTaxTypeAction.php` - Delete tax type
- `packages/taxation/src/Actions/CreateTaxRateAction.php` - Create rate with overlap validation per BR-TAX-001
- `packages/taxation/src/Actions/UpdateTaxRateAction.php` - Update rate
- `packages/taxation/src/Actions/DeleteTaxRateAction.php` - Delete rate
- `packages/taxation/src/Actions/GetEffectiveTaxRateAction.php` - Get effective rate per DR-TAX-002
- `packages/taxation/src/Actions/CreateTaxExemptionAction.php` - Create exemption per FR-TAX-004
- `packages/taxation/src/Actions/UpdateTaxExemptionAction.php` - Update exemption
- `packages/taxation/src/Actions/ValidateExemptionCertificateAction.php` - Validate certificate
- `packages/taxation/src/Actions/DeleteTaxExemptionAction.php` - Delete exemption

### Controllers, Requests & Resources
- `packages/taxation/src/Http/Controllers/TaxAuthorityController.php` - Authority API
- `packages/taxation/src/Http/Controllers/TaxTypeController.php` - Type API per FR-TAX-002
- `packages/taxation/src/Http/Controllers/TaxRateController.php` - Rate API with history per DR-TAX-002
- `packages/taxation/src/Http/Controllers/TaxExemptionController.php` - Exemption API per FR-TAX-004
- `packages/taxation/src/Http/Requests/CreateTaxAuthorityRequest.php` - Authority validation
- `packages/taxation/src/Http/Requests/CreateTaxTypeRequest.php` - Type validation
- `packages/taxation/src/Http/Requests/CreateTaxRateRequest.php` - Rate validation with overlap check per BR-TAX-001
- `packages/taxation/src/Http/Requests/CreateTaxExemptionRequest.php` - Exemption validation
- `packages/taxation/src/Http/Resources/TaxAuthorityResource.php` - Authority transformation
- `packages/taxation/src/Http/Resources/TaxTypeResource.php` - Type transformation
- `packages/taxation/src/Http/Resources/TaxRateResource.php` - Rate transformation
- `packages/taxation/src/Http/Resources/TaxExemptionResource.php` - Exemption transformation

### Policies
- `packages/taxation/src/Policies/TaxAuthorityPolicy.php` - Authority authorization
- `packages/taxation/src/Policies/TaxTypePolicy.php` - Type authorization per SR-TAX-002
- `packages/taxation/src/Policies/TaxRatePolicy.php` - Rate authorization per SR-TAX-002
- `packages/taxation/src/Policies/TaxExemptionPolicy.php` - Exemption authorization per SR-TAX-002

### Database
- `packages/taxation/database/migrations/2025_01_01_000001_create_tax_authorities_table.php`
- `packages/taxation/database/migrations/2025_01_01_000002_create_tax_types_table.php`
- `packages/taxation/database/migrations/2025_01_01_000003_create_tax_rates_table.php`
- `packages/taxation/database/migrations/2025_01_01_000004_create_tax_exemptions_table.php`
- `packages/taxation/database/factories/*Factory.php` - All model factories
- `packages/taxation/database/seeders/TaxAuthoritySeeder.php`
- `packages/taxation/database/seeders/TaxTypeSeeder.php`
- `packages/taxation/database/seeders/TaxRateSeeder.php`
- `packages/taxation/database/seeders/TaxExemptionSeeder.php`

### Tests
- `packages/taxation/tests/Unit/Models/*Test.php` - Model unit tests
- `packages/taxation/tests/Unit/Enums/*Test.php` - Enum unit tests
- `packages/taxation/tests/Unit/Actions/*Test.php` - Action unit tests
- `packages/taxation/tests/Feature/TaxAuthorityTest.php` - Authority feature tests
- `packages/taxation/tests/Feature/TaxTypeTest.php` - Type feature tests per FR-TAX-002
- `packages/taxation/tests/Feature/TaxRateTest.php` - Rate feature tests with overlap validation per BR-TAX-001
- `packages/taxation/tests/Feature/TaxExemptionTest.php` - Exemption feature tests per FR-TAX-004
- `packages/taxation/tests/Integration/TaxMasterDataIntegrationTest.php` - Integration tests
- `packages/taxation/tests/Performance/RateCachingPerformanceTest.php` - Cache performance per ARCH-TAX-002
- `packages/taxation/tests/Security/TaxAuthorizationTest.php` - Authorization tests per SR-TAX-002

## 6. Testing

### Unit Tests (18 tests)
- **TEST-001**: TaxAuthority getNextFilingDeadline() calculation
- **TEST-002**: TaxType getCurrentRate() retrieval
- **TEST-003**: TaxType hasGLAccount() check
- **TEST-004**: TaxRate isEffectiveOn() with various dates
- **TEST-005**: TaxRate overlapsWithRate() for BR-TAX-001
- **TEST-006**: TaxRate calculateTaxAmount() accuracy
- **TEST-007**: TaxExemption isValidOn() date validation
- **TEST-008**: TaxExemption getEffectiveRate() calculation per FR-TAX-004
- **TEST-009**: JurisdictionType getHierarchy() values
- **TEST-010**: FilingFrequency getDaysInPeriod() calculation
- **TEST-011**: TaxCategory supportReverse Charge() per FR-TAX-002

### Feature Tests (18 tests)
- **TEST-012**: Create tax authority via API
- **TEST-013**: Update tax authority contact info
- **TEST-014**: Delete tax authority with dependencies check
- **TEST-015**: Create VAT tax type per FR-TAX-002
- **TEST-016**: Create withholding tax type per FR-TAX-002
- **TEST-017**: Create compound tax type
- **TEST-018**: Create tax rate with effective dates per DR-TAX-002
- **TEST-019**: Reject overlapping tax rate per BR-TAX-001
- **TEST-020**: Update tax rate percentage
- **TEST-021**: Get effective rate for specific date
- **TEST-022**: Get rate history per DR-TAX-002
- **TEST-023**: Create full tax exemption per FR-TAX-004
- **TEST-024**: Create partial exemption with reduced rate per FR-TAX-004
- **TEST-025**: Validate exemption certificate
- **TEST-026**: Apply exemption to rate calculation per FR-TAX-004

### Integration Tests (6 tests)
- **TEST-027**: Tax authority with multiple tax types and rates
- **TEST-028**: Tax rate history tracking per DR-TAX-002
- **TEST-029**: Exemption application in tax calculation per FR-TAX-004
- **TEST-030**: Activity logging for all entities per SR-TAX-001

### Performance Tests (4 tests)
- **TEST-031**: Rate caching effectiveness per ARCH-TAX-002
- **TEST-032**: Get effective rate < 10ms with cache per ARCH-TAX-002
- **TEST-033**: Concurrent rate lookups performance
- **TEST-034**: Rate history query with 1000+ rates per DR-TAX-002

### Security Tests (4 tests)
- **TEST-035**: TaxRatePolicy authorization per SR-TAX-002
- **TEST-036**: Non-admin cannot create/update rates per SR-TAX-002
- **TEST-037**: Audit logging for all changes per SR-TAX-001
- **TEST-038**: Tenant isolation for all entities

### Acceptance Tests (6 tests)
- **TEST-039**: Complete tax authority setup
- **TEST-040**: Tax type with multiple rates over time per DR-TAX-002
- **TEST-041**: Rate effective date enforcement per BR-TAX-001
- **TEST-042**: Exemption workflow per FR-TAX-004
- **TEST-043**: Rate caching per ARCH-TAX-002
- **TEST-044**: Authorization enforcement per SR-TAX-002

**Total Test Coverage:** 56 tests (18 unit + 18 feature + 6 integration + 4 performance + 4 security + 6 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Tax rate overlaps not detected - Mitigation: TaxRateObserver validates BR-TAX-001 on create/update
- **RISK-002**: Cache invalidation issues - Mitigation: clear cache on all rate/exemption modifications per ARCH-TAX-002
- **RISK-003**: Historical rate queries slow - Mitigation: index on effective_from/effective_to per DR-TAX-002
- **RISK-004**: Exemption certificate validation unavailable - Mitigation: manual validation workflow with approval

### Assumptions
- **ASSUMPTION-001**: Tax authorities configured before tax types per FR-TAX-001
- **ASSUMPTION-002**: GL accounts exist before linking to tax types
- **ASSUMPTION-003**: Tax rate changes require admin approval per SR-TAX-002
- **ASSUMPTION-004**: Rate history retained indefinitely per DR-TAX-002
- **ASSUMPTION-005**: Redis available for rate caching per ARCH-TAX-002
- **ASSUMPTION-006**: Exemption certificates validated externally
- **ASSUMPTION-007**: Maximum 100 concurrent users modifying tax configuration

## 8. KIV for Future Implementations

- **KIV-001**: Automatic tax authority API integration for rate updates
- **KIV-002**: Machine learning for tax classification
- **KIV-003**: Multi-currency tax rate support
- **KIV-004**: Tax rate forecasting based on historical changes per DR-TAX-002
- **KIV-005**: Exemption certificate scanning and OCR
- **KIV-006**: Automatic exemption expiry notifications
- **KIV-007**: Tax rate comparison across jurisdictions
- **KIV-008**: Visual timeline for rate history per DR-TAX-002
- **KIV-009**: Bulk import of tax authorities and rates
- **KIV-010**: Tax configuration templates by country

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB19-TAXATION.md](../prd/prd-01/PRD01-SUB19-TAXATION.md)
- **Related Plans**:
  - PRD01-SUB19-PLAN02 (Tax Calculation Engine) - Uses master data for calculations
  - PRD01-SUB19-PLAN03 (Tax Period & Filing Management) - Uses rates for period calculations
  - PRD01-SUB19-PLAN04 (Tax Integration & Reconciliation) - GL integration
- **Integration Documentation**:
  - SUB01 (Multi-Tenancy) - Tenant isolation
  - SUB02 (Authentication) - User authorization per SR-TAX-002
  - SUB03 (Audit Logging) - Activity tracking per SR-TAX-001
  - SUB08 (General Ledger) - GL account linking
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
