---
plan: Implement Custom Reports and Dashboards
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, financial-reporting, custom-reports, report-builder, dashboards, kpi, real-time]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan adds custom report builder capabilities and real-time dashboard functionality to the Financial Reporting module. It enables users to create custom reports with drag-and-drop field selection, save and share report templates, and view real-time financial KPIs with trend charts for data-driven decision making.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-FR-004**: Support custom report builder with drag-and-drop field selection
- **REQ-FR-FR-007**: Provide real-time dashboards with KPIs and trend charts
- **REQ-DR-FR-001**: Store report definitions with field mappings and formulas
- **REQ-DR-FR-003**: Cache aggregated financial data for faster report generation
- **REQ-PR-FR-001**: Dashboard queries must return in < 3 seconds for datasets with < 10k rows
- **REQ-SR-FR-001**: Implement role-based access to financial reports by sensitivity level
- **REQ-ARCH-FR-001**: Use SQL for report data retrieval with optimized queries
- **REQ-ARCH-FR-002**: Implement materialized views for pre-aggregated financial data

### Security Constraints

- **SEC-001**: Custom report definitions must enforce tenant isolation
- **SEC-002**: Report sharing requires explicit permission grants
- **SEC-003**: Dashboard widgets must respect user role-based data access
- **SEC-004**: Custom formulas must be validated to prevent code injection

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ conventions for all implementations
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use Vue.js components for drag-and-drop UI (if frontend provided)
- **GUD-005**: All dashboard data must be cacheable with configurable TTL

### Patterns to Follow

- **PAT-001**: Use Builder pattern for custom report construction
- **PAT-002**: Use Strategy pattern for different dashboard widget types
- **PAT-003**: Use Observer pattern for real-time dashboard updates
- **PAT-004**: Use Factory pattern for widget creation
- **PAT-005**: Use Repository pattern for report template storage

### Constraints

- **CON-001**: Custom reports limited to 50 fields maximum for performance
- **CON-002**: Dashboards limited to 20 widgets maximum per page
- **CON-003**: Dashboard refresh rate minimum 30 seconds to prevent overload
- **CON-004**: Custom formulas limited to 500 characters
- **CON-005**: Report templates cannot exceed 1MB in size

## 2. Implementation Steps

