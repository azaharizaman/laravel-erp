---
plan: PRD01-SUB23-PLAN02 - API Documentation & Sandbox
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Laravel ERP Development Team
status: Planned
tags: [feature, api-gateway, documentation, swagger, openapi, sandbox, testing]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers **API Documentation & Sandbox Environment** for the API Gateway & Documentation module (PRD01-SUB23). It implements interactive API documentation with Swagger/OpenAPI, API sandbox environment for testing without affecting production data, and deprecated endpoint management with backward compatibility warnings.

## 1. Requirements & Constraints

**Requirements Addressed:**
- **FR-API-003**: Generate interactive API documentation with Swagger/OpenAPI
- **FR-API-004**: Provide API sandbox environment for testing without affecting production
- **BR-API-001**: API versions must be backward compatible for at least 12 months
- **BR-API-002**: Deprecated endpoints must show warnings 3 months before removal
- **ARCH-API-002**: Implement API versioning via URL path (/api/v1/, /api/v2/)

**Security Constraints:**
- **SEC-004**: Sandbox environments isolated from production database
- **SEC-005**: Sandbox API keys only work in sandbox environment
- **SEC-006**: Sandbox data auto-resets or can be manually reset

**Performance Constraints:**
- **CON-004**: OpenAPI documentation generation must complete in < 5 seconds
- **CON-005**: Sandbox environment creation must complete in < 30 seconds
- **CON-006**: Sandbox auto-reset must not interfere with ongoing requests

**Guidelines:**
- **GUD-005**: Use Swagger/OpenAPI 3.0 specification format
- **GUD-006**: Document all request/response schemas in OpenAPI
- **GUD-007**: Provide example requests and responses for each endpoint
- **GUD-008**: Auto-generate documentation from code annotations

**Patterns:**
- **PAT-005**: Use Builder pattern for sandbox environment setup
- **PAT-006**: Apply Template Method for sandbox data reset
- **PAT-007**: Use Strategy pattern for deprecated endpoint warnings

## 2. Implementation Steps

### GOAL-001: Implement OpenAPI Documentation Generation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-API-003 | Interactive API documentation with Swagger/OpenAPI | | |
| GUD-005, GUD-006, GUD-007, GUD-008 | OpenAPI 3.0 specification implementation | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Install `darkaonline/l5-swagger` package via Composer for Swagger UI integration | | |
| TASK-002 | Create OpenAPI config file `config/l5-swagger.php` with routes, host, basePath settings per API version | | |
| TASK-003 | Create base OpenAPI specification file `packages/api-gateway/docs/openapi.yaml` with info, servers, components, security schemes | | |
| TASK-004 | Create OpenAPI generator service in `packages/api-gateway/src/Services/OpenApiGeneratorService.php` with method `generate(): array` | | |
| TASK-005 | Implement OpenAPI endpoint discovery scanning all routes with `api.version` middleware to extract version, path, method | | |
| TASK-006 | Create OpenAPI request schema generator extracting validation rules from form requests and generating JSON schema | | |
| TASK-007 | Create OpenAPI response schema generator from API resources and model relationships generating JSON schema | | |
| TASK-008 | Create API controller DocBlock annotation handler extracting @OA annotations for operation summary, description, tags, examples | | |
| TASK-009 | Implement OpenAPI tags generation grouping endpoints by module: companies, inventory, sales, purchases, accounting | | |
| TASK-010 | Create Swagger UI controller in `packages/api-gateway/src/Http/Controllers/SwaggerController.php` with routes GET /api/docs and GET /api/docs/openapi.json | | |
| TASK-011 | Add OpenAPI cache in Redis with TTL 86400s (24 hours) for performance optimization | | |
| TASK-012 | Create Artisan command `php artisan api:generate-docs` to manually generate and cache OpenAPI specification | | |
| TASK-013 | Create listener to invalidate OpenAPI cache when routes or models change via event notifications | | |
| TASK-014 | Add OpenAPI download endpoints: GET /api/docs/openapi.json (JSON), GET /api/docs/openapi.yaml (YAML) | | |
| TASK-015 | Write feature tests verifying OpenAPI spec validation, endpoint discovery, schema generation, response format | | |

