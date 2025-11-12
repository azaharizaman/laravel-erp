---
plan: Implement UOM Conversion Engine with Precision Arithmetic
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, business-logic, uom, conversion, precision-math, core-functionality]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan builds the UOM Conversion Engine that enables automatic quantity conversion between compatible units with precision arithmetic. Using `brick/math` for decimal-safe calculations, this plan implements the core business logic for converting quantities (e.g., 100 kg → 220.462 lb), validating unit compatibility, applying rounding rules, and ensuring accuracy within 0.0001% tolerance. This conversion engine is critical for multi-unit operations across inventory, sales, purchasing, and manufacturing modules.

## 1. Requirements & Constraints

**Functional Requirements:**
- **FR-UOM-005**: Provide **Automatic Conversion** logic to translate quantities between compatible units

**Business Rules:**
- **BR-UOM-001**: Each UOM category MUST have one **designated base unit**
- **BR-UOM-002**: Conversion factors MUST be **stored relative to base unit**
- **BR-UOM-003**: Only **compatible units within same category** can be converted

**Integration Requirements:**
- **IR-UOM-001**: Provide UOM conversion API for **all modules** (Inventory, Sales, Purchasing)

**Performance Requirements:**
- **PR-UOM-001**: Unit conversions MUST maintain **rounding accuracy within 0.0001% tolerance**
- **PR-UOM-002**: Conversion calculations MUST complete in **< 5ms**

**Architecture Requirements:**
- **ARCH-UOM-002**: Implement **precision-safe decimal math** using brick/math package

**Constraints:**
- **CON-001**: All arithmetic must use brick/math BigDecimal, never native PHP float
- **CON-002**: Conversion between incompatible categories (kg → m) must throw exception
- **CON-003**: Rounding must respect precision setting of target UOM

**Guidelines:**
- **GUD-001**: Use Laravel Actions for conversion operations (AsAction trait)
- **GUD-002**: Return result as string to preserve precision (brick/math standard)
- **GUD-003**: Cache conversion factors for frequently used unit pairs (Redis)

**Patterns:**
- **PAT-001**: Strategy pattern for different conversion algorithms (direct, via-base-unit)
- **PAT-002**: Action pattern for ConvertQuantityAction, ValidateCompatibilityAction
- **PAT-003**: Service pattern for UomConversionService with fluent API

## 2. Implementation Steps

### GOAL-001: Create Core Conversion Service with brick/math Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-005, BR-UOM-002, BR-UOM-003, ARCH-UOM-002 | Build conversion service that uses BigDecimal arithmetic for precision-safe quantity conversion between compatible UOMs | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `app/Domains/UnitOfMeasure/Services/UomConversionService.php` with namespace. Add `declare(strict_types=1);`. Inject `UomRepositoryContract` via constructor. Import `use Brick\Math\BigDecimal; use Brick\Math\RoundingMode;` | | |
| TASK-002 | Implement `convert(string|BigDecimal $quantity, Uom|string $fromUom, Uom|string $toUom, ?int $precision = null, int $roundingMode = RoundingMode::HALF_UP): string` method. Accept quantity as string or BigDecimal to avoid float precision loss. Return string representation of result | | |
| TASK-003 | In convert() method, first resolve $fromUom and $toUom to Uom models if strings provided (use repository findByCode()). Throw `UomNotFoundException` if either UOM not found | | |
| TASK-004 | Validate unit compatibility by checking `$fromUom->category === $toUom->category`. Throw `IncompatibleUomException` with message "Cannot convert {category1} to {category2}" if categories don't match | | |
| TASK-005 | Handle direct conversion case: if `$fromUom->code === $toUom->code`, return input quantity unchanged (no calculation needed for kg → kg) | | |
| TASK-006 | Implement two-step conversion via base unit: (1) Convert FROM unit to base unit by multiplying quantity * from_conversion_factor. (2) Convert base unit to TO unit by dividing by to_conversion_factor. Use BigDecimal for all operations: `$base = BigDecimal::of($quantity)->multipliedBy($fromUom->conversion_factor); $result = $base->dividedBy($toUom->conversion_factor, $scale, RoundingMode::HALF_UP);` where $scale = precision ?? $toUom->precision | | |
| TASK-007 | Implement `convertToBaseUnit(string|BigDecimal $quantity, Uom|string $uom): string` helper method that converts any quantity to its category's base unit (e.g., 100 lb → 45.3592 kg). Use `BigDecimal::of($quantity)->multipliedBy($uom->conversion_factor)->toScale($precision, RoundingMode::HALF_UP)->__toString()` | | |
| TASK-008 | Implement `convertFromBaseUnit(string|BigDecimal $baseQuantity, Uom|string $targetUom): string` helper method that converts base unit quantity to target unit. Use division: `BigDecimal::of($baseQuantity)->dividedBy($targetUom->conversion_factor, $targetUom->precision, RoundingMode::HALF_UP)->__toString()` | | |

