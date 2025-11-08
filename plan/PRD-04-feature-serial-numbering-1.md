---
goal: Integrate Serial Numbering System for Automatic Document Numbering
version: 1.0
date_created: 2025-11-08
last_updated: 2025-11-08
owner: Core Domain Team
status: 'Planned'
tags: [feature, core, serial-numbering, automation, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the integration of the `azaharizaman/laravel-serial-numbering` package to provide automatic, configurable serial number generation for all business documents in the Laravel ERP. The system will generate unique, formatted document numbers for sales orders, purchase orders, invoices, items, customers, vendors, and all other entities requiring sequential identification.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate azaharizaman/laravel-serial-numbering package
- **REQ-002**: Configure serial number patterns for all document types
- **REQ-003**: Support pattern variables: {year}, {month}, {day}, {number}, {tenant}
- **REQ-004**: Implement auto-incrementing numbers with configurable digits and padding
- **REQ-005**: Support multiple reset periods: daily, monthly, yearly, never
- **REQ-006**: Ensure generated numbers are unique per tenant
- **REQ-007**: Support manual number override with validation
- **REQ-008**: Implement preview functionality for serial patterns
- **REQ-009**: Support configurable starting number per pattern
- **REQ-010**: Thread-safe number generation to prevent duplicates in concurrent requests
- **REQ-011**: Apply serial numbering to: Sales Orders, Purchase Orders, Invoices, Items (SKU), Customers, Vendors, Quotations, Stock Movements, Goods Receipt Notes

**Pattern Requirements:**
- **REQ-012**: Sales Orders: SO-{year}{month}-{number:5} (e.g., SO-202511-00001)
- **REQ-013**: Purchase Orders: PO-{year}{month}-{number:5} (e.g., PO-202511-00001)
- **REQ-014**: Invoices: INV-{year}{month}-{number:5} (e.g., INV-202511-00001)
- **REQ-015**: Stock Movements: SM-{year}{month}{day}-{number:6} (e.g., SM-20251108-000001)
- **REQ-016**: Items (SKU): ITEM-{year}-{number:6} (e.g., ITEM-2025-000001)
- **REQ-017**: Customers: CUST-{number:6} (e.g., CUST-000001)
- **REQ-018**: Vendors: VEND-{number:6} (e.g., VEND-000001)
- **REQ-019**: Sales Quotations: SQ-{year}{month}-{number:5} (e.g., SQ-202511-00001)
- **REQ-020**: Goods Receipt Notes: GRN-{year}{month}-{number:5} (e.g., GRN-202511-00001)

**Security Requirements:**
- **SEC-001**: Prevent number reuse or overwrites to maintain audit integrity
- **SEC-002**: Log all serial number generation in activity log
- **SEC-003**: Restrict pattern modification to Super Admin only
- **SEC-004**: Validate manual number inputs to prevent duplicates

**Performance Constraints:**
- **CON-001**: Serial number generation must complete within 50ms
- **CON-002**: Use database transactions to prevent race conditions
- **CON-003**: Support minimum 100 concurrent number generations per second

**Integration Guidelines:**
- **GUD-001**: Use HasSerialNumbering trait on all models requiring auto-numbering
- **GUD-002**: Configure serial patterns in config file, not database
- **GUD-003**: Generate serial number during model creation event (creating/created)
- **GUD-004**: Allow pattern customization per tenant via settings

**Design Patterns:**
- **PAT-001**: Use trait-based approach (HasSerialNumbering) for model integration
- **PAT-002**: Apply observer pattern for automatic number generation
- **PAT-003**: Use database locking to ensure thread safety
- **PAT-004**: Implement service layer for pattern preview and validation

## 2. Implementation Steps

### Implementation Phase 1: Package Installation

- GOAL-001: Install and configure laravel-serial-numbering package

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Install azaharizaman/laravel-serial-numbering package: composer require azaharizaman/laravel-serial-numbering:dev-main | | |
| TASK-002 | Publish package configuration: php artisan vendor:publish --provider="AzahariZaman\LaravelSerialNumbering\SerialNumberingServiceProvider" | | |
| TASK-003 | Run package migrations to create serial_numbers table | | |
| TASK-004 | Review serial_numbers table schema: pattern_name, last_number, reset_period, created_at, updated_at | | |
| TASK-005 | Add tenant_id column to serial_numbers table for multi-tenant isolation | | |
| TASK-006 | Add unique index on (tenant_id, pattern_name) to ensure unique patterns per tenant | | |

### Implementation Phase 2: Pattern Configuration

- GOAL-002: Define serial number patterns for all document types

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Open config/serial-numbering.php for pattern configuration | | |
| TASK-008 | Define sales_order pattern: 'pattern' => 'SO-{year}{month}-{number}', 'digits' => 5, 'start' => 1, 'reset' => 'monthly' | | |
| TASK-009 | Define purchase_order pattern: 'pattern' => 'PO-{year}{month}-{number}', 'digits' => 5, 'start' => 1, 'reset' => 'monthly' | | |
| TASK-010 | Define invoice pattern: 'pattern' => 'INV-{year}{month}-{number}', 'digits' => 5, 'start' => 1, 'reset' => 'monthly' | | |
| TASK-011 | Define stock_movement pattern: 'pattern' => 'SM-{year}{month}{day}-{number}', 'digits' => 6, 'start' => 1, 'reset' => 'daily' | | |
| TASK-012 | Define item pattern: 'pattern' => 'ITEM-{year}-{number}', 'digits' => 6, 'start' => 1, 'reset' => 'yearly' | | |
| TASK-013 | Define customer pattern: 'pattern' => 'CUST-{number}', 'digits' => 6, 'start' => 1, 'reset' => 'never' | | |
| TASK-014 | Define vendor pattern: 'pattern' => 'VEND-{number}', 'digits' => 6, 'start' => 1, 'reset' => 'never' | | |
| TASK-015 | Define sales_quotation pattern: 'pattern' => 'SQ-{year}{month}-{number}', 'digits' => 5, 'start' => 1, 'reset' => 'monthly' | | |
| TASK-016 | Define goods_receipt pattern: 'pattern' => 'GRN-{year}{month}-{number}', 'digits' => 5, 'start' => 1, 'reset' => 'monthly' | | |
| TASK-017 | Validate all patterns generate correctly with test dates | | |

### Implementation Phase 3: Model Integration - Sales Domain

- GOAL-003: Integrate serial numbering into Sales domain models

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-018 | Add HasSerialNumbering trait to SalesOrder model | | |
| TASK-019 | Set protected $serialPattern = 'sales_order' on SalesOrder model | | |
| TASK-020 | Set protected $serialColumn = 'order_number' on SalesOrder model | | |
| TASK-021 | Ensure order_number column exists in sales_orders table migration | | |
| TASK-022 | Add HasSerialNumbering trait to SalesQuotation model | | |
| TASK-023 | Set protected $serialPattern = 'sales_quotation' and $serialColumn = 'quote_number' | | |
| TASK-024 | Add HasSerialNumbering trait to Customer model | | |
| TASK-025 | Set protected $serialPattern = 'customer' and $serialColumn = 'customer_code' | | |
| TASK-026 | Test serial number generation on Sales model creation | | |

### Implementation Phase 4: Model Integration - Purchasing Domain

- GOAL-004: Integrate serial numbering into Purchasing domain models

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Add HasSerialNumbering trait to PurchaseOrder model | | |
| TASK-028 | Set protected $serialPattern = 'purchase_order' and $serialColumn = 'po_number' | | |
| TASK-029 | Ensure po_number column exists in purchase_orders table migration | | |
| TASK-030 | Add HasSerialNumbering trait to GoodsReceiptNote model | | |
| TASK-031 | Set protected $serialPattern = 'goods_receipt' and $serialColumn = 'grn_number' | | |
| TASK-032 | Add HasSerialNumbering trait to Vendor model | | |
| TASK-033 | Set protected $serialPattern = 'vendor' and $serialColumn = 'vendor_code' | | |
| TASK-034 | Test serial number generation on Purchasing model creation | | |

### Implementation Phase 5: Model Integration - Inventory Domain

- GOAL-005: Integrate serial numbering into Inventory domain models

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-035 | Add HasSerialNumbering trait to Item model | | |
| TASK-036 | Set protected $serialPattern = 'item' and $serialColumn = 'sku' | | |
| TASK-037 | Ensure sku column exists in inventory_items table migration with unique constraint | | |
| TASK-038 | Add HasSerialNumbering trait to StockMovement model | | |
| TASK-039 | Set protected $serialPattern = 'stock_movement' and $serialColumn = 'movement_number' | | |
| TASK-040 | Test serial number generation on Inventory model creation | | |

### Implementation Phase 6: Multi-Tenant Support

- GOAL-006: Ensure serial number isolation per tenant

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-041 | Add tenant_id column to serial_numbers table if not already present | | |
| TASK-042 | Create migration to add tenant_id: $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade') | | |
| TASK-043 | Add index on (tenant_id, pattern_name, reset_period) for performance | | |
| TASK-044 | Modify package's number generation logic to scope by tenant_id (if package doesn't support, extend it) | | |
| TASK-045 | Create SerialNumberService wrapper in app/Domains/Core/Services/SerialNumberService.php | | |
| TASK-046 | Implement getTenantScopedGenerator() method to inject tenant context | | |
| TASK-047 | Test number generation across multiple tenants ensures no conflicts | | |

