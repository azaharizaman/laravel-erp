---
plan: Implement Financial Reporting API and Multi-Period Comparison
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, financial-reporting, api, multi-period, comparison, variance-analysis, drill-down]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan builds upon the Financial Reporting foundation to add RESTful API endpoints, multi-period comparative reporting with variance analysis, and drill-down capability from summary to transaction detail. This enables consumption of financial reports through APIs for custom frontends, AI agents, and third-party integrations.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-FR-002**: Support multi-period comparative reports with variance analysis
- **REQ-FR-FR-003**: Provide drill-down capability from summary to transaction detail
- **REQ-DR-FR-002**: Maintain report execution history with parameters and generated snapshots
- **REQ-IR-FR-001**: Integrate with General Ledger as primary data source
- **REQ-SR-FR-001**: Implement role-based access to financial reports by sensitivity level
- **REQ-SR-FR-002**: Watermark and log access to confidential financial reports
- **REQ-PR-FR-001**: Dashboard queries must return in < 3 seconds for datasets with < 10k rows
- **REQ-PR-FR-002**: Financial statement generation must complete in < 5 seconds for monthly period
- **REQ-ARCH-FR-001**: Use SQL for report data retrieval with optimized queries
- **REQ-API-001**: All report endpoints must follow RESTful conventions with /api/v1/financial-reports prefix
- **REQ-API-002**: Support JSON:API specification for response format
- **REQ-API-003**: Implement pagination for large result sets (default 50, max 200 per page)

### Security Constraints

- **SEC-001**: Report API endpoints must require authentication via Laravel Sanctum
- **SEC-002**: Financial reports must enforce role-based permissions (view-financial-reports, view-sensitive-reports)
- **SEC-003**: Drill-down to transaction detail requires additional permission (view-transaction-detail)
- **SEC-004**: All report access must be logged for compliance auditing

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ API Resources for response transformation
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use Form Requests for input validation
- **GUD-005**: All controller methods must have PHPDoc blocks

### Patterns to Follow

- **PAT-001**: Use Laravel Actions for report generation operations (invokable as API, Job, Command)
- **PAT-002**: Use API Resources for consistent JSON response formatting
- **PAT-003**: Use Policy pattern for authorization checks
- **PAT-004**: Use Query Builder pattern for drill-down filtering
- **PAT-005**: Use Cache-Aside pattern for frequently accessed reports

### Constraints

- **CON-001**: API responses must return within 5 seconds for report generation
- **CON-002**: Multi-period comparison limited to 12 periods maximum
- **CON-003**: Drill-down results must be paginated (max 200 transactions per page)
- **CON-004**: All endpoints must support tenant isolation via middleware
- **CON-005**: Report execution history must be retained for 24 months

## 2. Implementation Steps