### GOAL-002: Create Validation and Compatibility Checking Actions

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-UOM-003, IR-UOM-001 | Implement validation actions to check unit compatibility, prevent invalid conversions, and provide compatibility lists for UI dropdowns | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Create `app/Domains/UnitOfMeasure/Actions/ValidateUomCompatibilityAction.php` using AsAction trait. Add `declare(strict_types=1);`. Inject UomRepositoryContract | | |
| TASK-010 | Implement `handle(Uom|string $uom1, Uom|string $uom2): bool` method that returns true if UOMs are in same category, false otherwise. Use repository to resolve UOM models if strings. Never throw exception, just return boolean | | |
| TASK-011 | Add `rules(): array` method returning validation rules for Laravel validation: `['uom1' => ['required', 'exists:uoms,code'], 'uom2' => ['required', 'exists:uoms,code']]` | | |
| TASK-012 | Create `app/Domains/UnitOfMeasure/Actions/GetCompatibleUomsAction.php` using AsAction trait. Inject UomRepositoryContract | | |
| TASK-013 | Implement `handle(Uom|string $uom, ?string $tenantId = null): Collection` method that returns all UOMs (system + tenant) in the same category as input UOM. Use `$repository->findByCategory($uom->category, $tenantId)` with active filter | | |
| TASK-014 | Add caching to GetCompatibleUomsAction: cache results for 1 hour using cache key `uom:compatible:{category}:{tenantId}`. Clear cache when new tenant UOM is created in that category | | |

### GOAL-003: Implement ConvertQuantityAction for Module Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-UOM-005, IR-UOM-001, PR-UOM-001, PR-UOM-002 | Create primary action for quantity conversion that modules will use, with validation, performance optimization, and error handling | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Create `app/Domains/UnitOfMeasure/Actions/ConvertQuantityAction.php` using AsAction trait. Add `declare(strict_types=1);`. Inject UomConversionService | | |
| TASK-016 | Implement `handle(string $quantity, string $fromUomCode, string $toUomCode, ?string $tenantId = null): array` method that returns `['quantity' => string, 'from_uom' => string, 'to_uom' => string, 'conversion_factor' => string]`. Use service convert() method internally | | |
| TASK-017 | Add input validation at start of handle(): validate quantity is numeric and > 0 using `is_numeric()` and comparison. Throw `InvalidQuantityException` if invalid | | |
| TASK-018 | Add caching for frequently used conversion pairs: cache conversion_factor for (from_uom, to_uom) pair for 24 hours. Use cache key `uom:conversion:{fromCode}:{toCode}`. This avoids repeated database lookups and calculations | | |
| TASK-019 | Add performance monitoring: log warning if conversion takes > 5ms (PR-UOM-002 requirement). Use microtime(true) before/after service call. Include UOM codes and quantity in log for debugging | | |
| TASK-020 | Implement `asCommand()` method to expose as Artisan command: `php artisan uom:convert {quantity} {from} {to}`. Output result in format: "{quantity} {from} = {result} {to}" | | |
| TASK-021 | Implement `asJob()` method for async conversion (future use in batch imports). Queue job to 'conversions' queue with retry = 3 | | |