### Implementation Phase 7: Manual Override Support

- GOAL-007: Allow manual serial number entry with validation

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-048 | Add $allowManualSerialNumber = true property to models supporting manual override | | |
| TASK-049 | Modify model observers to skip auto-generation if serial column already has value | | |
| TASK-050 | Create ValidateSerialNumberAction in app/Domains/Core/Actions/ValidateSerialNumberAction.php | | |
| TASK-051 | Implement format validation: check against pattern regex | | |
| TASK-052 | Implement uniqueness validation: check database for existing number in tenant | | |
| TASK-053 | Return validation errors with specific messages | | |
| TASK-054 | Apply ValidateSerialNumberAction in Form Requests where manual entry is allowed | | |
| TASK-055 | Test manual number entry with valid and invalid formats | | |

### Implementation Phase 8: Pattern Preview Service

- GOAL-008: Implement pattern preview functionality for testing

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-056 | Create SerialNumberPreviewService in app/Domains/Core/Services/SerialNumberPreviewService.php | | |
| TASK-057 | Implement preview() method accepting pattern and sample date | | |
| TASK-058 | Generate 10 sample numbers based on pattern and date | | |
| TASK-059 | Support custom number ranges in preview: start, end | | |
| TASK-060 | Return array of sample generated numbers | | |
| TASK-061 | Create API endpoint GET /api/v1/serial-numbers/preview | | |
| TASK-062 | Accept query parameters: pattern, date, count | | |
| TASK-063 | Return JSON array of previewed serial numbers | | |