### GOAL-001: RESTful API Controllers and Routes

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| API-001, API-002, API-003, SR-FR-001 | Create RESTful API controllers for financial report CRUD operations with proper routing, authentication, authorization, and pagination. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `src/Http/Controllers/Api/V1/ReportDefinitionController.php`: Include `declare(strict_types=1);`. Implement methods: `index(IndexReportDefinitionsRequest $request): JsonResponse` (list report definitions with filtering by type, category, is_standard), `show(int $id): JsonResponse` (get single definition), `store(StoreReportDefinitionRequest $request): JsonResponse` (create custom report), `update(UpdateReportDefinitionRequest $request, int $id): JsonResponse` (update definition), `destroy(int $id): JsonResponse` (soft delete). Apply `auth:sanctum` and `tenant` middleware. Use ReportDefinitionResource for responses. | | |
| TASK-002 | Create `src/Http/Controllers/Api/V1/ReportExecutionController.php`: Include `declare(strict_types=1);`. Implement methods: `generate(GenerateReportRequest $request): JsonResponse` (POST /api/v1/financial-reports/generate - generates report and returns data), `show(int $executionId): JsonResponse` (GET /api/v1/financial-reports/executions/{id} - retrieves historical execution), `index(Request $request): JsonResponse` (GET /api/v1/financial-reports/executions - lists execution history with filters), `download(int $executionId, string $format): Response` (GET /api/v1/financial-reports/executions/{id}/download?format=pdf|excel|csv - exports report). Apply auth and tenant middleware. | | |
| TASK-003 | Register API routes in `routes/api.php`: Define route group with prefix 'api/v1/financial-reports', middleware ['auth:sanctum', 'tenant'], name prefix 'api.v1.financial-reports'. Routes: GET /definitions (index), POST /definitions (store), GET /definitions/{id} (show), PATCH /definitions/{id} (update), DELETE /definitions/{id} (destroy), POST /generate (generate report), GET /executions (execution history), GET /executions/{id} (show execution), GET /executions/{id}/download (download report). Apply middleware 'can:view-financial-reports' on all routes, 'can:manage-report-definitions' on CRUD routes. | | |
| TASK-004 | Create Form Request `src/Http/Requests/GenerateReportRequest.php`: Validation rules: report_definition_id (required, exists:report_definitions,id), period_id (required_without:date_range, exists:fiscal_periods,id), date_range (required_without:period_id, array with from/to dates), comparison_periods (nullable, array, max:12, each item is period_id or date_range), filters (nullable, array with account_codes, cost_centers, departments), options (nullable, array with include_drill_down boolean, export_format string). Authorization method checks 'view-financial-reports' permission. Apply tenant_id validation to ensure report_definition belongs to current tenant. | | |
| TASK-005 | Create Form Requests: `src/Http/Requests/StoreReportDefinitionRequest.php` (validation: code required|unique per tenant|max:50, name required|max:255, type required|in:balance_sheet,income_statement,cash_flow,custom, field_mappings required|json, formulas nullable|json, filters nullable|json), `UpdateReportDefinitionRequest.php` (same validation, all optional), `IndexReportDefinitionsRequest.php` (validation: type nullable|in:enum, category nullable|string, is_standard nullable|boolean, per_page nullable|integer|min:1|max:200). Authorization checks 'manage-report-definitions' permission. | | |

### GOAL-002: API Resources and Response Formatting

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| API-002, DR-FR-002 | Create API Resources for consistent JSON:API response formatting with proper data transformation and metadata inclusion. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-006 | Create `src/Http/Resources/ReportDefinitionResource.php`: Include `declare(strict_types=1);`. Transform ReportDefinition model to JSON:API format in `toArray()` method: Return array with keys: id, type ('report_definition'), attributes (code, name, type, category, field_mappings, formulas, filters, sorting, is_standard, is_active, created_at, updated_at), relationships (creator with user data if loaded), links (self, executions). Conditionally hide field_mappings and formulas if user lacks 'manage-report-definitions' permission. Add meta with execution_count if available. | | |
| TASK-007 | Create `src/Http/Resources/ReportExecutionResource.php`: Transform ReportExecutionHistory model to JSON:API format: Return array with keys: id, type ('report_execution'), attributes (report_definition_id, report_name, executed_by_id, executed_by_name, parameters, execution_time_ms, row_count, status, executed_at, created_at), relationships (report_definition if loaded, executed_by user if loaded), links (self, report_definition, download with formats [pdf, excel, csv]). Conditionally include result_snapshot only if user has 'view-execution-snapshots' permission. Add meta with performance metrics. | | |
| TASK-008 | Create `src/Http/Resources/ReportDataResource.php`: Transform generated report data array to structured JSON response: Return array with keys: report_definition (code, name, type), parameters (period, date_range, filters), execution_metadata (execution_id, executed_at, execution_time_ms, is_balanced for financial statements), sections (array of report sections with nested line items), comparison_periods (array if multi-period), drill_down_available (boolean), links (execution_history, drill_down endpoint if applicable). Format numbers with proper decimal places and thousand separators per config. Include currency information. | | |
| TASK-009 | Create `src/Http/Resources/ReportExecutionCollection.php`: Extend ResourceCollection for paginated execution history: In `toArray()` return JSON:API collection format with data array, meta (current_page, per_page, total, last_page), links (first, last, prev, next, self). Add collection-level meta: total_executions, average_execution_time_ms, success_rate (percentage of completed vs failed). Support filtering and sorting via query parameters. | | |
| TASK-010 | Create `src/Http/Resources/DrillDownResource.php`: Transform drill-down transaction detail to JSON response: Return array with keys: report_line (label, balance), account (code, name, type, balance), transactions (paginated array of GL transactions with posting_id, date, reference, description, debit, credit, balance, cost_center, department), pagination (current_page, per_page, total, links), filters_applied (account_codes, date_range). Include breadcrumb trail showing path from financial statement section to specific account. | | |