### GOAL-002: Implement Deprecated Endpoint Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-API-001 | Backward compatibility for API versions (12 months) | | |
| BR-API-002 | Deprecated endpoint warnings 3 months before removal | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create deprecated endpoint model in `packages/api-gateway/src/Models/DeprecatedEndpoint.php` with columns: api_version, endpoint_path, http_method, deprecated_at, sunset_at, replacement_endpoint, deprecation_message | | |
| TASK-017 | Create deprecated endpoint repository contract in `packages/api-gateway/src/Contracts/DeprecatedEndpointRepositoryContract.php` with methods: findByPath, isDeprecated, getWarning, getSunsetDate | | |
| TASK-018 | Implement deprecated endpoint repository in `packages/api-gateway/src/Repositories/DeprecatedEndpointRepository.php` with caching (TTL 86400s) | | |
| TASK-019 | Create deprecated endpoint middleware in `app/Http/Middleware/CheckDeprecatedEndpoint.php` that checks if endpoint is deprecated and adds response headers: Deprecation: true, Sunset: <sunset_date>, Link: <replacement_endpoint> | | |
| TASK-020 | Add deprecation warning to API response body in JSON: warnings: [{type: 'deprecated', message: 'This endpoint is deprecated...', replacement: '...', sunset_date: '...'}] | | |
| TASK-021 | Create deprecation notification service in `packages/api-gateway/src/Services/DeprecationNotificationService.php` to alert API key owners 90 days before sunset | | |
| TASK-022 | Implement Artisan command `php artisan api:deprecate-endpoint` to mark endpoints as deprecated with sunset date 12 months from deprecation date | | |
| TASK-023 | Create listener for deprecation warnings that logs API key usage of deprecated endpoints for monitoring | | |
| TASK-024 | Add deprecated endpoints section to OpenAPI documentation showing replacement endpoints and timeline | | |
| TASK-025 | Write unit tests for deprecation detection, warning generation, and date calculation | | |

### GOAL-003: Implement API Sandbox Environment

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-API-004 | Provide API sandbox environment for testing | | |
| SEC-004, SEC-005, SEC-006 | Sandbox isolation and security | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-026 | Create sandbox environment model in `packages/api-gateway/src/Models/SandboxEnvironment.php` with columns: tenant_id, environment_name, database_name, is_active, auto_reset, reset_frequency, last_reset_at, created_by | | |
| TASK-027 | Create sandbox repository contract in `packages/api-gateway/src/Contracts/SandboxRepositoryContract.php` with methods: create, update, delete, reset, list | | |
| TASK-028 | Implement sandbox repository in `packages/api-gateway/src/Repositories/SandboxRepository.php` | | |
| TASK-029 | Create sandbox service in `packages/api-gateway/src/Services/SandboxService.php` with methods: createEnvironment, resetEnvironment, deleteEnvironment | | |
| TASK-030 | Implement sandbox database cloning: clone production database schema and seed with sample data to `sandbox_{tenant_id}_{environment_name}` | | |
| TASK-031 | Create sandbox data seeder in `packages/api-gateway/database/seeders/SandboxSeeder.php` with sample companies, inventory, customers, vendors, GL accounts | | |
| TASK-032 | Implement auto-reset functionality: hourly, daily, or weekly based on reset_frequency setting | | |
| TASK-033 | Create Artisan command `php artisan sandbox:create {tenant_id} {environment_name}` to create sandbox environment | | |
| TASK-034 | Create Artisan command `php artisan sandbox:reset {tenant_id} {environment_name}` to manually reset sandbox to seed state | | |
| TASK-035 | Create Artisan command `php artisan sandbox:delete {tenant_id} {environment_name}` to delete sandbox environment and cleanup database | | |
| TASK-036 | Add middleware `app/Http/Middleware/RouteSandboxRequests.php` to route API requests to appropriate database (sandbox or production) based on API key environment flag | | |
| TASK-037 | Create sandbox API key attribute: `sandbox_environment_id` linking API key to specific sandbox (NULL for production) | | |
| TASK-038 | Implement sandbox endpoint middleware validation: if API key is sandbox, only allow requests to sandbox environment | | |
| TASK-039 | Create sandbox controller in `packages/api-gateway/src/Http/Controllers/SandboxController.php` with endpoints: GET /api/v1/sandbox/environments, POST /api/v1/sandbox/environments, POST /api/v1/sandbox/environments/{id}/reset, DELETE /api/v1/sandbox/environments/{id} | | |
| TASK-040 | Add sandbox management to OpenAPI documentation with special endpoint section | | |
| TASK-041 | Write feature tests for sandbox creation, data isolation, reset functionality, API key routing | | |