### Implementation Phase 9: Pattern Management API

- GOAL-009: Create API endpoints for pattern management (admin only)

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-064 | Create SerialNumberPatternController in app/Http/Controllers/Api/V1/SerialNumberPatternController.php | | |
| TASK-065 | Implement index() method listing all configured patterns with current numbers | | |
| TASK-066 | Implement show() method displaying pattern details and recent generated numbers | | |
| TASK-067 | Implement reset() method to reset counter for a pattern (admin only, with confirmation) | | |
| TASK-068 | Implement setStartNumber() method to change starting number (admin only) | | |
| TASK-069 | Create SerialNumberPatternResource in app/Http/Resources/SerialNumberPatternResource.php | | |
| TASK-070 | Include pattern name, format, current number, reset period, last generated | | |
| TASK-071 | Define routes in routes/api.php under /api/v1/serial-numbers | | |
| TASK-072 | Apply auth:sanctum and admin middleware to all routes except preview | | |

### Implementation Phase 10: CLI Commands

- GOAL-010: Create CLI commands for serial number operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-073 | Create ListSerialPatternsCommand in app/Console/Commands/SerialNumber/ListSerialPatternsCommand.php with signature erp:serial:list | | |
| TASK-074 | Display table with columns: Pattern Name, Current Format, Current Number, Reset Period | | |
| TASK-075 | Add --tenant option to filter by tenant | | |
| TASK-076 | Create PreviewSerialPatternCommand in app/Console/Commands/SerialNumber/PreviewSerialPatternCommand.php with signature erp:serial:preview {pattern} | | |
| TASK-077 | Generate and display 10 sample numbers for specified pattern | | |
| TASK-078 | Add --count option to specify number of samples | | |
| TASK-079 | Create ResetSerialPatternCommand in app/Console/Commands/SerialNumber/ResetSerialPatternCommand.php with signature erp:serial:reset {pattern} | | |
| TASK-080 | Add --tenant option and confirmation prompt | | |
| TASK-081 | Reset counter to starting number and display confirmation | | |
| TASK-082 | Register commands in app/Console/Kernel.php | | |