### GOAL-003: Multi-Period Comparison and Variance Analysis

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-002, PR-FR-001 | Implement multi-period comparative reporting with variance analysis (absolute and percentage changes) across up to 12 periods. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Create `src/Services/MultiPeriodComparisonService.php` implementing `MultiPeriodComparisonServiceContract`: Include `declare(strict_types=1);`. Inject `ReportGeneratorServiceContract`, `FiscalPeriodRepositoryContract`. Implement method `generateComparison(ReportDefinition $definition, int $basePeriodId, array $comparisonPeriodIds): array`. Validate max 12 comparison periods. For each period, call `ReportGeneratorService::generate()` to get report data. Store results keyed by period. Calculate variance for each report line: absolute_variance (current - prior), percentage_variance ((current - prior) / prior * 100). Return array with base_period data, comparison_periods array, variance_analysis array. Use parallel execution for period data retrieval if supported. | | |
| TASK-012 | Implement variance calculation in `MultiPeriodComparisonService`: Create private method `calculateVariance(array $baseData, array $comparisonData): array`. For each report line in baseData, find matching line in comparisonData by line key. Calculate absolute_variance = baseData['balance'] - comparisonData['balance']. Calculate percentage_variance = (absolute_variance / comparisonData['balance']) * 100 with division by zero protection (return null if denominator is 0). Determine variance_direction: 'favorable', 'unfavorable', 'neutral' based on account type and sign (e.g., revenue increase is favorable, expense increase is unfavorable). Return array with variance metrics per report line. | | |
| TASK-013 | Extend `ReportExecutionController::generate()` to support multi-period comparison: Check if request includes comparison_periods parameter. If present, call `MultiPeriodComparisonService::generateComparison()` instead of single-period generation. Transform comparison result using `MultiPeriodComparisonResource`. Record execution history with all period parameters. Apply caching with cache key including all period IDs and filters. Ensure execution completes within 5 seconds (PR-FR-002) by using pre-aggregated data from financial_aggregates table. | | |
| TASK-014 | Create `src/Http/Resources/MultiPeriodComparisonResource.php`: Transform multi-period comparison data to JSON response: Return array with keys: report_definition (code, name, type), base_period (id, name, date_from, date_to, data), comparison_periods (array of period objects with same structure), variance_analysis (array of report lines with variance metrics: absolute_variance, percentage_variance, variance_direction, trend [increasing, decreasing, stable]), chart_data (formatted for frontend charting libraries with period labels and values), execution_metadata. Format numbers consistently across all periods. | | |
| TASK-015 | Create `src/Contracts/MultiPeriodComparisonServiceContract.php` interface: Define methods: `generateComparison(ReportDefinition $definition, int $basePeriodId, array $comparisonPeriodIds): array`, `calculateVariance(array $baseData, array $comparisonData): array`, `determineTrend(array $periodData): string`, `formatForChart(array $comparisonData): array`. All methods with full PHPDoc including @param and @return types. Interface enables mocking for testing and future alternative implementations. | | |