### GOAL-001: Custom Report Builder Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-004, DR-FR-001, ARCH-FR-001 | Create report builder foundation with field selection, custom column definitions, aggregation functions, and sorting/filtering capabilities. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `src/Models/CustomReport.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `Searchable`, `LogsActivity`. Define fillable: tenant_id, name, description, source_type (enum: 'gl_accounts', 'gl_transactions', 'trial_balance', 'custom_query'), field_definitions (JSONB array of selected fields with display_name, data_type, aggregation_function), filters (JSONB array of filter conditions), sorting (JSONB array), grouping (JSONB array), formulas (JSONB array of custom calculated fields), layout (JSONB for column order, widths), is_shared (boolean), shared_with_roles (JSONB array), created_by, is_active. Define casts appropriately. Add relationships: belongsTo(Tenant), belongsTo(User, 'created_by'). Add scopes: scopeActive(), scopeSharedWith($userId). Implement Searchable trait methods. | | |
| TASK-002 | Create migration `database/migrations/create_custom_reports_table.php`: Define `custom_reports` table with columns: id (BIGSERIAL), tenant_id (UUID/BIGINT, indexed, NOT NULL), name (VARCHAR(255), NOT NULL), description (TEXT, nullable), source_type (ENUM: 'gl_accounts', 'gl_transactions', 'trial_balance', 'custom_query'), field_definitions (JSONB, NOT NULL), filters (JSONB, nullable), sorting (JSONB, nullable), grouping (JSONB, nullable), formulas (JSONB, nullable), layout (JSONB, nullable), is_shared (BOOLEAN, default false), shared_with_roles (JSONB, nullable), created_by (BIGINT, foreign key to users.id), is_active (BOOLEAN, default true), created_at, updated_at, deleted_at (soft delete). Add unique constraint on (tenant_id, name). Add indexes on (tenant_id, is_active), (tenant_id, created_by). | | |
| TASK-003 | Create `src/Contracts/CustomReportBuilderServiceContract.php` interface: Define methods: `build(CustomReport $report, array $parameters): array` (executes custom report), `validate(array $fieldDefinitions): bool` (validates field selections), `getAvailableFields(string $sourceType): array` (returns available fields for source), `applyFilters(Builder $query, array $filters): Builder` (applies dynamic filters), `applyAggregation(array $data, array $fieldDefinitions): array` (applies aggregation functions), `evaluateFormulas(array $data, array $formulas): array` (evaluates custom formulas). All methods with full PHPDoc. | | |
| TASK-004 | Create `src/Services/CustomReportBuilderService.php` implementing `CustomReportBuilderServiceContract`: Inject `GeneralLedgerRepositoryContract`. Implement `build()`: 1) Validate report definition, 2) Get available fields for source_type via `getAvailableFields()`, 3) Build query based on source_type (GL accounts, transactions, trial balance), 4) Apply filters via `applyFilters()`, 5) Apply sorting and grouping, 6) Execute query, 7) Apply aggregation via `applyAggregation()`, 8) Evaluate custom formulas via `evaluateFormulas()`, 9) Format results according to layout, 10) Return structured array with data, metadata, execution_time. Throw `CustomReportException` on errors. | | |
| TASK-005 | Implement `getAvailableFields()` in `CustomReportBuilderService`: Return array of available fields based on source_type. For 'gl_accounts': return [account_code, account_name, account_type, parent_code, level, is_active, current_balance]. For 'gl_transactions': return [posting_id, posting_date, account_code, account_name, debit, credit, balance, reference, description, cost_center, department, fiscal_period]. For 'trial_balance': return [account_code, account_name, opening_balance, debit, credit, closing_balance]. Each field definition includes: field_name, display_name, data_type (string, number, date, boolean), supports_aggregation (boolean), aggregation_functions (array: sum, avg, count, min, max). | | |
| TASK-006 | Implement `applyFilters()` in `CustomReportBuilderService`: Accept Laravel Query Builder and filters array. Filters format: [{field: 'account_code', operator: 'starts_with', value: '1000'}, {field: 'posting_date', operator: 'between', value: ['2025-01-01', '2025-12-31']}]. Support operators: equals, not_equals, greater_than, less_than, between, in, not_in, starts_with, ends_with, contains, is_null, is_not_null. Dynamically apply where clauses to query. Validate field names against available fields. Sanitize input to prevent SQL injection. Return modified query builder. | | |