### Implementation Phase 11: Audit Logging Integration

- GOAL-011: Log all serial number generation and pattern changes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-083 | Create SerialNumberObserver in app/Domains/Core/Observers/SerialNumberObserver.php | | |
| TASK-084 | Listen for serial number creation event | | |
| TASK-085 | Log activity with details: pattern, generated number, model type, model ID | | |
| TASK-086 | Tag logs with log_name: 'serial-numbering' | | |
| TASK-087 | Log pattern reset events with admin user and reason | | |
| TASK-088 | Log manual serial number entries separately | | |
| TASK-089 | Register observer in EventServiceProvider | | |

### Implementation Phase 12: Error Handling & Edge Cases

- GOAL-012: Handle errors and edge cases gracefully

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-090 | Create SerialNumberException in app/Domains/Core/Exceptions/SerialNumberException.php | | |
| TASK-091 | Handle pattern not found error with clear message | | |
| TASK-092 | Handle duplicate number error with retry logic (max 3 attempts) | | |
| TASK-093 | Handle database lock timeout with appropriate error message | | |
| TASK-094 | Implement fallback number generation if pattern fails | | |
| TASK-095 | Log all serial number errors to error log | | |
| TASK-096 | Display user-friendly error messages in API responses | | |

### Implementation Phase 13: Database Seeding

- GOAL-013: Seed initial serial number patterns for development and testing

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-097 | Create SerialNumberSeeder in database/seeders/SerialNumberSeeder.php | | |
| TASK-098 | Seed serial_numbers table with initial records for all patterns | | |
| TASK-099 | Set last_number to 0 for each pattern | | |
| TASK-100 | Include tenant_id for default tenant if applicable | | |
| TASK-101 | Call SerialNumberSeeder from DatabaseSeeder | | |
| TASK-102 | Test seeding generates correct initial state | | |

### Implementation Phase 14: Testing

- GOAL-014: Create comprehensive test suite for serial numbering

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-103 | Create SerialNumberTest feature test in tests/Feature/Core/SerialNumberTest.php | | |
| TASK-104 | Test sales order creation auto-generates order_number | | |
| TASK-105 | Test generated number follows configured pattern | | |
| TASK-106 | Test number increments correctly on subsequent creations | | |
| TASK-107 | Test reset period: monthly reset changes prefix month | | |
| TASK-108 | Test reset period: daily reset changes prefix day | | |
| TASK-109 | Test tenant isolation: tenant A and tenant B get separate number sequences | | |
| TASK-110 | Test manual number override with valid format | | |
| TASK-111 | Test manual number override rejects invalid format (422) | | |
| TASK-112 | Test manual number override rejects duplicate number (422) | | |
| TASK-113 | Test concurrent number generation (simulate race condition) | | |
| TASK-114 | Test GET /api/v1/serial-numbers/preview returns sample numbers | | |
| TASK-115 | Test POST /api/v1/serial-numbers/{pattern}/reset resets counter | | |
| TASK-116 | Create SerialNumberServiceTest unit test in tests/Unit/Core/SerialNumberServiceTest.php | | |
| TASK-117 | Test pattern parsing extracts variables correctly | | |
| TASK-118 | Test number formatting pads digits correctly | | |
| TASK-119 | Test date variable substitution | | |

