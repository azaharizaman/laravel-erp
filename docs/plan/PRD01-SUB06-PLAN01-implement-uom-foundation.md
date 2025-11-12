---
plan: Implement UOM Foundation (Database & Core Models)
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, infrastructure, uom, measurement, core-infrastructure, inventory]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan establishes the foundational database schema, core models, enums, and data seeding for the Unit of Measure (UOM) Management System. This plan focuses on creating the essential data structures that support precise measurement handling, conversion factors with 6+ decimal precision using `brick/math`, and tenant-scoped custom units. The UOM system is critical infrastructure for all quantity-based operations in the ERP (inventory, purchasing, sales, manufacturing).

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-UOM-001**: Define **System UOMs** (meter, kilogram, liter, piece) seeded at installation
- **FR-UOM-002**: Support **Tenant Custom UOMs** for industry-specific needs
- **FR-UOM-003**: Store **Conversion Factors** with at least **6 decimal precision** for accurate unit conversions
- **FR-UOM-004**: Support **UOM Categories** (length, mass, volume, area, count, time)

**Business Rules:**
- **BR-UOM-001**: Each UOM category MUST have one **designated base unit**
- **BR-UOM-002**: Conversion factors MUST be **stored relative to base unit**
- **BR-UOM-004**: **System UOMs cannot be deleted**, only deactivated
- **BR-UOM-005**: **Tenant custom UOMs can be deleted** if not in use

**Data Requirements:**
- **DR-UOM-001**: UOM MUST store: **code, name, category, base_unit, conversion_factor, precision, is_active**
- **DR-UOM-002**: Conversion factors MUST use **DECIMAL(20,10)** or equivalent for precision
- **DR-UOM-003**: System UOMs MUST be **seeded on installation**

**Security Requirements:**
- **SR-UOM-001**: Enforce **tenant isolation** on custom UOMs

**Architecture Requirements:**
- **ARCH-UOM-001**: Use **azaharizaman/laravel-uom-management** package as foundation
- **ARCH-UOM-002**: Implement **precision-safe decimal math** using brick/math package

**Constraints:**
- **CON-001**: DECIMAL(20,10) for conversion_factor column
- **CON-002**: Tenant-scoped custom UOMs; NULL tenant_id for system UOMs
- **CON-003**: Code must be unique within tenant scope (unique index on tenant_id, code)

**Guidelines:**
- **GUD-001**: Follow PSR-12 coding standards with Laravel Pint
- **GUD-002**: Use strict types declaration in all PHP files
- **GUD-003**: Implement repository pattern for data access
- **GUD-004**: Use Laravel Actions for business logic

**Patterns:**
- **PAT-001**: Repository pattern (UomRepositoryContract → DatabaseUomRepository)
- **PAT-002**: Factory pattern for model creation in tests
- **PAT-003**: Enum pattern for UOM categories (PHP 8.2 backed enums)

## 2. Implementation Steps

### GOAL-001: Create Database Schema with High-Precision Conversion Factors

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-001, FR-UOM-002, FR-UOM-003, FR-UOM-004, DR-UOM-001, DR-UOM-002, SR-UOM-001 | Establish database table with tenant isolation, high-precision decimal storage, and proper indexing for performance | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration file `database/migrations/YYYY_MM_DD_HHMMSS_create_uoms_table.php` using anonymous class format. Define `uoms` table with: `id` (BIGSERIAL/BIGINT UNSIGNED), `tenant_id` (UUID/VARCHAR(36) NULL for system UOMs), `code` (VARCHAR(20) NOT NULL), `name` (VARCHAR(100) NOT NULL), `category` (VARCHAR(50) NOT NULL), `base_unit` (VARCHAR(20) NOT NULL), `conversion_factor` (DECIMAL(20,10) NOT NULL DEFAULT 1.0), `precision` (INTEGER NOT NULL DEFAULT 2), `is_active` (BOOLEAN NOT NULL DEFAULT TRUE), `is_system` (BOOLEAN NOT NULL DEFAULT FALSE), `metadata` (JSON NULL for extensibility), `created_at`, `updated_at`, `deleted_at` (for soft deletes) | | |
| TASK-002 | Add unique constraint on `(tenant_id, code)` to enforce code uniqueness within tenant scope. System UOMs have tenant_id=NULL and codes must be globally unique | | |
| TASK-003 | Create composite index on `(tenant_id, category)` for efficient category filtering queries | | |
| TASK-004 | Create index on `(tenant_id, is_active)` for active UOM lookup performance | | |
| TASK-005 | Create index on `base_unit` column for conversion factor queries | | |
| TASK-006 | Add foreign key constraint on `tenant_id` referencing `tenants(id)` with ON DELETE CASCADE to maintain referential integrity | | |