### GOAL-004: Create Conversion Exception Hierarchy

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-UOM-003 | Define custom exception classes for conversion errors to provide clear error messages and proper HTTP status codes in API responses | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create `app/Domains/UnitOfMeasure/Exceptions/UomConversionException.php` as base exception class extending `\RuntimeException`. Add `declare(strict_types=1);`. Set default message "UOM conversion failed" and code 400 | | |
| TASK-023 | Create `app/Domains/UnitOfMeasure/Exceptions/IncompatibleUomException.php` extending UomConversionException. Constructor accepts `(string $fromCategory, string $toCategory)`. Message: "Cannot convert between incompatible categories: {fromCategory} and {toCategory}". Set HTTP code 422 (Unprocessable Entity) | | |
| TASK-024 | Create `app/Domains/UnitOfMeasure/Exceptions/UomNotFoundException.php` extending UomConversionException. Constructor accepts `(string $code)`. Message: "Unit of measure not found: {code}". Set HTTP code 404 | | |
| TASK-025 | Create `app/Domains/UnitOfMeasure/Exceptions/InvalidQuantityException.php` extending UomConversionException. Constructor accepts `(string $quantity, string $reason)`. Message: "Invalid quantity '{quantity}': {reason}". Set HTTP code 422 | | |
| TASK-026 | Register exception handler in `app/Exceptions/Handler.php`: map UomConversionException and subclasses to JSON responses with appropriate HTTP codes. Return format: `{'error': 'IncompatibleUomException', 'message': '...', 'code': 422}` | | |

### GOAL-005: Add Conversion Testing Suite with Precision Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-UOM-001, PR-UOM-002 | Create comprehensive test suite that validates conversion accuracy, performance, and edge cases using known conversion values | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create feature test `tests/Feature/UnitOfMeasure/UomConversionTest.php`. Add `declare(strict_types=1);`. Use RefreshDatabase trait. Seed standard UOMs in setUp() | | |
| TASK-028 | Test basic conversion accuracy: `test_converts_mass_accurately()` - convert 100 kg to lb, expect 220.462 lb with 3 decimal precision. Validate result matches within 0.0001% tolerance (PR-UOM-001). Assert using BigDecimal comparison: `BigDecimal::of($result)->minus($expected)->abs()->isLessThan('0.001')` | | |
| TASK-029 | Test conversion performance: `test_conversion_completes_within_5ms()` - measure time for 100 conversions using microtime(true). Assert average time < 5ms (PR-UOM-002). Use dataset with 10 different conversion pairs to avoid caching bias | | |
| TASK-030 | Test bidirectional conversion: `test_bidirectional_conversion_maintains_precision()` - convert 100 kg → lb → kg. Assert result equals original within tolerance. This validates conversion factor accuracy | | |
| TASK-031 | Test incompatible units: `test_throws_exception_for_incompatible_categories()` - attempt to convert kg (mass) to m (length). Assert IncompatibleUomException thrown with correct message | | |
| TASK-032 | Test edge cases: `test_handles_zero_quantity()`, `test_handles_very_large_numbers()` (1e15), `test_handles_very_small_numbers()` (1e-10). Assert no precision loss using BigDecimal assertions | | |
| TASK-033 | Test rounding modes: `test_applies_correct_rounding_mode()` - convert 10.666666 kg to g with precision=2. Test HALF_UP (10.67), FLOOR (10.66), CEILING (10.67) rounding modes. Assert exact results | | |
| TASK-034 | Test caching: `test_caches_conversion_factors()` - perform same conversion twice, assert second call hits cache (verify with cache spy). Assert cache key format correct | | |

## 3. Alternatives