### GOAL-004: Drill-Down Capability to Transaction Detail

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-003, SR-FR-003, CON-003 | Implement drill-down from financial statement summary to transaction detail with proper filtering, pagination, and authorization. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-016 | Create `src/Http/Controllers/Api/V1/DrillDownController.php`: Include `declare(strict_types=1);`. Implement method `show(DrillDownRequest $request): JsonResponse`. Accept parameters: execution_id (report execution to drill from), report_line (specific line to drill into), account_code (optional, specific account), date_from/date_to (filter transactions by date), filters (cost_center, department, reference). Validate execution_id exists and belongs to current tenant. Check permission 'view-transaction-detail'. Call `DrillDownService::getTransactionDetail()` to retrieve GL transactions. Apply pagination (max 200 per page). Return DrillDownResource. | | |
| TASK-017 | Create `src/Services/DrillDownService.php` implementing `DrillDownServiceContract`: Inject `GeneralLedgerRepositoryContract`, `ReportExecutionRepositoryContract`. Implement method `getTransactionDetail(int $executionId, string $reportLine, array $filters = []): Collection`. Retrieve execution record to get report_definition and parameters. From report_definition field_mappings, extract account_codes mapped to specified report_line. Query GL transactions via `GeneralLedgerRepositoryContract::getTransactions()` with account_codes, date range from execution parameters, additional filters. Order by posting_date, posting_id. Return Collection of transaction records with debit, credit, running balance. | | |
| TASK-018 | Implement transaction filtering in `DrillDownService`: Support filters: account_code (single or array), cost_center, department, reference (partial match), amount_min, amount_max, transaction_type (debit, credit, both). Build query dynamically using Laravel Query Builder. Apply tenant_id filter automatically. Use indexes on gl_postings table (tenant_id, posting_date, account_id) for performance. Implement running balance calculation: track cumulative balance as transactions are retrieved, include in response. Handle large result sets with cursor pagination for memory efficiency. | | |
| TASK-019 | Register drill-down route in `routes/api.php`: Add route GET /api/v1/financial-reports/drill-down with middleware ['auth:sanctum', 'tenant', 'can:view-transaction-detail']. Route to DrillDownController::show(). Accept query parameters: execution_id, report_line, account_code, date_from, date_to, cost_center, department, page, per_page. Return DrillDownResource with paginated transactions and filter metadata. | | |
| TASK-020 | Create Form Request `src/Http/Requests/DrillDownRequest.php`: Validation rules: execution_id (required, exists:report_execution_history,id), report_line (required, string, max:255), account_code (nullable, string, exists:gl_accounts,code), date_from (nullable, date, before_or_equal:date_to), date_to (nullable, date, after_or_equal:date_from), cost_center (nullable, exists:cost_centers,code), department (nullable, exists:departments,code), page (nullable, integer, min:1), per_page (nullable, integer, min:1, max:200). Authorization checks 'view-transaction-detail' permission and validates execution belongs to current tenant. | | |

### GOAL-005: Report Access Logging and Performance Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| SR-FR-002, SEC-004, PR-FR-001, PR-FR-002 | Implement comprehensive audit logging for report access and create performance tests to validate response time requirements. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create `src/Middleware/LogReportAccessMiddleware.php`: Middleware to log all financial report access. In `handle()` method, before passing request to next middleware, record: user_id, tenant_id, report_definition_id (if applicable), execution_id (if applicable), endpoint (request path), parameters (request all), ip_address, user_agent, accessed_at (timestamp). Store in `report_access_log` table (create migration). After request completes, update log record with response_status, response_time_ms. Apply middleware to all financial-reports routes. | | |
| TASK-022 | Create migration `database/migrations/create_report_access_log_table.php`: Define `report_access_log` table with columns: id (BIGSERIAL), tenant_id (indexed, NOT NULL), user_id (indexed, NOT NULL), report_definition_id (nullable, indexed), execution_id (nullable, indexed), endpoint (VARCHAR(255)), parameters (JSONB), ip_address (INET), user_agent (TEXT), accessed_at (TIMESTAMP, indexed), response_status (SMALLINT), response_time_ms (INTEGER), created_at. Add indexes on (tenant_id, accessed_at), (user_id, accessed_at), (report_definition_id, accessed_at). Partition by accessed_at month for data retention management. | | |
| TASK-023 | Implement watermarking for sensitive reports in `ReportDataResource`: Add method `shouldWatermark()` checking if report_definition has sensitivity_level='high' or user lacks 'view-unmarked-reports' permission. If watermarking required, add watermark object to response meta: {text: "CONFIDENTIAL - User: {username} - Date: {timestamp}", position: "footer"}. Frontend responsible for displaying watermark. Include access_logged: true in meta to inform user that access is recorded. Log watermarked report access with additional flag in report_access_log table. | | |
| TASK-024 | Create Feature test `tests/Feature/ReportApiTest.php`: Use Pest syntax. Test scenarios: 1) POST /api/v1/financial-reports/generate with valid parameters (expect 201, report data structure, execution_id), 2) GET /api/v1/financial-reports/executions (expect 200, paginated list), 3) GET /api/v1/financial-reports/executions/{id} (expect 200, execution detail), 4) POST /generate with comparison_periods (expect comparison data with variance analysis), 5) GET /drill-down (expect paginated transactions), 6) Unauthorized access without auth token (expect 401), 7) Access without permission (expect 403), 8) Cross-tenant access attempt (expect 404 or 403). Assert response structure, data types, required fields. Use factories for test data. | | |
| TASK-025 | Create Performance test `tests/Feature/ReportPerformanceTest.php`: Test scenarios: 1) Generate monthly Balance Sheet with 1000 accounts (assert completion < 5 seconds per PR-FR-002), 2) Generate Income Statement with 12-period comparison (assert completion < 5 seconds), 3) Drill-down query with 10,000 transactions (assert first page < 3 seconds per PR-FR-001), 4) Concurrent report generation (10 requests) (assert all complete within timeout), 5) Cache hit scenario (generate same report twice, assert second request < 1 second). Use `$this->travelTo()` for date simulation. Use database seeder to create large dataset. Measure execution time with `microtime()`. Mark tests with `@group performance` tag. | | |