### GOAL-002: Create UOM Model with Precision-Safe Conversion Logic

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-001, FR-UOM-002, BR-UOM-001, BR-UOM-002, ARCH-UOM-002 | Implement Eloquent model with brick/math integration for precision arithmetic, tenant scoping, and conversion factor handling | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `app/Domains/UnitOfMeasure/Models/Uom.php` Eloquent model with namespace `App\Domains\UnitOfMeasure\Models`. Add `declare(strict_types=1);`. Use `SoftDeletes` trait for soft delete support | | |
| TASK-008 | Define `$fillable = ['tenant_id', 'code', 'name', 'category', 'base_unit', 'conversion_factor', 'precision', 'is_active', 'is_system', 'metadata']` | | |
| TASK-009 | Add `$casts = ['tenant_id' => 'string', 'conversion_factor' => 'decimal:10', 'precision' => 'integer', 'is_active' => 'boolean', 'is_system' => 'boolean', 'metadata' => 'array']` | | |
| TASK-010 | Implement `BelongsToTenant` trait from `App\Domains\Core\Traits\BelongsToTenant` for automatic tenant_id injection and scoping | | |
| TASK-011 | Add `HasFactory` trait and create corresponding factory. Implement `LogsActivity` trait from Spatie for audit logging with `log_attributes` = ['code', 'name', 'conversion_factor', 'is_active'] | | |
| TASK-012 | Create `scopeCategory(Builder $query, string $category): Builder` method to filter UOMs by category (e.g., `Uom::category('mass')->get()`) | | |
| TASK-013 | Create `scopeSystem(Builder $query): Builder` method to filter system UOMs (where `is_system = true`) | | |
| TASK-014 | Create `scopeActive(Builder $query): Builder` method to filter active UOMs (where `is_active = true`) | | |
| TASK-015 | Implement `isBaseUnit(): bool` method that returns `$this->code === $this->base_unit` to check if this UOM is the base unit for its category | | |
| TASK-016 | Add `tenant()` relationship: `belongsTo(Tenant::class, 'tenant_id')->withDefault()` to handle NULL tenant_id for system UOMs | | |

### GOAL-003: Create UOM Category Enum and Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-004, BR-UOM-001 | Define PHP 8.2 backed enum for UOM categories with validation and helper methods | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create `app/Domains/UnitOfMeasure/Enums/UomCategory.php` as PHP 8.2 **backed string enum** with namespace `App\Domains\UnitOfMeasure\Enums`. Add `declare(strict_types=1);` | | |
| TASK-018 | Define enum cases: `case LENGTH = 'length'; case MASS = 'mass'; case VOLUME = 'volume'; case AREA = 'area'; case COUNT = 'count'; case TIME = 'time';` | | |
| TASK-019 | Implement `label(): string` method using match expression to return human-readable labels (e.g., LENGTH → "Length", MASS → "Mass/Weight") | | |
| TASK-020 | Implement `baseUnit(): string` method to return the standard base unit code for each category (LENGTH → 'm', MASS → 'kg', VOLUME → 'L', AREA → 'm²', COUNT → 'pc', TIME → 's') | | |
| TASK-021 | Implement static method `values(): array` that returns all enum values as strings (e.g., ['length', 'mass', 'volume', 'area', 'count', 'time']) for validation rules | | |
| TASK-022 | Update `Uom` model to cast `category` to `UomCategory::class` in $casts array for automatic enum hydration | | |