### GOAL-004: Implement API Documentation Portal

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-API-003 | Interactive API documentation portal | | |
| GUD-007 | Example requests and responses | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-042 | Create Swagger UI custom theme in `packages/api-gateway/docs/swagger-ui-custom.css` with ERP branding | | |
| TASK-043 | Create documentation controller in `packages/api-gateway/src/Http/Controllers/DocumentationController.php` with routes: GET /docs (HTML), GET /docs/api (API reference) | | |
| TASK-044 | Create documentation view `packages/api-gateway/resources/views/documentation/index.blade.php` with Swagger UI embedded | | |
| TASK-045 | Add interactive endpoint explorer allowing users to make test requests directly from documentation | | |
| TASK-046 | Implement request/response example generation from factory data and actual API calls | | |
| TASK-047 | Create API reference guide in Markdown `packages/api-gateway/docs/API_REFERENCE.md` with all endpoints, parameters, responses | | |
| TASK-048 | Add authentication guide in `packages/api-gateway/docs/AUTHENTICATION.md` covering Sanctum, API keys, OAuth 2.0 flows | | |
| TASK-049 | Add quick start guide in `packages/api-gateway/docs/QUICKSTART.md` with example requests using curl, PHP, JavaScript | | |
| TASK-050 | Create webhook documentation in `packages/api-gateway/docs/WEBHOOKS.md` with event types, payload schemas, retry logic | | |
| TASK-051 | Write feature tests for documentation page accessibility, content completeness, code example validity | | |

### GOAL-005: Testing, Documentation & Deployment

| Requirements Addressed | Description | Completed | Date |
|---|---|---|---|
| FR-API-003, FR-API-004 | Testing coverage for documentation and sandbox | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Write unit tests for OpenAPI generator: endpoint discovery, schema generation, tag organization (8 tests) | | |
| TASK-053 | Write unit tests for deprecation service: date calculation, warning generation, notification logic (6 tests) | | |
| TASK-054 | Write unit tests for sandbox service: database cloning, reset logic, auto-reset scheduling (8 tests) | | |
| TASK-055 | Write feature tests for OpenAPI endpoints: /api/docs, /api/docs/openapi.json, /api/docs/openapi.yaml (5 tests) | | |
| TASK-056 | Write feature tests for deprecated endpoints: warning headers, response messages, replacement links (6 tests) | | |
| TASK-057 | Write feature tests for sandbox management: create, reset, delete, API key routing, isolation (10 tests) | | |
| TASK-058 | Write feature tests for documentation portal: page rendering, endpoint explorer, example generation (6 tests) | | |
| TASK-059 | Write integration tests for deprecated endpoint notifications: email alerts, API key owner notifications (4 tests) | | |
| TASK-060 | Achieve minimum 80% code coverage: run `./vendor/bin/pest --coverage` for documentation and sandbox modules | | |
| TASK-061 | Create setup guide in `packages/api-gateway/docs/SETUP.md`: installation, migrations, configuration, seeding sandbox | | |
| TASK-062 | Create troubleshooting guide in `packages/api-gateway/docs/TROUBLESHOOTING.md`: common issues, sandbox issues, documentation refresh | | |
| TASK-063 | Update main README.md with link to documentation portal and sandbox instructions | | |
| TASK-064 | Validate all acceptance criteria: OpenAPI spec validity, sandbox isolation, deprecation warnings functional | | |
| TASK-065 | Conduct code review: PSR-12 compliance via Pint, strict types, PHPDoc completeness, pattern adherence | | |
| TASK-066 | Run full test suite: `./vendor/bin/pest packages/api-gateway/tests/` verify all tests pass | | |