## 3. Alternatives

- **ALT-001**: Build custom serial numbering from scratch - Rejected because azaharizaman/laravel-serial-numbering already exists and provides required functionality. Leveraging existing package reduces development time and bugs.

- **ALT-002**: Use database auto-increment for document numbers - Rejected because auto-increment doesn't support formatted numbers with date components, prefixes, or reset periods. Lacks flexibility for business requirements.

- **ALT-003**: UUID for all document identifiers - Rejected because UUIDs are not user-friendly, don't convey business meaning (date, type), and are difficult to communicate verbally or in paper-based processes.

- **ALT-004**: Store patterns in database instead of config - Considered for tenant-specific customization but deferred to Phase 2. Config-based patterns are simpler and sufficient for MVP.

## 4. Dependencies

- **DEP-001**: Laravel 12.x framework installed and configured
- **DEP-002**: PHP 8.2+ for typed properties
- **DEP-003**: azaharizaman/laravel-serial-numbering package (install via composer)
- **DEP-004**: Tenant system must be implemented (PRD-01) for multi-tenant scoping
- **DEP-005**: All domain models must exist before applying HasSerialNumbering trait
- **DEP-006**: Database migrations for serial number columns on models must be created
- **DEP-007**: Audit logging system (PRD-03) for logging serial number activities

## 5. Files

**New Files to Create:**
- **FILE-001**: database/migrations/YYYY_MM_DD_HHMMSS_add_tenant_id_to_serial_numbers_table.php - Multi-tenant support
- **FILE-002**: app/Domains/Core/Services/SerialNumberService.php - Tenant-scoped serial number service
- **FILE-003**: app/Domains/Core/Services/SerialNumberPreviewService.php - Pattern preview service
- **FILE-004**: app/Domains/Core/Actions/ValidateSerialNumberAction.php - Manual number validation
- **FILE-005**: app/Http/Controllers/Api/V1/SerialNumberPatternController.php - Pattern management API
- **FILE-006**: app/Http/Resources/SerialNumberPatternResource.php - API resource
- **FILE-007**: app/Console/Commands/SerialNumber/ListSerialPatternsCommand.php - CLI list command
- **FILE-008**: app/Console/Commands/SerialNumber/PreviewSerialPatternCommand.php - CLI preview command
- **FILE-009**: app/Console/Commands/SerialNumber/ResetSerialPatternCommand.php - CLI reset command
- **FILE-010**: app/Domains/Core/Observers/SerialNumberObserver.php - Audit logging observer
- **FILE-011**: app/Domains/Core/Exceptions/SerialNumberException.php - Custom exception
- **FILE-012**: database/seeders/SerialNumberSeeder.php - Initial pattern seeder

**Files to Modify:**
- **FILE-013**: config/serial-numbering.php - Configure all patterns
- **FILE-014**: app/Domains/Sales/Models/SalesOrder.php - Add HasSerialNumbering trait
- **FILE-015**: app/Domains/Sales/Models/SalesQuotation.php - Add HasSerialNumbering trait
- **FILE-016**: app/Domains/Sales/Models/Customer.php - Add HasSerialNumbering trait
- **FILE-017**: app/Domains/Purchasing/Models/PurchaseOrder.php - Add HasSerialNumbering trait
- **FILE-018**: app/Domains/Purchasing/Models/GoodsReceiptNote.php - Add HasSerialNumbering trait
- **FILE-019**: app/Domains/Purchasing/Models/Vendor.php - Add HasSerialNumbering trait
- **FILE-020**: app/Domains/Inventory/Models/Item.php - Add HasSerialNumbering trait
- **FILE-021**: app/Domains/Inventory/Models/StockMovement.php - Add HasSerialNumbering trait
- **FILE-022**: app/Providers/EventServiceProvider.php - Register SerialNumberObserver
- **FILE-023**: routes/api.php - Define serial number routes
- **FILE-024**: database/seeders/DatabaseSeeder.php - Call SerialNumberSeeder