### GOAL-004: Seed Standard System UOMs with Conversion Factors

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-001, DR-UOM-003, BR-UOM-002 | Create comprehensive seeder for industry-standard UOMs with accurate conversion factors relative to base units | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-023 | Create `database/seeders/UomSeeder.php` with namespace `Database\Seeders`. Add `declare(strict_types=1);`. Import `Uom` model and `UomCategory` enum | | |
| TASK-024 | Seed **Length UOMs** with base unit 'm' (meter): millimeter (mm, 0.001), centimeter (cm, 0.01), meter (m, 1.0), kilometer (km, 1000.0), inch (in, 0.0254), foot (ft, 0.3048), yard (yd, 0.9144), mile (mi, 1609.344). Set `is_system=true`, `tenant_id=NULL`, `precision` based on typical use (mm: 1, m: 3, km: 2) | | |
| TASK-025 | Seed **Mass UOMs** with base unit 'kg' (kilogram): milligram (mg, 0.000001), gram (g, 0.001), kilogram (kg, 1.0), metric ton (t, 1000.0), ounce (oz, 0.0283495), pound (lb, 0.453592), imperial ton (ton, 1016.047). Set `precision` (mg: 4, g: 3, kg: 3, lb: 2) | | |
| TASK-026 | Seed **Volume UOMs** with base unit 'L' (liter): milliliter (mL, 0.001), liter (L, 1.0), cubic meter (m³, 1000.0), fluid ounce (fl oz, 0.0295735), cup (cup, 0.236588), pint (pt, 0.473176), quart (qt, 0.946353), gallon (gal, 3.78541). Set `precision` (mL: 2, L: 3, gal: 2) | | |
| TASK-027 | Seed **Area UOMs** with base unit 'm²' (square meter): square millimeter (mm², 0.000001), square centimeter (cm², 0.0001), square meter (m², 1.0), hectare (ha, 10000.0), square kilometer (km², 1000000.0), square inch (sq in, 0.00064516), square foot (sq ft, 0.092903), acre (ac, 4046.86). Set `precision=2` for most area units | | |
| TASK-028 | Seed **Count UOMs** with base unit 'pc' (piece): piece (pc, 1.0), dozen (doz, 12.0), gross (gr, 144.0), hundred (100, 100.0), thousand (1000, 1000.0). Set `precision=0` for count units | | |
| TASK-029 | Seed **Time UOMs** with base unit 's' (second): second (s, 1.0), minute (min, 60.0), hour (hr, 3600.0), day (day, 86400.0), week (wk, 604800.0). Set `precision=2` for time calculations | | |
| TASK-030 | Call `UomSeeder` from `DatabaseSeeder::run()` method to ensure execution during `php artisan db:seed` | | |

### GOAL-005: Create UOM Factory and Repository Pattern

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PAT-001, GUD-003 | Implement repository pattern with contract interface and factory for testing support | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-031 | Create `app/Domains/UnitOfMeasure/Contracts/UomRepositoryContract.php` interface with namespace. Add `declare(strict_types=1);`. Define methods: `findByCode(string $code, ?string $tenantId = null): ?Uom`, `findByCategory(string $category, ?string $tenantId = null): Collection`, `findActive(?string $tenantId = null): Collection`, `create(array $data): Uom`, `update(Uom $uom, array $data): Uom`, `delete(Uom $uom): bool`, `isInUse(Uom $uom): bool` | | |
| TASK-032 | Create `app/Domains/UnitOfMeasure/Repositories/DatabaseUomRepository.php` implementing `UomRepositoryContract`. Inject tenant context via constructor for automatic scoping | | |
| TASK-033 | Implement `findByCode()` with tenant scope: `Uom::where('code', $code)->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q->whereNull('tenant_id'))->first()` to handle both system and tenant UOMs | | |
| TASK-034 | Implement `findByCategory()` using `Uom::category($category)->where('tenant_id', $tenantId)->orWhereNull('tenant_id')->active()->get()` to return both system and tenant UOMs | | |
| TASK-035 | Implement `isInUse()` method that checks if UOM is referenced in inventory_items, purchase_order_items, sales_order_items, or other related tables. Return true if any references exist (prevents deletion) | | |
| TASK-036 | Create `database/factories/UnitOfMeasure/UomFactory.php` extending `Factory`. Define default state with faker data: code (unique 2-5 char), name, category (random from UomCategory), base_unit, conversion_factor (random 0.001-1000 with 10 decimals), precision (1-4), is_active (true), is_system (false), tenant_id (null for testing, or inject via state) | | |
| TASK-037 | Add factory states: `system()` sets is_system=true and tenant_id=NULL; `custom()` sets is_system=false and requires tenant_id; `inactive()` sets is_active=false; `forCategory(UomCategory $category)` sets category and base_unit from enum | | |
| TASK-038 | Bind `UomRepositoryContract` to `DatabaseUomRepository` in `app/Providers/AppServiceProvider.php` (or create `UomServiceProvider` if package structure requires) using `$this->app->bind(UomRepositoryContract::class, DatabaseUomRepository::class)` | | |