- **ALT-001**: Use PHP native float arithmetic with round() function - **Rejected** because float precision errors accumulate in multi-step conversions (0.1 + 0.2 !== 0.3 in float)
- **ALT-002**: Pre-calculate all conversion pairs and store in database - **Rejected** because it creates O(n²) storage for n units and becomes unmanageable with custom tenant UOMs
- **ALT-003**: Use monetary package (moneyphp/money) for arithmetic - **Rejected** because brick/math is more flexible, widely adopted, and designed for general decimal arithmetic (not just currency)
- **ALT-004**: Implement custom BigDecimal wrapper - **Rejected** because brick/math is battle-tested, well-maintained, and used by major Laravel projects

## 4. Dependencies

**Package Dependencies:**
- **DEP-001**: `brick/math` ^0.12 (MANDATORY for precision arithmetic)
- **DEP-002**: `lorisleiva/laravel-actions` ^2.0 (Action pattern support)
- **DEP-003**: Redis extension (optional for caching optimization)

**Internal Dependencies:**
- **DEP-004**: PRD01-SUB06-PLAN01 (UOM Foundation) - MUST be completed first for Uom model and repository
- **DEP-005**: PRD01-SUB01 (Multi-Tenancy) - For tenant context resolution

**Infrastructure:**
- **DEP-006**: PHP 8.2+ with bcmath extension (required by brick/math)
- **DEP-007**: Redis (recommended for conversion factor caching)

## 5. Files

**Services:**
- `app/Domains/UnitOfMeasure/Services/UomConversionService.php` - Core conversion logic with brick/math

**Actions:**
- `app/Domains/UnitOfMeasure/Actions/ConvertQuantityAction.php` - Primary conversion action for module integration
- `app/Domains/UnitOfMeasure/Actions/ValidateUomCompatibilityAction.php` - Compatibility validation
- `app/Domains/UnitOfMeasure/Actions/GetCompatibleUomsAction.php` - Compatible UOM listing

**Exceptions:**
- `app/Domains/UnitOfMeasure/Exceptions/UomConversionException.php` - Base exception
- `app/Domains/UnitOfMeasure/Exceptions/IncompatibleUomException.php` - Category mismatch exception
- `app/Domains/UnitOfMeasure/Exceptions/UomNotFoundException.php` - UOM not found exception
- `app/Domains/UnitOfMeasure/Exceptions/InvalidQuantityException.php` - Invalid quantity exception

**Tests:**
- `tests/Feature/UnitOfMeasure/UomConversionTest.php` - Conversion accuracy and performance tests
- `tests/Unit/UnitOfMeasure/UomConversionServiceTest.php` - Unit tests for service methods

**Exception Handler (modified):**
- `app/Exceptions/Handler.php` - Register UOM exception mappings

## 6. Testing

**Unit Tests (12 tests):**
- **TEST-001**: `test_conversion_service_uses_big_decimal_for_arithmetic` - Mock brick/math calls
- **TEST-002**: `test_convert_to_base_unit_multiplies_by_conversion_factor` - Test 100 lb → 45.3592 kg
- **TEST-003**: `test_convert_from_base_unit_divides_by_conversion_factor` - Test 45.3592 kg → 100 lb
- **TEST-004**: `test_direct_conversion_returns_input_unchanged` - Test kg → kg
- **TEST-005**: `test_validate_compatibility_returns_true_for_same_category` - Test kg vs lb
- **TEST-006**: `test_validate_compatibility_returns_false_for_different_categories` - Test kg vs m
- **TEST-007**: `test_get_compatible_uoms_returns_all_category_uoms` - Test mass category returns 7 units
- **TEST-008**: `test_incompatible_uom_exception_has_correct_message` - Test exception message format
- **TEST-009**: `test_uom_not_found_exception_thrown_for_invalid_code` - Test exception for 'xyz' code
- **TEST-010**: `test_invalid_quantity_exception_thrown_for_negative_numbers` - Test -100 throws exception
- **TEST-011**: `test_conversion_result_preserves_precision` - Test string return format with 10 decimals
- **TEST-012**: `test_rounding_mode_applied_correctly` - Test HALF_UP vs FLOOR rounding