## 3. Alternatives

- **ALT-001**: Use Postman Collections instead of OpenAPI - rejected because OpenAPI provides better IDE support and auto-generation
- **ALT-002**: Use Docker for sandbox environments - possible but adds infrastructure complexity; database cloning simpler for MVP
- **ALT-003**: Manual deprecation tracking without automated warnings - rejected because fails to meet BR-API-002 requirement

## 4. Dependencies

- **DEP-001**: PLAN01 - API Gateway Foundation must be completed first (routes, authentication, API key management)
- **DEP-002**: darkaonline/l5-swagger package for Swagger UI
- **DEP-003**: PostgreSQL 14+ for database cloning and schema introspection
- **DEP-004**: Redis 6+ for OpenAPI and deprecation cache

## 5. Files

**Created/Modified Files:**

- **packages/api-gateway/src/Services/OpenApiGeneratorService.php**: OpenAPI specification generator
- **packages/api-gateway/src/Services/DeprecationNotificationService.php**: Deprecation notifications
- **packages/api-gateway/src/Services/SandboxService.php**: Sandbox environment management
- **packages/api-gateway/src/Models/DeprecatedEndpoint.php**: Deprecated endpoint model
- **packages/api-gateway/src/Models/SandboxEnvironment.php**: Sandbox environment model
- **packages/api-gateway/src/Repositories/DeprecatedEndpointRepository.php**: Deprecated endpoint data access
- **packages/api-gateway/src/Repositories/SandboxRepository.php**: Sandbox data access
- **packages/api-gateway/src/Http/Controllers/SwaggerController.php**: Swagger documentation controller
- **packages/api-gateway/src/Http/Controllers/SandboxController.php**: Sandbox management controller
- **packages/api-gateway/src/Http/Middleware/CheckDeprecatedEndpoint.php**: Deprecation warning middleware
- **packages/api-gateway/src/Http/Middleware/RouteSandboxRequests.php**: Sandbox request routing middleware
- **packages/api-gateway/database/seeders/SandboxSeeder.php**: Sandbox sample data seeder
- **packages/api-gateway/database/migrations/xxxx_create_deprecated_endpoints_table.php**: Migration
- **packages/api-gateway/database/migrations/xxxx_create_sandbox_environments_table.php**: Migration
- **packages/api-gateway/docs/openapi.yaml**: OpenAPI 3.0 specification
- **packages/api-gateway/docs/API_REFERENCE.md**: API reference documentation
- **packages/api-gateway/docs/AUTHENTICATION.md**: Authentication guide
- **packages/api-gateway/docs/QUICKSTART.md**: Quick start guide
- **packages/api-gateway/docs/WEBHOOKS.md**: Webhook documentation
- **packages/api-gateway/docs/SETUP.md**: Setup guide
- **packages/api-gateway/docs/TROUBLESHOOTING.md**: Troubleshooting guide
- **packages/api-gateway/tests/Unit/Services/OpenApiGeneratorServiceTest.php**: Unit tests
- **packages/api-gateway/tests/Unit/Services/DeprecationServiceTest.php**: Unit tests
- **packages/api-gateway/tests/Unit/Services/SandboxServiceTest.php**: Unit tests
- **packages/api-gateway/tests/Feature/OpenApiEndpointsTest.php**: Feature tests
- **packages/api-gateway/tests/Feature/DeprecatedEndpointsTest.php**: Feature tests
- **packages/api-gateway/tests/Feature/SandboxManagementTest.php**: Feature tests
- **packages/api-gateway/tests/Feature/DocumentationPortalTest.php**: Feature tests
- **packages/api-gateway/resources/views/documentation/index.blade.php**: Swagger UI view