### GOAL-002: Report Template Management and Sharing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| DR-FR-001, SR-FR-001 | Implement report template save, load, clone, share functionality with role-based access control. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/Http/Controllers/Api/V1/CustomReportController.php`: Include `declare(strict_types=1);`. Implement methods: `index(IndexCustomReportsRequest $request): JsonResponse` (list custom reports with filtering), `show(int $id): JsonResponse` (get report definition), `store(StoreCustomReportRequest $request): JsonResponse` (create new custom report), `update(UpdateCustomReportRequest $request, int $id): JsonResponse` (update definition), `destroy(int $id): JsonResponse` (soft delete), `execute(ExecuteCustomReportRequest $request, int $id): JsonResponse` (run report and return results), `clone(int $id): JsonResponse` (create copy of report), `share(ShareCustomReportRequest $request, int $id): JsonResponse` (share with roles). Apply auth:sanctum and tenant middleware. | | |
| TASK-008 | Create Form Requests: `StoreCustomReportRequest.php` (validation: name required|max:255|unique per tenant, source_type required|in:enum, field_definitions required|array|min:1|max:50, field_definitions.*.field_name required|string, field_definitions.*.aggregation_function nullable|in:sum,avg,count,min,max, filters nullable|array, formulas nullable|array|max:10, formulas.*.expression required|string|max:500), `UpdateCustomReportRequest.php` (same validation, all optional), `ExecuteCustomReportRequest.php` (validation: parameters nullable|array, export_format nullable|in:json,csv,excel), `ShareCustomReportRequest.php` (validation: role_ids required|array, role_ids.* exists:roles,id). Authorization checks 'manage-custom-reports' permission. | | |
| TASK-009 | Create `src/Http/Resources/CustomReportResource.php`: Transform CustomReport model to JSON:API format: Return array with keys: id, type ('custom_report'), attributes (name, description, source_type, field_definitions, filters, sorting, grouping, formulas, layout, is_shared, shared_with_roles, created_by_name, is_active, created_at, updated_at), relationships (creator if loaded), links (self, execute, clone, share), meta (execution_count, last_executed_at if available). Conditionally hide sensitive fields based on permissions. | | |
| TASK-010 | Implement sharing logic in `CustomReportController::share()`: Accept role_ids array. Validate user has 'share-custom-reports' permission. Update custom_report record: set is_shared=true, set shared_with_roles to provided role_ids. Dispatch `CustomReportSharedEvent` with report_id, shared_by, shared_with_roles. Create audit log entry. Return updated CustomReportResource. Support unsharing by passing empty role_ids array (sets is_shared=false). Validate report belongs to current tenant. | | |
| TASK-011 | Create `src/Policies/CustomReportPolicy.php`: Implement authorization methods: `viewAny(User $user): bool` checks authenticated, `view(User $user, CustomReport $report): bool` checks same tenant AND (created_by matches OR report is_shared with user's roles OR user has 'view-all-custom-reports' permission), `create(User $user): bool` checks 'create-custom-reports' permission, `update(User $user, CustomReport $report): bool` checks created_by matches OR 'manage-all-custom-reports' permission, `delete(User $user, CustomReport $report): bool` checks created_by matches OR 'manage-all-custom-reports' permission, `execute(User $user, CustomReport $report): bool` checks view permission, `share(User $user, CustomReport $report): bool` checks created_by matches AND 'share-custom-reports' permission. Register policy in service provider. | | |

### GOAL-003: Dashboard Widget System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-007, PR-FR-001, DR-FR-003 | Create dashboard system with configurable widgets displaying KPIs, charts, and financial metrics with real-time updates and caching. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create `src/Models/Dashboard.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `LogsActivity`. Define fillable: tenant_id, name, description, layout (JSONB defining grid layout), is_default (boolean), created_by, is_active. Casts: layout => 'array', is_default => 'boolean', is_active => 'boolean'. Relationships: belongsTo(Tenant), belongsTo(User, 'created_by'), hasMany(DashboardWidget). Add scope: scopeDefault(). One dashboard per tenant can be is_default=true (default dashboard shown on login). | | |
| TASK-013 | Create `src/Models/DashboardWidget.php` Eloquent model: Define fillable: dashboard_id, widget_type (ENUM: 'kpi_single', 'kpi_comparison', 'trend_chart', 'pie_chart', 'bar_chart', 'table', 'custom_report'), title, configuration (JSONB with widget-specific settings), position (JSONB with x, y, width, height for grid layout), refresh_interval (INTEGER seconds, min 30), is_visible (boolean). Casts appropriately. Relationships: belongsTo(Dashboard). Add scope: scopeVisible(). Configuration structure varies by widget_type: kpi_single: {metric: 'total_revenue', period: 'current_month', comparison_period: 'prior_month'}, trend_chart: {metrics: ['revenue', 'expenses'], period: 'last_12_months', chart_type: 'line'}. | | |
| TASK-014 | Create migrations: `create_dashboards_table.php` and `create_dashboard_widgets_table.php` with proper schema. dashboards table: id, tenant_id (indexed), name (VARCHAR 255), description (TEXT), layout (JSONB), is_default (BOOLEAN), created_by, is_active, created_at, updated_at, deleted_at. Unique constraint on (tenant_id, is_default) where is_default=true. dashboard_widgets table: id, dashboard_id (indexed, foreign key), widget_type (ENUM), title (VARCHAR 255), configuration (JSONB), position (JSONB), refresh_interval (INTEGER default 300), is_visible (BOOLEAN default true), created_at, updated_at. Add indexes on (dashboard_id, is_visible). | | |
| TASK-015 | Create `src/Contracts/DashboardWidgetServiceContract.php` interface: Define methods: `getData(DashboardWidget $widget): array` (retrieves widget data), `refresh(DashboardWidget $widget): array` (forces data refresh), `getAvailableMetrics(): array` (returns list of available KPI metrics), `calculateKPI(string $metric, array $parameters): float` (calculates single KPI value), `getTrendData(array $metrics, array $parameters): array` (returns time-series data for charts). All methods with PHPDoc. | | |
| TASK-016 | Create `src/Services/DashboardWidgetService.php` implementing `DashboardWidgetServiceContract`: Inject `GeneralLedgerRepositoryContract`, `FiscalPeriodRepositoryContract`, `Cache`. Implement `getData()`: 1) Check cache with key 'dashboard:widget:{id}', 2) If miss, generate data based on widget_type using appropriate method (KPI, chart, table), 3) Apply widget configuration, 4) Cache result with TTL = refresh_interval, 5) Return data array. Implement widget-specific methods: `getKPIData()`, `getChartData()`, `getTableData()`. Use financial_aggregates table for fast data retrieval. Ensure queries complete < 3 seconds (PR-FR-001). | | |