## 3. Alternatives

- **ALT-001**: Use simple float for conversion_factor instead of DECIMAL(20,10) - **Rejected** because floating-point arithmetic introduces rounding errors unacceptable for financial/quantity calculations (e.g., 1.0 / 3 * 3 ≠ 1.0 in float)
- **ALT-002**: Store conversion factors bidirectionally (kg→lb and lb→kg) - **Rejected** because it introduces data redundancy and potential inconsistency. Storing relative to base unit allows calculation of any conversion path
- **ALT-003**: Use package `moontoast/math` instead of `brick/math` - **Rejected** because `brick/math` is more actively maintained, has better Laravel integration, and is already used in `azaharizaman/laravel-uom-management`
- **ALT-004**: Implement UOM as JSON column in inventory_items table - **Rejected** because UOMs are shared across multiple modules and require referential integrity, validation, and centralized management

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `brick/math` ^0.12 (MANDATORY for precision decimal arithmetic)
- **DEP-002**: `laravel/framework` ^12.0 (core framework)
- **DEP-003**: `spatie/laravel-activitylog` ^4.0 (audit logging)
- **DEP-004**: `azaharizaman/laravel-uom-management` (if using published package as base, otherwise internal package)

**Internal Dependencies:**
- **DEP-005**: PRD01-SUB01 (Multi-Tenancy System) - MUST be implemented first for tenant_id and BelongsToTenant trait
- **DEP-006**: PRD01-SUB03 (Audit Logging System) - for LogsActivity trait integration

**Infrastructure:**
- **DEP-007**: PostgreSQL 14+ OR MySQL 8.0+ with support for DECIMAL(20,10) precision
- **DEP-008**: Redis (optional but recommended for caching frequently accessed system UOMs)

## 5. Files