## 3. Alternatives

- **ALT-001**: Use GraphQL instead of REST for report API
  - *Pros*: Flexible querying, reduce over-fetching, single endpoint
  - *Cons*: Increased complexity, less standardized, harder to cache
  - *Decision*: Not chosen for MVP - REST simpler and more widely adopted; GraphQL can be added later

- **ALT-002**: Store drill-down transactions in report execution snapshot
  - *Pros*: Faster drill-down retrieval, no need to query GL again
  - *Cons*: Massive storage requirements, stale data if GL corrected after report
  - *Decision*: Not chosen - Real-time drill-down ensures accuracy and reduces storage

- **ALT-003**: Use WebSockets for real-time report generation progress
  - *Pros*: Better UX for long-running reports, can show progress bar
  - *Cons*: Additional infrastructure, complexity, not suitable for API consumers
  - *Decision*: Deferred - HTTP long-polling sufficient for MVP; consider for future enhancement

- **ALT-004**: Pre-generate all standard reports for all periods nightly
  - *Pros*: Instant report retrieval, no generation wait time
  - *Cons*: Storage overhead, inflexibility for ad-hoc filtering, resource intensive
  - *Decision*: Not chosen - On-demand generation with caching provides better balance

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-financial-reporting` (PLAN01) - Foundation layer required
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Tenant context and middleware
- `azaharizaman/erp-authentication` (PRD01-SUB02) - Authentication and authorization
- `azaharizaman/erp-general-ledger` (PRD01-SUB08) - Transaction data for drill-down
- `azaharizaman/erp-audit-logging` (PRD01-SUB03) - Optional for enhanced audit trail

**Internal Dependencies:**
- PLAN01 foundation must be complete (ReportDefinition, ReportGeneratorService, repositories)
- General Ledger module for transaction queries
- Fiscal Period management for multi-period comparison

**Infrastructure Dependencies:**
- Laravel Sanctum for API authentication
- Redis for report caching (recommended)
- Database partitioning for report_access_log table (for data retention)

## 5. Files

**Controllers:**
- `packages/financial-reporting/src/Http/Controllers/Api/V1/ReportDefinitionController.php` - CRUD for report definitions
- `packages/financial-reporting/src/Http/Controllers/Api/V1/ReportExecutionController.php` - Report generation and history
- `packages/financial-reporting/src/Http/Controllers/Api/V1/DrillDownController.php` - Transaction drill-down

**Form Requests:**
- `packages/financial-reporting/src/Http/Requests/GenerateReportRequest.php` - Report generation validation
- `packages/financial-reporting/src/Http/Requests/StoreReportDefinitionRequest.php` - Create definition validation
- `packages/financial-reporting/src/Http/Requests/UpdateReportDefinitionRequest.php` - Update definition validation
- `packages/financial-reporting/src/Http/Requests/IndexReportDefinitionsRequest.php` - List definitions validation
- `packages/financial-reporting/src/Http/Requests/DrillDownRequest.php` - Drill-down validation

**API Resources:**
- `packages/financial-reporting/src/Http/Resources/ReportDefinitionResource.php` - Report definition transformation
- `packages/financial-reporting/src/Http/Resources/ReportExecutionResource.php` - Execution history transformation
- `packages/financial-reporting/src/Http/Resources/ReportDataResource.php` - Report data transformation
- `packages/financial-reporting/src/Http/Resources/ReportExecutionCollection.php` - Paginated executions
- `packages/financial-reporting/src/Http/Resources/DrillDownResource.php` - Transaction detail transformation
- `packages/financial-reporting/src/Http/Resources/MultiPeriodComparisonResource.php` - Comparison data transformation

**Services:**
- `packages/financial-reporting/src/Services/MultiPeriodComparisonService.php` - Multi-period comparison logic
- `packages/financial-reporting/src/Services/DrillDownService.php` - Transaction drill-down logic

**Contracts:**
- `packages/financial-reporting/src/Contracts/MultiPeriodComparisonServiceContract.php` - Comparison service interface
- `packages/financial-reporting/src/Contracts/DrillDownServiceContract.php` - Drill-down service interface

**Middleware:**
- `packages/financial-reporting/src/Middleware/LogReportAccessMiddleware.php` - Report access logging

**Migrations:**
- `packages/financial-reporting/database/migrations/create_report_access_log_table.php` - Access log schema

**Routes:**
- `packages/financial-reporting/routes/api.php` - API route definitions (updated)

**Tests:**
- `packages/financial-reporting/tests/Feature/ReportApiTest.php` - API endpoint tests
- `packages/financial-reporting/tests/Feature/ReportPerformanceTest.php` - Performance validation tests

## 6. Testing

- **TEST-001**: POST /api/v1/financial-reports/generate with valid period_id, verify 201 status, report data structure, execution_id returned
- **TEST-002**: POST /generate with comparison_periods (3 periods), verify comparison data with variance_analysis, percentage changes calculated
- **TEST-003**: GET /api/v1/financial-reports/executions with pagination, verify correct page size, links, meta information
- **TEST-004**: GET /api/v1/financial-reports/executions/{id}, verify execution detail with parameters, result_snapshot, execution_time
- **TEST-005**: GET /api/v1/financial-reports/drill-down with execution_id and report_line, verify paginated GL transactions
- **TEST-006**: Unauthorized API access (no token), expect 401 Unauthorized
- **TEST-007**: API access without 'view-financial-reports' permission, expect 403 Forbidden
- **TEST-008**: Cross-tenant report access attempt, verify blocked with 403 or 404
- **TEST-009**: Performance test: Generate Balance Sheet with 1000 accounts, assert < 5 seconds (PR-FR-002)
- **TEST-010**: Performance test: Drill-down with 10,000 transactions, assert first page < 3 seconds (PR-FR-001)
- **TEST-011**: Verify report access logged in report_access_log table with correct user, tenant, timestamp
- **TEST-012**: Verify watermark metadata included for sensitive reports

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: API response time exceeds 5 seconds for complex multi-period comparisons
  - *Mitigation*: Use financial_aggregates table for pre-aggregated data, implement aggressive caching, optimize SQL queries
- **RISK-002**: Drill-down queries cause database performance issues with large transaction volumes
  - *Mitigation*: Mandatory pagination (max 200), database indexing, query optimization, consider read replicas
- **RISK-003**: report_access_log table grows too large affecting performance
  - *Mitigation*: Implement table partitioning by month, automated archiving of old logs, retention policy (24 months)
- **RISK-004**: Concurrent report generation exhausts server resources
  - *Mitigation*: Implement queue-based generation for long-running reports, rate limiting, resource monitoring

**Assumptions:**
- **ASSUMPTION-001**: Users access reports through custom frontends that can consume JSON:API format
- **ASSUMPTION-002**: Financial data pre-aggregated in financial_aggregates table for acceptable performance
- **ASSUMPTION-003**: Users understand variance analysis concepts (absolute variance, percentage variance)
- **ASSUMPTION-004**: GL transaction data is indexed properly for drill-down queries (account_id, posting_date)
- **ASSUMPTION-005**: Redis or equivalent caching layer is available for report result caching

## 8. KIV for future implementations

- **KIV-001**: Add WebSocket support for real-time report generation progress updates
- **KIV-002**: Implement report export to PDF and Excel formats (currently returns JSON only)
- **KIV-003**: Add support for custom calculated fields in drill-down (user-defined formulas on transaction data)
- **KIV-004**: Implement report annotations and comments (collaborative features)
- **KIV-005**: Add GraphQL API as alternative to REST for flexible querying
- **KIV-006**: Implement report subscriptions (automatically regenerate and deliver reports on schedule)
- **KIV-007**: Add AI-powered variance explanation (automatic analysis of significant variances)
- **KIV-008**: Implement drill-up capability (navigate from transaction back to summary)

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md](../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md)
- Related PLAN: [PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md](PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md) - Foundation layer
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md](../prd/prd-01/PRD01-SUB08-GENERAL-LEDGER.md) - GL transaction data
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- GitHub Copilot Instructions: [../../.github/copilot-instructions.md](../../.github/copilot-instructions.md)
- Laravel API Resources: https://laravel.com/docs/eloquent-resources
- JSON:API Specification: https://jsonapi.org/