### GOAL-004: Real-Time Dashboard Data and API

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-007, PR-FR-001, ARCH-FR-002 | Implement real-time dashboard API endpoints with automatic refresh, WebSocket support for live updates, and optimized query performance. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Create `src/Http/Controllers/Api/V1/DashboardController.php`: Implement methods: `index(Request $request): JsonResponse` (list user's dashboards), `show(int $id): JsonResponse` (get dashboard with widgets), `store(StoreDashboardRequest $request): JsonResponse` (create dashboard), `update(UpdateDashboardRequest $request, int $id): JsonResponse` (update dashboard), `destroy(int $id): JsonResponse` (delete), `setDefault(int $id): JsonResponse` (set as default dashboard), `widgetData(int $dashboardId, int $widgetId): JsonResponse` (get single widget data), `refreshWidget(int $dashboardId, int $widgetId): JsonResponse` (force widget refresh). Apply auth and tenant middleware. | | |
| TASK-018 | Create `src/Http/Controllers/Api/V1/DashboardWidgetController.php`: Implement methods: `store(StoreDashboardWidgetRequest $request, int $dashboardId): JsonResponse` (add widget to dashboard), `update(UpdateDashboardWidgetRequest $request, int $widgetId): JsonResponse` (update widget config), `destroy(int $widgetId): JsonResponse` (remove widget), `reorder(ReorderWidgetsRequest $request, int $dashboardId): JsonResponse` (update widget positions). Validation ensures max 20 widgets per dashboard. Apply proper authorization. | | |
| TASK-019 | Create API Resources: `DashboardResource.php` (transforms Dashboard with nested widgets), `DashboardWidgetResource.php` (transforms widget with current data and metadata), `DashboardWidgetDataResource.php` (transforms widget data with chart-ready format). Include links for refresh, self, related resources. Add meta with last_refreshed_at, cache_expires_at, next_refresh_at. Format chart data for popular libraries (Chart.js format with labels, datasets, colors). | | |
| TASK-020 | Implement real-time updates using Laravel Broadcasting: Create `src/Events/DashboardWidgetUpdatedEvent` implementing ShouldBroadcast: Properties: dashboard_id, widget_id, widget_data, updated_at. Broadcast on channel `dashboard.{tenant_id}.{dashboard_id}` using private channel. Create listener in `src/Listeners/BroadcastDashboardWidgetUpdate.php` that fires when financial data changes (e.g., new GL posting). Frontend subscribes to channel and auto-updates widgets without polling. Requires Laravel Echo + Pusher/Redis broadcasting configured. | | |
| TASK-021 | Create `src/Commands/RefreshDashboardWidgetsCommand.php`: Artisan command `dashboard:refresh-widgets` with signature accepting --dashboard-id or --all flag. Iterate through widgets needing refresh (where last_refreshed_at + refresh_interval < now). Call `DashboardWidgetService::refresh()` for each. Update widget cache. Log refresh activity. Schedule command to run every minute in Kernel.php. This ensures widgets auto-refresh even without active users. Dispatch `DashboardWidgetUpdatedEvent` after each refresh for real-time updates. | | |

### GOAL-005: Testing and Performance Optimization

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-FR-001, CON-001, CON-002 | Create comprehensive tests for custom reports and dashboards, validate performance requirements, and optimize query execution. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-022 | Create Feature test `tests/Feature/CustomReportBuilderTest.php`: Test scenarios: 1) Create custom report with field selections (expect success, report saved), 2) Execute custom report with filters (expect results match filters), 3) Share report with roles (expect shared_with_roles updated), 4) Clone existing report (expect duplicate created with same definition), 5) Attempt to access shared report as user with allowed role (expect success), 6) Attempt to access shared report as user without role (expect 403), 7) Custom report with aggregation functions (expect sum, avg, count calculated correctly), 8) Custom report with custom formulas (expect formula evaluated correctly), 9) Exceed 50 fields limit (expect validation error), 10) Invalid source_type (expect validation error). Use factories for test data. | | |
| TASK-023 | Create Feature test `tests/Feature/DashboardTest.php`: Test scenarios: 1) Create dashboard with widgets (expect success), 2) Add widget to dashboard (expect widget created, position saved), 3) Get dashboard data (expect all visible widgets with data), 4) Refresh single widget (expect cache cleared, new data fetched), 5) Set dashboard as default (expect is_default=true, previous default reset), 6) Reorder widgets (expect positions updated), 7) Remove widget from dashboard (expect deleted), 8) Exceed 20 widgets limit (expect validation error), 9) Widget with invalid configuration (expect validation error), 10) Dashboard access by different tenant (expect 403). Assert data structure and performance. | | |
| TASK-024 | Create Performance test `tests/Feature/DashboardPerformanceTest.php`: Test scenarios: 1) Load dashboard with 20 widgets (assert total time < 3 seconds per PR-FR-001), 2) Single KPI widget with complex calculation (assert < 500ms), 3) Trend chart with 12 months data (assert < 1 second), 4) Custom report with 50 fields and 10,000 rows (assert < 3 seconds), 5) Concurrent widget refresh (10 widgets simultaneously, assert no timeout), 6) Cache hit scenario (fetch widget data twice, assert second < 100ms), 7) Dashboard with cached widgets (assert full load < 1 second). Use database seeder for large dataset. Measure with microtime(). Tag with @group performance. | | |
| TASK-025 | Create Unit test `tests/Unit/CustomReportBuilderServiceTest.php`: Test `applyFilters()` method with various operators (equals, between, in, contains, etc.), Test `applyAggregation()` with sum, avg, count functions, Test `evaluateFormulas()` with valid and invalid expressions, Test `getAvailableFields()` for each source_type, Test filter validation and SQL injection prevention, Test field limit enforcement (max 50 fields). Mock dependencies. Assert correct query building and result formatting. | | |
| TASK-026 | Optimize dashboard queries: Create database indexes on financial_aggregates table: (tenant_id, period_id, account_type), (tenant_id, account_code, period_id). Create materialized view `mv_dashboard_kpis` with pre-calculated common KPIs: total_revenue, total_expenses, net_income, total_assets, total_liabilities, total_equity per tenant per period. Refresh materialized view after GL posting (trigger or scheduled job). Update DashboardWidgetService to use materialized view for KPI widgets. Document refresh strategy in comments. Add `php artisan dashboard:refresh-kpis` command for manual refresh. | | |