**Migration:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_uoms_table.php` - Database schema with tenant isolation and high-precision decimals

**Models:**
- `app/Domains/UnitOfMeasure/Models/Uom.php` - Eloquent model with scopes, relationships, and precision handling

**Enums:**
- `app/Domains/UnitOfMeasure/Enums/UomCategory.php` - PHP 8.2 backed string enum for categories

**Contracts:**
- `app/Domains/UnitOfMeasure/Contracts/UomRepositoryContract.php` - Repository interface

**Repositories:**
- `app/Domains/UnitOfMeasure/Repositories/DatabaseUomRepository.php` - Repository implementation

**Seeders:**
- `database/seeders/UomSeeder.php` - System UOM seeder with 40+ standard units
- `database/seeders/DatabaseSeeder.php` (modified) - Call UomSeeder

**Factories:**
- `database/factories/UnitOfMeasure/UomFactory.php` - Factory for testing

**Service Providers (if creating package):**
- `app/Domains/UnitOfMeasure/Providers/UomServiceProvider.php` - Service provider for bindings

## 6. Testing

**Unit Tests (15 tests):**
- **TEST-001**: `test_uom_model_has_correct_fillable_attributes` - Verify $fillable array
- **TEST-002**: `test_uom_model_casts_attributes_correctly` - Verify conversion_factor cast to decimal, category to UomCategory enum
- **TEST-003**: `test_uom_category_enum_returns_all_values` - Verify UomCategory::values() returns 6 categories
- **TEST-004**: `test_uom_category_enum_returns_base_unit_for_each_category` - Verify baseUnit() method for all cases
- **TEST-005**: `test_uom_scope_category_filters_correctly` - Test scopeCategory method
- **TEST-006**: `test_uom_scope_system_returns_only_system_uoms` - Test scopeSystem method
- **TEST-007**: `test_uom_scope_active_returns_only_active_uoms` - Test scopeActive method
- **TEST-008**: `test_is_base_unit_returns_true_for_base_units` - Test isBaseUnit() for kg, m, L
- **TEST-009**: `test_is_base_unit_returns_false_for_derived_units` - Test isBaseUnit() for lb, ft, gal
- **TEST-010**: `test_repository_find_by_code_returns_correct_uom` - Test findByCode with tenant scoping
- **TEST-011**: `test_repository_find_by_category_returns_all_category_uoms` - Test findByCategory returns system + tenant UOMs
- **TEST-012**: `test_repository_is_in_use_returns_true_when_uom_referenced` - Test isInUse() with mock references
- **TEST-013**: `test_factory_creates_valid_uom_with_correct_precision` - Test UomFactory default state
- **TEST-014**: `test_factory_system_state_creates_system_uom` - Test system() state with tenant_id=NULL
- **TEST-015**: `test_factory_custom_state_requires_tenant_id` - Test custom() state with tenant_id validation

**Feature Tests (8 tests):**
- **TEST-016**: `test_uom_seeder_creates_all_standard_length_units` - Verify 8 length UOMs seeded
- **TEST-017**: `test_uom_seeder_creates_all_standard_mass_units` - Verify 7 mass UOMs seeded
- **TEST-018**: `test_uom_seeder_creates_all_standard_volume_units` - Verify 8 volume UOMs seeded
- **TEST-019**: `test_uom_seeder_sets_correct_conversion_factors` - Verify kg conversion_factor = 1.0, lb = 0.453592
- **TEST-020**: `test_uom_seeder_marks_all_as_system_uoms` - Verify is_system=true and tenant_id=NULL
- **TEST-021**: `test_tenant_can_create_custom_uom_with_unique_code` - Test tenant-scoped UOM creation
- **TEST-022**: `test_system_uom_code_must_be_globally_unique` - Test unique constraint on (NULL, 'kg') fails duplicate
- **TEST-023**: `test_tenant_uom_code_must_be_unique_within_tenant` - Test tenant A can use 'sack', tenant B can use 'sack'

**Integration Tests (5 tests):**
- **TEST-024**: `test_belongs_to_tenant_trait_automatically_scopes_queries` - Verify BelongsToTenant scoping
- **TEST-025**: `test_logs_activity_trait_records_uom_changes` - Verify Spatie activity log on update
- **TEST-026**: `test_soft_delete_preserves_uom_for_historical_records` - Test soft delete with trashed() scope
- **TEST-027**: `test_repository_binding_resolves_correctly_from_container` - Test service container binding
- **TEST-028**: `test_database_transaction_rollback_on_invalid_conversion_factor` - Test ACID compliance

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Floating-point precision loss in conversion calculations - **Mitigation**: Use brick/math for all arithmetic operations, never use PHP native float math
- **RISK-002**: Seeded conversion factors may have slight inaccuracies from source data - **Mitigation**: Use NIST and ISO standard conversion factors, validate against official standards
- **RISK-003**: Tenant custom UOMs with invalid conversion factors could corrupt calculations - **Mitigation**: Validate conversion_factor > 0 and precision in [0,10] range in Form Requests
- **RISK-004**: Large number of custom UOMs (1000+) may slow down category queries - **Mitigation**: Implement caching for frequently accessed UOM lists, use composite indexes
- **RISK-005**: Deleting base unit UOM would break all derived unit conversions - **Mitigation**: Prevent deletion of base units in repository isInUse() check, add database-level protection

**Assumptions:**
- **ASSUMPTION-001**: System UOMs are sufficient for 95% of businesses; custom UOMs are edge cases
- **ASSUMPTION-002**: Conversion factors are static and do not change over time (unlike currency exchange rates)
- **ASSUMPTION-003**: All UOMs in a category can be converted to each other via the base unit
- **ASSUMPTION-004**: DECIMAL(20,10) provides sufficient precision for all business use cases (0.0000000001 accuracy)
- **ASSUMPTION-005**: Tenants will not create more than 10,000 custom UOMs (validated by SCR-UOM-001)

## 8. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB06-UOM.md](../prd/prd-01/PRD01-SUB06-UOM.md)
- **Related Sub-PRDs:**
  - PRD01-SUB01 (Multi-Tenancy System) - For tenant isolation
  - PRD01-SUB07 (Inventory Management) - Primary consumer of UOM system
- **External Documentation:**
  - brick/math Documentation: https://github.com/brick/math
  - NIST Unit Conversion Standards: https://www.nist.gov/pml/weights-and-measures/metric-si/unit-conversion
  - Laravel Eloquent Casts: https://laravel.com/docs/12.x/eloquent-mutators#custom-casts
  - Nested Set Model (if implementing UOM hierarchies): https://github.com/lazychaser/laravel-nestedset