**Test Files:**
- **FILE-025**: tests/Feature/Core/SerialNumberTest.php - Feature tests
- **FILE-026**: tests/Unit/Core/SerialNumberServiceTest.php - Service unit tests

## 6. Testing

**Unit Tests:**
- **TEST-001**: Test pattern parsing extracts {year}, {month}, {day}, {number} variables
- **TEST-002**: Test number formatting pads with correct number of zeros
- **TEST-003**: Test date substitution uses current date correctly
- **TEST-004**: Test reset period logic determines when to reset counter
- **TEST-005**: Test ValidateSerialNumberAction validates pattern format
- **TEST-006**: Test ValidateSerialNumberAction checks uniqueness
- **TEST-007**: Test SerialNumberPreviewService generates sample numbers

**Feature Tests:**
- **TEST-008**: Test SalesOrder creation auto-generates order_number
- **TEST-009**: Test generated order_number follows SO-YYYYMM-NNNNN pattern
- **TEST-010**: Test sequential orders increment number correctly
- **TEST-011**: Test monthly reset changes month prefix and resets counter
- **TEST-012**: Test daily reset changes day prefix and resets counter
- **TEST-013**: Test tenant isolation: different tenants get separate sequences
- **TEST-014**: Test manual number entry with valid format succeeds
- **TEST-015**: Test manual number entry with invalid format fails (422)
- **TEST-016**: Test manual number entry with duplicate fails (422)
- **TEST-017**: Test concurrent creation doesn't generate duplicate numbers
- **TEST-018**: Test GET /api/v1/serial-numbers/preview returns samples
- **TEST-019**: Test POST /api/v1/serial-numbers/{pattern}/reset resets counter
- **TEST-020**: Test GET /api/v1/serial-numbers lists all patterns

**Integration Tests:**
- **TEST-021**: Test complete order lifecycle with serial numbering
- **TEST-022**: Test serial number audit logging captures generation
- **TEST-023**: Test pattern reset audit logging

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Race condition in concurrent number generation - Mitigation: Use database row locking, retry logic, thorough testing of concurrent scenarios
- **RISK-002**: Pattern migration complexity if format changes - Mitigation: Version patterns, support legacy format parsing, provide migration tools
- **RISK-003**: Number exhaustion in fixed-digit patterns - Mitigation: Monitor usage, alerting at 80% capacity, support pattern expansion
- **RISK-004**: Performance degradation with high-volume number generation - Mitigation: Optimize queries, consider caching, database connection pooling

**Assumptions:**
- **ASSUMPTION-001**: Configured digit lengths are sufficient (5-6 digits = 99,999-999,999 numbers per period)
- **ASSUMPTION-002**: Monthly reset period is appropriate for most document types
- **ASSUMPTION-003**: Serial number format requirements won't change frequently
- **ASSUMPTION-004**: Database locking is sufficient for thread safety (no distributed locks needed)
- **ASSUMPTION-005**: Manual number entry is rare edge case
- **ASSUMPTION-006**: Pattern preview with 10 samples is adequate for testing
- **ASSUMPTION-007**: Package supports tenant scoping or can be easily extended

## 8. Related Specifications / Further Reading

- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Overall Phase 1 requirements
- [PRD-01-infrastructure-multitenancy-1.md](./PRD-01-infrastructure-multitenancy-1.md) - Multi-tenancy system (prerequisite)
- [Laravel Serial Numbering Package](https://github.com/azaharizaman/laravel-serial-numbering)
- [Sequential Document Numbering Best Practices](https://www.accountingtools.com/articles/how-to-create-document-numbering-systems.html)
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Module development guidelines