## 3. Alternatives

- **ALT-001**: Use external BI tool (Metabase, Looker) for custom reporting
  - *Pros*: Rich features, mature platform, less development
  - *Cons*: Licensing costs, less API integration, not headless-first
  - *Decision*: Not chosen - Custom builder provides better API control for AI agents

- **ALT-002**: Use NoSQL (MongoDB) for flexible custom report schemas
  - *Pros*: Schema-less, easier to store arbitrary field definitions
  - *Cons*: Complex joins with GL data, no ACID transactions, additional infrastructure
  - *Decision*: Not chosen - JSONB in PostgreSQL provides similar flexibility with ACID guarantees

- **ALT-003**: Build drag-and-drop UI as part of Laravel package
  - *Pros*: Integrated solution, single package
  - *Cons*: Violates headless architecture, limits frontend flexibility
  - *Decision*: Not chosen - API-only approach allows any frontend implementation

- **ALT-004**: Use Server-Sent Events (SSE) instead of WebSockets for real-time updates
  - *Pros*: Simpler than WebSockets, HTTP-based, no additional server
  - *Cons*: One-way communication only, connection limits
  - *Decision*: Acceptable alternative - Can be implemented later if WebSocket overhead too high

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-financial-reporting` (PLAN01, PLAN02) - Foundation and API required
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Tenant isolation
- `azaharizaman/erp-authentication` (PRD01-SUB02) - User roles and permissions
- `azaharizaman/erp-general-ledger` (PRD01-SUB08) - Data source
- `symfony/expression-language` - Safe formula evaluation (added in PLAN01)
- `laravel/echo` - Optional for WebSocket real-time updates
- `pusher/pusher-php-server` OR `predis/predis` - Broadcasting driver

**Internal Dependencies:**
- PLAN01 (ReportDefinition, ReportGeneratorService) - Foundation layer
- PLAN02 (API Resources, Controllers) - API patterns established
- General Ledger repository for data queries
- Financial aggregates table for performance

**Infrastructure Dependencies:**
- Redis for widget caching and broadcasting (recommended)
- WebSocket server (Pusher, Laravel WebSockets, or Soketi) for real-time updates
- Materialized view support in database (PostgreSQL)

## 5. Files

**Models:**
- `packages/financial-reporting/src/Models/CustomReport.php` - Custom report definitions
- `packages/financial-reporting/src/Models/Dashboard.php` - Dashboard layouts
- `packages/financial-reporting/src/Models/DashboardWidget.php` - Widget configurations

**Migrations:**
- `packages/financial-reporting/database/migrations/create_custom_reports_table.php` - Custom reports schema
- `packages/financial-reporting/database/migrations/create_dashboards_table.php` - Dashboards schema
- `packages/financial-reporting/database/migrations/create_dashboard_widgets_table.php` - Widgets schema

**Contracts:**
- `packages/financial-reporting/src/Contracts/CustomReportBuilderServiceContract.php` - Report builder interface
- `packages/financial-reporting/src/Contracts/DashboardWidgetServiceContract.php` - Widget service interface

**Services:**
- `packages/financial-reporting/src/Services/CustomReportBuilderService.php` - Report building logic
- `packages/financial-reporting/src/Services/DashboardWidgetService.php` - Widget data retrieval

**Controllers:**
- `packages/financial-reporting/src/Http/Controllers/Api/V1/CustomReportController.php` - Custom report CRUD
- `packages/financial-reporting/src/Http/Controllers/Api/V1/DashboardController.php` - Dashboard management
- `packages/financial-reporting/src/Http/Controllers/Api/V1/DashboardWidgetController.php` - Widget management

**Form Requests:**
- `packages/financial-reporting/src/Http/Requests/StoreCustomReportRequest.php` - Create report validation
- `packages/financial-reporting/src/Http/Requests/UpdateCustomReportRequest.php` - Update report validation
- `packages/financial-reporting/src/Http/Requests/ExecuteCustomReportRequest.php` - Execute report validation
- `packages/financial-reporting/src/Http/Requests/ShareCustomReportRequest.php` - Share report validation
- `packages/financial-reporting/src/Http/Requests/StoreDashboardRequest.php` - Create dashboard validation
- `packages/financial-reporting/src/Http/Requests/UpdateDashboardRequest.php` - Update dashboard validation
- `packages/financial-reporting/src/Http/Requests/StoreDashboardWidgetRequest.php` - Add widget validation
- `packages/financial-reporting/src/Http/Requests/UpdateDashboardWidgetRequest.php` - Update widget validation
- `packages/financial-reporting/src/Http/Requests/ReorderWidgetsRequest.php` - Reorder widgets validation

**API Resources:**
- `packages/financial-reporting/src/Http/Resources/CustomReportResource.php` - Custom report transformation
- `packages/financial-reporting/src/Http/Resources/DashboardResource.php` - Dashboard transformation
- `packages/financial-reporting/src/Http/Resources/DashboardWidgetResource.php` - Widget transformation
- `packages/financial-reporting/src/Http/Resources/DashboardWidgetDataResource.php` - Widget data transformation

**Policies:**
- `packages/financial-reporting/src/Policies/CustomReportPolicy.php` - Custom report authorization

**Events:**
- `packages/financial-reporting/src/Events/DashboardWidgetUpdatedEvent.php` - Real-time widget updates
- `packages/financial-reporting/src/Events/CustomReportSharedEvent.php` - Report sharing notification

**Listeners:**
- `packages/financial-reporting/src/Listeners/BroadcastDashboardWidgetUpdate.php` - Broadcast widget updates

**Commands:**
- `packages/financial-reporting/src/Commands/RefreshDashboardWidgetsCommand.php` - Auto-refresh widgets

**Tests:**
- `packages/financial-reporting/tests/Feature/CustomReportBuilderTest.php` - Custom report tests
- `packages/financial-reporting/tests/Feature/DashboardTest.php` - Dashboard tests
- `packages/financial-reporting/tests/Feature/DashboardPerformanceTest.php` - Performance tests
- `packages/financial-reporting/tests/Unit/CustomReportBuilderServiceTest.php` - Builder unit tests

## 6. Testing

- **TEST-001**: Create custom report with 10 fields, execute with filters, verify results match filter criteria
- **TEST-002**: Custom report with aggregation (sum, avg, count), verify calculations correct
- **TEST-003**: Custom report with custom formula "Net Income = Revenue - Expenses", verify formula evaluated
- **TEST-004**: Share custom report with role, access as user with role (expect success), without role (expect 403)
- **TEST-005**: Clone custom report, verify duplicate has same definition but different ID and name
- **TEST-006**: Attempt to create report with 51 fields, expect validation error (max 50)
- **TEST-007**: Create dashboard with 5 widgets, verify all widgets returned with data
- **TEST-008**: Add KPI widget to dashboard, verify widget shows correct metric value
- **TEST-009**: Refresh widget, verify cache cleared and new data fetched
- **TEST-010**: Set dashboard as default, verify is_default=true and previous default reset
- **TEST-011**: Performance: Load dashboard with 20 widgets, assert total time < 3 seconds (PR-FR-001)
- **TEST-012**: Performance: Custom report with 50 fields and 10,000 rows, assert < 3 seconds

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Custom formulas could be exploited for code injection or DoS attacks
  - *Mitigation*: Use symfony/expression-language with restricted functions, validate formulas before execution, set execution timeout
- **RISK-002**: Real-time WebSocket updates could overwhelm server with high user count
  - *Mitigation*: Implement connection limits, rate limiting, use Redis for message queueing, consider SSE as alternative
- **RISK-003**: Complex custom reports could cause database performance issues
  - *Mitigation*: Enforce field limit (50 max), query timeout, use explain analyze for optimization, implement query result caching
- **RISK-004**: Dashboard widgets fetching stale cached data
  - *Mitigation*: Configurable refresh_interval per widget, manual refresh button, cache invalidation on data changes

**Assumptions:**
- **ASSUMPTION-001**: Users understand basic SQL concepts (filtering, aggregation, grouping)
- **ASSUMPTION-002**: Frontend will implement drag-and-drop UI for report builder (API provides structure)
- **ASSUMPTION-003**: Financial data aggregated regularly (financial_aggregates table updated)
- **ASSUMPTION-004**: WebSocket infrastructure (Pusher or Laravel WebSockets) available for real-time updates
- **ASSUMPTION-005**: Dashboard usage patterns: 5-10 active dashboards per tenant, 10-15 widgets per dashboard

## 8. KIV for future implementations

- **KIV-001**: Add AI-powered report builder suggestions based on user behavior
- **KIV-002**: Implement report scheduling with automatic email delivery (covered in PLAN04)
- **KIV-003**: Add collaborative features (comments, annotations on reports)
- **KIV-004**: Implement report versioning and change history
- **KIV-005**: Add natural language query interface for report building
- **KIV-006**: Support embedded dashboards in external applications (iframe, embed code)
- **KIV-007**: Add drill-through from dashboard widgets to detailed reports
- **KIV-008**: Implement dashboard templates for common financial analysis scenarios

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md](../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md)
- Related PLAN: [PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md](PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md)
- Related PLAN: [PRD01-SUB20-PLAN02-implement-financial-reporting-api-comparison.md](PRD01-SUB20-PLAN02-implement-financial-reporting-api-comparison.md)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- Laravel Broadcasting: https://laravel.com/docs/broadcasting
- Symfony Expression Language: https://symfony.com/doc/current/components/expression_language.html