## 6. Testing

**Unit Tests (22 tests):**
- OpenAPI generator: endpoint discovery, schema generation, tag organization
- Deprecation service: date calculation, warning generation, notification
- Sandbox service: database cloning, reset scheduling, auto-reset

**Feature Tests (27 tests):**
- OpenAPI endpoints: documentation access, spec download, format validation
- Deprecated endpoints: header generation, response messages, replacement links
- Sandbox management: creation, reset, deletion, isolation, API key routing
- Documentation portal: rendering, example generation, code validity

**Integration Tests (4 tests):**
- Deprecated endpoint notifications: email alerts, API key owner notifications
- Sandbox auto-reset: scheduling, data verification, concurrent request handling

**Performance Tests (implicit in feature tests):**
- OpenAPI generation: < 5 seconds (CON-004)
- Sandbox creation: < 30 seconds (CON-005)
- Documentation portal rendering: < 2 seconds

**Total: 53 tests** with minimum 80% code coverage

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Sandbox database cloning performance at scale with large production database - Mitigation: use PostgreSQL pg_dump with compression, implement incremental seeding
- **RISK-002**: OpenAPI cache invalidation complexity - Mitigation: implement listener-based cache invalidation on route/model changes
- **RISK-003**: Sandbox isolation breach: API key accessing wrong environment - Mitigation: add middleware validation, database-level checks, logging

**Assumptions:**
- **ASSUMPTION-001**: All endpoints documented via OpenAPI annotations or form request validation
- **ASSUMPTION-002**: PostgreSQL available for sandbox database cloning
- **ASSUMPTION-003**: Developers maintain deprecated endpoint notices in code
- **ASSUMPTION-004**: Email service configured for deprecation notifications

## 8. KIV for future implementations

- **KIV-001**: API versioning with header-based versioning (Accept-Version header) as alternative to URL-based
- **KIV-002**: GraphQL API documentation in GraphQL schema format (defer to PLAN03)
- **KIV-003**: Multi-language API documentation (German, French, Spanish)
- **KIV-004**: Sandbox data templates for different use cases (e.g., "full ERP", "simple inventory")
- **KIV-005**: OpenAPI generator for async/event-driven endpoints

## 9. Related PRD / Further Reading

- **PRD01-SUB23**: [API Gateway & Documentation](../prd/prd-01/PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md)
- **PRD01-SUB01**: [Multi-Tenancy](../prd/prd-01/PRD01-SUB01-MULTITENANCY.md) - Tenant isolation
- **PRD01-SUB02**: [Authentication & Authorization](../prd/prd-01/PRD01-SUB02-AUTHENTICATION.md) - API key security
- **OpenAPI 3.0 Specification**: https://spec.openapis.org/oas/v3.0.3
- **Swagger UI Documentation**: https://github.com/swagger-api/swagger-ui
- **L5-Swagger Documentation**: https://github.com/DarkaOnLine/L5-Swagger

---

**Implementation Status:** Ready for development

**Estimated Effort:** 3-4 weeks (1 developer)

**Previous Plan:** PRD01-SUB23-PLAN01 (API Gateway Foundation)

**Next Plan:** PRD01-SUB23-PLAN03 (Rate Limiting & Analytics)