**Feature Tests (8 tests):**
- **TEST-013**: `test_converts_mass_accurately` - Covered in TASK-028
- **TEST-014**: `test_conversion_completes_within_5ms` - Covered in TASK-029
- **TEST-015**: `test_bidirectional_conversion_maintains_precision` - Covered in TASK-030
- **TEST-016**: `test_throws_exception_for_incompatible_categories` - Covered in TASK-031
- **TEST-017**: `test_handles_zero_quantity` - Covered in TASK-032
- **TEST-018**: `test_handles_very_large_numbers` - Covered in TASK-032
- **TEST-019**: `test_applies_correct_rounding_mode` - Covered in TASK-033
- **TEST-020**: `test_caches_conversion_factors` - Covered in TASK-034

**Integration Tests (5 tests):**
- **TEST-021**: `test_convert_quantity_action_integrates_with_service` - End-to-end action test
- **TEST-022**: `test_artisan_command_converts_correctly` - Test `php artisan uom:convert`
- **TEST-023**: `test_conversion_respects_tenant_custom_uoms` - Test tenant-scoped UOM conversion
- **TEST-024**: `test_cache_invalidation_after_uom_update` - Test cache clearing
- **TEST-025**: `test_concurrent_conversions_do_not_interfere` - Test thread-safety with parallel requests

**Performance Tests (3 tests):**
- **TEST-026**: `test_1000_conversions_complete_within_5_seconds` - Batch conversion performance
- **TEST-027**: `test_cache_hit_rate_above_90_percent` - Test caching effectiveness
- **TEST-028**: `test_memory_usage_stable_for_large_batches` - Test no memory leaks in loops

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: brick/math dependency adds external library risk - **Mitigation**: Lock version in composer.json, monitor for security updates, extensive test coverage
- **RISK-002**: Rounding errors accumulate in multi-step conversions (kg → lb → oz) - **Mitigation**: Always convert via base unit in single operation, not chained conversions. Document best practice
- **RISK-003**: Cache invalidation bugs could cause stale conversion factors - **Mitigation**: Use cache tags, clear all UOM cache on any UOM update, test cache invalidation extensively
- **RISK-004**: Performance degradation with 10,000+ custom UOMs - **Mitigation**: Implement query optimization, add database indexes, use Redis caching aggressively
- **RISK-005**: Tenant custom UOMs with incorrect conversion factors corrupt data - **Mitigation**: Validate conversion_factor > 0 and reasonable (not 1e15), add admin review for custom UOMs

**Assumptions:**
- **ASSUMPTION-001**: bcmath PHP extension is available in all deployment environments
- **ASSUMPTION-002**: Redis is available for caching (fallback to database cache if not)
- **ASSUMPTION-003**: Conversions will primarily be between system UOMs (95%+ of usage)
- **ASSUMPTION-004**: Multi-step conversions (kg → lb → oz) are rare and can be discouraged
- **ASSUMPTION-005**: Rounding to target UOM precision is acceptable behavior (users don't need arbitrary precision retention)

## 8. Related PRD / Further Reading

- **Master PRD:** [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD:** [../prd/prd-01/PRD01-SUB06-UOM.md](../prd/prd-01/PRD01-SUB06-UOM.md)
- **Related Plans:**
  - PRD01-SUB06-PLAN01 (UOM Foundation) - Prerequisites
  - PRD01-SUB06-PLAN03 (UOM API & Integration) - Consumers of this conversion engine
- **External Documentation:**
  - brick/math Documentation: https://github.com/brick/math
  - brick/math BigDecimal API: https://github.com/brick/math/blob/master/src/BigDecimal.php
  - PHP bcmath Extension: https://www.php.net/manual/en/book.bc.php
  - NIST Unit Conversion Accuracy Standards: https://www.nist.gov/pml/weights-and-measures/metric-si/si-units
