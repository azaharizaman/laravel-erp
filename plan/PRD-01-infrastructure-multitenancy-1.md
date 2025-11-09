---
goal: Implement Multi-Tenancy System Foundation
version: 1.0
date_created: 2025-11-08
last_updated: 2025-11-08
owner: Core Domain Team
status: 'Planned'
tags: [infrastructure, core, multitenancy, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan details the development of the multi-tenancy system foundation for the Laravel ERP. The multi-tenancy system is critical infrastructure that enables the ERP to serve multiple organizations (tenants) from a single application instance while maintaining strict data isolation and security boundaries. This system provides tenant context management, global scoping, and tenant-specific configuration capabilities.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Implement Tenant model with UUID primary key for unique tenant identification
- **REQ-002**: Implement global scope (TenantScope) for automatic tenant filtering on all tenant-aware models
- **REQ-003**: Add tenant_id foreign key column to all domain tables with proper indexing
- **REQ-004**: Implement tenant context management via middleware for request lifecycle
- **REQ-005**: Implement TenantManager service for tenant operations and context switching
- **REQ-006**: Prevent cross-tenant data access at database and application levels
- **REQ-007**: Support tenant-specific configuration storage (JSON field)
- **REQ-008**: Implement tenant impersonation for support operations
- **REQ-009**: Create RESTful API endpoints for tenant management (admin-only access)
- **REQ-010**: Implement CLI commands for tenant operations
- **REQ-011**: Support tenant status management (active, suspended, archived)

**Security Requirements:**
- **SEC-001**: Enforce tenant isolation at database level using global scopes
- **SEC-002**: Validate tenant_id in all API requests against authenticated user's tenant
- **SEC-003**: Implement authorization policies to prevent cross-tenant access
- **SEC-004**: Log all tenant impersonation activities for audit trail
- **SEC-005**: Encrypt sensitive tenant configuration data

**Performance Constraints:**
- **CON-001**: Tenant filtering must not add more than 5ms overhead to queries
- **CON-002**: Tenant context resolution must complete in under 10ms
- **CON-003**: Support minimum 1000 concurrent tenants per instance

**Integration Guidelines:**
- **GUD-001**: All domain models must use BelongsToTenant trait
- **GUD-002**: Tenant middleware must be applied to all authenticated routes
- **GUD-003**: Follow Laravel best practices for global scopes
- **GUD-004**: Use repository pattern for tenant data access

**Design Patterns:**
- **PAT-001**: Use trait-based approach (BelongsToTenant) for model tenancy
- **PAT-002**: Implement contract-driven design (TenantManagerContract)
- **PAT-003**: Use middleware pattern for tenant context injection
- **PAT-004**: Apply repository pattern for tenant CRUD operations

## 2. Implementation Steps

### Implementation Phase 1: Core Infrastructure

- GOAL-001: Set up tenant database schema, models, and migrations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create tenants table migration with columns: id (UUID), name, domain (unique), status (enum), configuration (JSON), subscription_plan, billing_email, contact_name, contact_email, contact_phone, created_at, updated_at, deleted_at | | |
| TASK-002 | Create Tenant model in app/Domains/Core/Models/Tenant.php with casts, fillable, and soft deletes | | |
| TASK-003 | Add tenant_id column to users table migration with foreign key constraint and index | | |
| TASK-004 | Create TenantStatus enum in app/Domains/Core/Enums/TenantStatus.php with values: ACTIVE, SUSPENDED, ARCHIVED | | |
| TASK-005 | Implement LogsActivity trait on Tenant model for audit logging | | |
| TASK-006 | Create Tenant factory for testing in database/factories/TenantFactory.php | | |

### Implementation Phase 2: Tenant Scope & Trait

- GOAL-002: Implement automatic tenant filtering via global scope and trait

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create TenantScope class in app/Domains/Core/Scopes/TenantScope.php implementing Illuminate\Database\Eloquent\Scope interface | | |
| TASK-008 | Implement apply() method in TenantScope to add WHERE tenant_id = ? clause to all queries | | |
| TASK-009 | Create BelongsToTenant trait in app/Domains/Core/Traits/BelongsToTenant.php | | |
| TASK-010 | Implement bootBelongsToTenant() method to add TenantScope and auto-set tenant_id on model creation | | |
| TASK-011 | Add tenant() relationship method to BelongsToTenant trait returning BelongsTo relationship | | |
| TASK-012 | Add withoutTenantScope() and withAllTenants() helper methods to trait | | |

### Implementation Phase 3: Tenant Manager Service

- GOAL-003: Build tenant management service with context switching capabilities

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create TenantManagerContract interface in app/Domains/Core/Contracts/TenantManagerContract.php with methods: create(), setActive(), current(), impersonate(), stopImpersonation() | | |
| TASK-014 | Implement TenantManager service in app/Domains/Core/Services/TenantManager.php | | |
| TASK-015 | Implement create() method with validation and tenant initialization | | |
| TASK-016 | Implement setActive() method to set current tenant in request context | | |
| TASK-017 | Implement current() method to retrieve active tenant from context | | |
| TASK-018 | Implement impersonate() method for support staff with audit logging | | |
| TASK-019 | Implement stopImpersonation() method to restore original tenant context | | |
| TASK-020 | Bind TenantManagerContract to TenantManager in Core service provider | | |

### Implementation Phase 4: Middleware & Context Management

- GOAL-004: Create middleware for tenant resolution and context injection

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create IdentifyTenant middleware in app/Domains/Core/Middleware/IdentifyTenant.php | | |
| TASK-022 | Implement handle() method to resolve tenant from authenticated user | | |
| TASK-023 | Set tenant context in TenantManager service within middleware | | |
| TASK-024 | Handle missing tenant gracefully with appropriate error response | | |
| TASK-025 | Add middleware to app/Http/Kernel.php in api middleware group | | |
| TASK-026 | Create tenant() helper function in app/Support/Helpers/tenant.php returning current tenant | | |

### Implementation Phase 5: Repository & Actions

- GOAL-005: Implement tenant repository and action classes for CRUD operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create TenantRepositoryContract in app/Domains/Core/Contracts/TenantRepositoryContract.php with methods: findById(), findByDomain(), all(), create(), update(), archive() | | |
| TASK-028 | Implement TenantRepository in app/Domains/Core/Repositories/TenantRepository.php | | |
| TASK-029 | Create CreateTenantAction in app/Domains/Core/Actions/CreateTenantAction.php using AsAction trait | | |
| TASK-030 | Implement validation in CreateTenantAction: unique domain, valid email, required fields | | |
| TASK-031 | Dispatch TenantCreatedEvent after successful tenant creation | | |
| TASK-032 | Create UpdateTenantAction in app/Domains/Core/Actions/UpdateTenantAction.php | | |
| TASK-033 | Create ArchiveTenantAction in app/Domains/Core/Actions/ArchiveTenantAction.php with soft delete | | |

### Implementation Phase 6: API Endpoints

- GOAL-006: Build RESTful API endpoints for tenant management (admin-only)

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-034 | Create TenantController in app/Http/Controllers/Api/V1/TenantController.php | | |
| TASK-035 | Implement index() method with pagination, filtering, and sorting | | |
| TASK-036 | Implement store() method calling CreateTenantAction | | |
| TASK-037 | Implement show() method returning single tenant with relationships | | |
| TASK-038 | Implement update() method calling UpdateTenantAction | | |
| TASK-039 | Implement destroy() method calling ArchiveTenantAction | | |
| TASK-040 | Create TenantResource in app/Http/Resources/TenantResource.php for response transformation | | |
| TASK-041 | Create StoreTenantRequest in app/Http/Requests/StoreTenantRequest.php with validation rules | | |
| TASK-042 | Create UpdateTenantRequest in app/Http/Requests/UpdateTenantRequest.php | | |
| TASK-043 | Define routes in routes/api.php under /api/v1/tenants with auth:sanctum and admin middleware | | |

### Implementation Phase 7: CLI Commands

- GOAL-007: Create CLI commands for tenant operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-044 | Create CreateTenantCommand in app/Console/Commands/Tenant/CreateTenantCommand.php with signature erp:tenant:create | | |
| TASK-045 | Add command options: --name, --domain, --email with interactive prompts | | |
| TASK-046 | Call CreateTenantAction from command and display success message | | |
| TASK-047 | Create ListTenantsCommand in app/Console/Commands/Tenant/ListTenantsCommand.php with signature erp:tenant:list | | |
| TASK-048 | Format output as table with columns: ID, Name, Domain, Status, Created At | | |
| TASK-049 | Add filtering options: --status, --search | | |
| TASK-050 | Register commands in app/Console/Kernel.php | | |

### Implementation Phase 8: Events & Listeners

- GOAL-008: Implement event-driven architecture for tenant lifecycle

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-051 | Create TenantCreatedEvent in app/Domains/Core/Events/TenantCreatedEvent.php | | |
| TASK-052 | Create TenantUpdatedEvent in app/Domains/Core/Events/TenantUpdatedEvent.php | | |
| TASK-053 | Create TenantArchivedEvent in app/Domains/Core/Events/TenantArchivedEvent.php | | |
| TASK-054 | Create InitializeTenantDataListener in app/Domains/Core/Listeners/InitializeTenantDataListener.php | | |
| TASK-055 | Implement handle() method to create default roles and permissions for new tenant | | |
| TASK-056 | Register events and listeners in EventServiceProvider | | |

### Implementation Phase 9: Policies & Authorization

- GOAL-009: Implement authorization policies for tenant operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-057 | Create TenantPolicy in app/Domains/Core/Policies/TenantPolicy.php | | |
| TASK-058 | Implement viewAny() method checking for 'view tenants' permission | | |
| TASK-059 | Implement view() method checking for 'view tenants' permission | | |
| TASK-060 | Implement create() method checking for 'create tenants' permission (super admin only) | | |
| TASK-061 | Implement update() method checking for 'update tenants' permission (super admin only) | | |
| TASK-062 | Implement delete() method checking for 'delete tenants' permission (super admin only) | | |
| TASK-063 | Register TenantPolicy in AuthServiceProvider | | |

### Implementation Phase 10: Testing

- GOAL-010: Create comprehensive test suite for multi-tenancy system

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-064 | Create TenantTest feature test in tests/Feature/Core/TenantTest.php | | |
| TASK-065 | Test tenant creation via API endpoint with valid data | | |
| TASK-066 | Test tenant creation fails with duplicate domain | | |
| TASK-067 | Test tenant listing with pagination and filtering | | |
| TASK-068 | Test tenant update via API endpoint | | |
| TASK-069 | Test tenant archival and soft delete | | |
| TASK-070 | Test tenant isolation: user cannot access another tenant's data | | |
| TASK-071 | Create TenantScopeTest unit test in tests/Unit/Core/TenantScopeTest.php | | |
| TASK-072 | Test BelongsToTenant trait automatically sets tenant_id on create | | |
| TASK-073 | Test TenantScope filters queries by current tenant | | |
| TASK-074 | Test withoutTenantScope() bypasses filtering | | |
| TASK-075 | Create TenantManagerTest unit test in tests/Unit/Core/TenantManagerTest.php | | |
| TASK-076 | Test setActive() and current() methods | | |
| TASK-077 | Test impersonation functionality and audit logging | | |

## 3. Alternatives

- **ALT-001**: Single database per tenant approach - Rejected due to increased operational complexity and higher infrastructure costs. Multi-tenant single database with tenant_id filtering is more cost-effective and easier to manage.

- **ALT-002**: Schema-based multi-tenancy (PostgreSQL schemas) - Rejected because it ties the system to PostgreSQL only, violating the database-agnostic requirement.

- **ALT-003**: Using package like spatie/laravel-multitenancy - Rejected to maintain full control over tenancy logic and avoid external dependencies for core infrastructure. The requirements are specific enough to warrant custom implementation.

- **ALT-004**: Subdomain-based tenant resolution - Considered but not primary implementation. Domain-based resolution added as optional feature, with primary tenant identification via authenticated user's tenant_id for API-first architecture.

## 4. Dependencies

- **DEP-001**: Laravel 12.x framework must be installed and configured
- **DEP-002**: PHP 8.2+ required for enum support and typed properties
- **DEP-003**: Database must support UUID columns (MySQL 8.0+, PostgreSQL 13+, SQLite 3.37+)
- **DEP-004**: spatie/laravel-activitylog package for audit logging on Tenant model
- **DEP-005**: Authentication system (Laravel Sanctum) must be configured for API authentication
- **DEP-006**: User model must exist before adding tenant_id foreign key
- **DEP-007**: Basic permission system must be in place for policy checks

## 5. Files

**New Files to Create:**
- **FILE-001**: database/migrations/YYYY_MM_DD_HHMMSS_create_tenants_table.php - Tenants table schema
- **FILE-002**: database/migrations/YYYY_MM_DD_HHMMSS_add_tenant_id_to_users_table.php - Add tenant relationship to users
- **FILE-003**: app/Domains/Core/Models/Tenant.php - Tenant Eloquent model
- **FILE-004**: app/Domains/Core/Enums/TenantStatus.php - Tenant status enum
- **FILE-005**: app/Domains/Core/Scopes/TenantScope.php - Global scope for tenant filtering
- **FILE-006**: app/Domains/Core/Traits/BelongsToTenant.php - Trait for tenant-aware models
- **FILE-007**: app/Domains/Core/Contracts/TenantManagerContract.php - Tenant manager interface
- **FILE-008**: app/Domains/Core/Services/TenantManager.php - Tenant manager service
- **FILE-009**: app/Domains/Core/Middleware/IdentifyTenant.php - Tenant identification middleware
- **FILE-010**: app/Domains/Core/Contracts/TenantRepositoryContract.php - Tenant repository interface
- **FILE-011**: app/Domains/Core/Repositories/TenantRepository.php - Tenant repository implementation
- **FILE-012**: app/Domains/Core/Actions/CreateTenantAction.php - Create tenant action
- **FILE-013**: app/Domains/Core/Actions/UpdateTenantAction.php - Update tenant action
- **FILE-014**: app/Domains/Core/Actions/ArchiveTenantAction.php - Archive tenant action
- **FILE-015**: app/Http/Controllers/Api/V1/TenantController.php - API controller
- **FILE-016**: app/Http/Resources/TenantResource.php - API resource
- **FILE-017**: app/Http/Requests/StoreTenantRequest.php - Store validation
- **FILE-018**: app/Http/Requests/UpdateTenantRequest.php - Update validation
- **FILE-019**: app/Console/Commands/Tenant/CreateTenantCommand.php - CLI create command
- **FILE-020**: app/Console/Commands/Tenant/ListTenantsCommand.php - CLI list command
- **FILE-021**: app/Domains/Core/Events/TenantCreatedEvent.php - Tenant created event
- **FILE-022**: app/Domains/Core/Events/TenantUpdatedEvent.php - Tenant updated event
- **FILE-023**: app/Domains/Core/Events/TenantArchivedEvent.php - Tenant archived event
- **FILE-024**: app/Domains/Core/Listeners/InitializeTenantDataListener.php - Initialize tenant data
- **FILE-025**: app/Domains/Core/Policies/TenantPolicy.php - Authorization policy
- **FILE-026**: app/Support/Helpers/tenant.php - Helper functions
- **FILE-027**: database/factories/TenantFactory.php - Factory for testing

**Files to Modify:**
- **FILE-028**: app/Http/Kernel.php - Register IdentifyTenant middleware
- **FILE-029**: app/Providers/EventServiceProvider.php - Register events and listeners
- **FILE-030**: app/Providers/AuthServiceProvider.php - Register TenantPolicy
- **FILE-031**: routes/api.php - Define tenant API routes
- **FILE-032**: composer.json - Ensure required packages are listed

**Test Files:**
- **FILE-033**: tests/Feature/Core/TenantTest.php - Feature tests
- **FILE-034**: tests/Unit/Core/TenantScopeTest.php - Scope unit tests
- **FILE-035**: tests/Unit/Core/TenantManagerTest.php - Manager unit tests

## 6. Testing

**Unit Tests:**
- **TEST-001**: Test TenantScope applies WHERE clause correctly
- **TEST-002**: Test BelongsToTenant trait sets tenant_id on model creation
- **TEST-003**: Test withoutTenantScope() removes scope from query
- **TEST-004**: Test TenantManager setActive() stores tenant in context
- **TEST-005**: Test TenantManager current() retrieves active tenant
- **TEST-006**: Test TenantManager impersonate() switches tenant context
- **TEST-007**: Test tenant() helper function returns current tenant
- **TEST-008**: Test CreateTenantAction validates unique domain constraint
- **TEST-009**: Test CreateTenantAction dispatches TenantCreatedEvent
- **TEST-010**: Test TenantRepository create() method

**Feature Tests:**
- **TEST-011**: Test POST /api/v1/tenants creates tenant with valid data
- **TEST-012**: Test POST /api/v1/tenants fails with duplicate domain (422 response)
- **TEST-013**: Test GET /api/v1/tenants returns paginated tenant list
- **TEST-014**: Test GET /api/v1/tenants/{id} returns single tenant
- **TEST-015**: Test PATCH /api/v1/tenants/{id} updates tenant
- **TEST-016**: Test DELETE /api/v1/tenants/{id} archives tenant (soft delete)
- **TEST-017**: Test tenant isolation: User A cannot access Tenant B data
- **TEST-018**: Test IdentifyTenant middleware sets tenant context from user
- **TEST-019**: Test unauthorized user cannot access tenant endpoints (403)
- **TEST-020**: Test CLI command php artisan erp:tenant:create works

**Integration Tests:**
- **TEST-021**: Test multi-tenant query filtering across related models
- **TEST-022**: Test tenant creation triggers listener to initialize data
- **TEST-023**: Test impersonation logs activity correctly
- **TEST-024**: Test tenant switching in single request lifecycle

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Performance degradation with WHERE tenant_id clause on every query - Mitigation: Proper indexing on tenant_id columns, query optimization, database connection pooling
- **RISK-002**: Tenant context loss in async jobs/queues - Mitigation: Serialize tenant_id with job payload, restore context in job handler
- **RISK-003**: Global scope bypass vulnerability allowing cross-tenant access - Mitigation: Comprehensive testing, code reviews, policy enforcement at application level
- **RISK-004**: Tenant impersonation abuse - Mitigation: Strict permission checks, comprehensive audit logging, automatic session timeout
- **RISK-005**: Migration complexity when adding tenant_id to existing tables - Mitigation: Careful migration planning, backfill strategies, database backup before migrations

**Assumptions:**
- **ASSUMPTION-001**: All domain models will adopt BelongsToTenant trait consistently
- **ASSUMPTION-002**: Tenant count will remain under 10,000 for initial phase
- **ASSUMPTION-003**: All API requests will be authenticated (no public endpoints requiring tenant context)
- **ASSUMPTION-004**: Database supports UUID natively or can cast efficiently
- **ASSUMPTION-005**: Single active tenant per request lifecycle is sufficient
- **ASSUMPTION-006**: Tenant domain names will be unique and validated during creation
- **ASSUMPTION-007**: Soft deletes are acceptable for tenant archival (no hard delete requirement)

## 8. Related Specifications / Further Reading

- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Overall Phase 1 requirements
- [Laravel Multi-Tenancy Best Practices](https://laravel.com/docs/12.x/eloquent#global-scopes)
- [Laravel Global Scopes Documentation](https://laravel.com/docs/12.x/eloquent#global-scopes)
- [Multi-Tenant Data Architecture](https://docs.microsoft.com/en-us/azure/architecture/patterns/multi-tenancy)
- [PRD-02-infrastructure-auth-1.md](./PRD-02-infrastructure-auth-1.md) - Authentication system (depends on tenant system)
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Module development guidelines
