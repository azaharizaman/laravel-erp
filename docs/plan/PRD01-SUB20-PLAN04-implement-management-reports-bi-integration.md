---
plan: Implement Management Reports and BI Integration
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, financial-reporting, management-reports, departmental-pl, cost-center, consolidation, bi-integration, export, scheduling]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan completes the Financial Reporting module by adding management reporting capabilities (departmental P&L, cost center analysis), consolidation reporting for multi-company groups, report scheduling with automated delivery, export functionality (PDF, Excel, CSV), and business intelligence tool integration APIs for external BI platforms (Power BI, Tableau, Looker).

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-FR-005**: Generate management reports (departmental P&L, cost center analysis)
- **REQ-FR-FR-006**: Support report scheduling with email delivery and export formats (PDF, Excel, CSV)
- **REQ-FR-FR-008**: Support consolidation reporting for multi-company groups
- **REQ-IR-FR-003**: Provide BI tool integration (Power BI, Tableau, Looker) via APIs
- **REQ-BR-FR-001**: Reports can only be generated for closed accounting periods or current period
- **REQ-BR-FR-002**: Compliance reports (SOX, IFRS) require audit trail and version control
- **REQ-DR-FR-002**: Maintain report execution history with parameters and generated snapshots
- **REQ-PR-FR-002**: Financial statement generation must complete in < 5 seconds for monthly period
- **REQ-ARCH-FR-001**: Use SQL for report data retrieval with optimized queries

### Security Constraints

- **SEC-001**: Management reports must enforce department/cost center access restrictions
- **SEC-002**: Scheduled reports must respect recipient permissions at execution time
- **SEC-003**: BI integration API keys must be encrypted and rotated regularly
- **SEC-004**: Consolidated reports require cross-company authorization

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ queued jobs for report generation and email delivery
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Use external libraries for PDF/Excel generation (dompdf, PhpSpreadsheet)
- **GUD-005**: All scheduled reports must be logged with execution results

### Patterns to Follow

- **PAT-001**: Use Action pattern for scheduled report execution
- **PAT-002**: Use Strategy pattern for different export formats (PDF, Excel, CSV)
- **PAT-003**: Use Adapter pattern for BI tool integrations
- **PAT-004**: Use Chain of Responsibility for consolidation logic
- **PAT-005**: Use Observer pattern for report completion notifications

### Constraints

- **CON-001**: Scheduled reports limited to 100 schedules per tenant
- **CON-002**: Email delivery limited to 50 recipients per report
- **CON-003**: PDF export limited to 50 pages per report
- **CON-004**: Consolidation limited to 10 companies per group
- **CON-005**: BI API rate limited to 1000 requests per hour per tenant

## 2. Implementation Steps

### GOAL-001: Management Reports Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-005, BR-FR-001, ARCH-FR-001 | Create departmental P&L and cost center analysis reports with proper data segmentation and access control. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `src/Services/ManagementReportService.php` implementing `ManagementReportServiceContract`: Include `declare(strict_types=1);`. Inject `GeneralLedgerRepositoryContract`, `ReportGeneratorServiceContract`, `DepartmentRepositoryContract`, `CostCenterRepositoryContract`. Implement methods: `generateDepartmentalPL(int $departmentId, int $periodId, array $options): array` (generates P&L for specific department), `generateCostCenterAnalysis(int $costCenterId, int $periodId, array $options): array` (generates cost analysis), `generateDepartmentComparison(array $departmentIds, int $periodId): array` (compares multiple departments), `validateDepartmentAccess(User $user, int $departmentId): bool` (checks user can access department data). All methods with full PHPDoc. | | |
| TASK-002 | Implement `generateDepartmentalPL()` in `ManagementReportService`: Accept department_id, period_id, options (include_sub_departments boolean, comparison_period_id integer). Query GL transactions filtered by department_id (and sub-departments if included). Group by account_type (revenue, expenses). Calculate totals: Total Revenue, Total Expenses, Department Net Income, Department Contribution Margin (%). If comparison_period included, calculate variance. Apply account mappings from Income Statement report definition. Return structured array with sections, line items, totals, comparison data. Cache result with key 'mgmt_report:dept_pl:{department_id}:{period_id}'. Validate user has access via `validateDepartmentAccess()`. | | |
| TASK-003 | Implement `generateCostCenterAnalysis()` in `ManagementReportService`: Accept cost_center_id, period_id, options (include_budget_comparison boolean, group_by_account_type boolean). Query GL transactions filtered by cost_center_id. Group by account (if group_by_account_type=false) or account_type (if true). Calculate: Total Costs, Cost Breakdown by Account/Type, Cost per Unit (if unit data available), Budget Variance (if budget comparison enabled). Support drill-down to transaction detail. Return structured array. Validate user has cost center access. Cache result. Execution must complete < 5 seconds (PR-FR-002). | | |
| TASK-004 | Create `src/Http/Controllers/Api/V1/ManagementReportController.php`: Include `declare(strict_types=1);`. Implement methods: `departmentalPL(GenerateDepartmentalPLRequest $request): JsonResponse` (POST /api/v1/financial-reports/management/departmental-pl), `costCenterAnalysis(GenerateCostCenterAnalysisRequest $request): JsonResponse` (POST /api/v1/financial-reports/management/cost-center-analysis), `departmentComparison(GenerateDepartmentComparisonRequest $request): JsonResponse` (POST /api/v1/financial-reports/management/department-comparison). Apply auth:sanctum and tenant middleware. Check 'view-management-reports' permission. Return ManagementReportResource. Record execution in report_execution_history table. | | |
| TASK-005 | Create Form Requests: `GenerateDepartmentalPLRequest.php` (validation: department_id required|exists:departments,id, period_id required|exists:fiscal_periods,id, include_sub_departments nullable|boolean, comparison_period_id nullable|exists:fiscal_periods,id), `GenerateCostCenterAnalysisRequest.php` (validation: cost_center_id required|exists:cost_centers,id, period_id required, include_budget_comparison nullable|boolean, group_by_account_type nullable|boolean). Authorization: check user has access to specified department/cost center via Policy. Validate department/cost center belongs to current tenant. | | |
| TASK-006 | Create `src/Http/Resources/ManagementReportResource.php`: Transform management report data to JSON:API format: Return array with keys: type ('management_report'), attributes (report_type 'departmental_pl'|'cost_center_analysis', entity_id, entity_name, period, data with sections and line items, totals, comparison if applicable, metadata), links (execution_history, export with format options, drill_down if supported), meta (execution_time_ms, row_count, cache_hit boolean). Format numbers with locale-specific formatting. Include chart_data for visualization. | | |

### GOAL-002: Consolidation Reporting for Multi-Company Groups

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-008, SEC-004, CON-004 | Implement consolidation reporting to combine financial statements across multiple legal entities within a tenant group. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/Models/CompanyGroup.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `LogsActivity`. Define fillable: tenant_id, name, description, parent_company_id (self-reference for hierarchical groups), consolidation_method (ENUM: 'full', 'proportional', 'equity'), is_active. Casts: is_active => 'boolean'. Relationships: belongsTo(Tenant), belongsTo(Company, 'parent_company_id'), belongsToMany(Company, 'company_group_members', 'group_id', 'company_id')->withPivot('ownership_percentage', 'consolidation_method', 'effective_from', 'effective_to'). Add scope: scopeActive(). | | |
| TASK-008 | Create migration `database/migrations/create_company_groups_table.php` and `create_company_group_members_table.php`: company_groups table: id, tenant_id (indexed), name (VARCHAR 255), description (TEXT), parent_company_id (BIGINT nullable, self-FK), consolidation_method (ENUM), is_active, created_at, updated_at. company_group_members table: id, group_id (FK to company_groups), company_id (FK to companies in backoffice module), ownership_percentage (DECIMAL 5,2, 0-100), consolidation_method (ENUM, nullable, overrides group default), effective_from (DATE), effective_to (DATE nullable), created_at, updated_at. Add unique constraint on (group_id, company_id, effective_from). | | |
| TASK-009 | Create `src/Services/ConsolidationReportService.php` implementing `ConsolidationReportServiceContract`: Inject `ReportGeneratorServiceContract`, `CompanyGroupRepository`. Implement methods: `generateConsolidatedReport(int $groupId, string $reportType, int $periodId, array $options): array` (generates consolidated financial statement), `eliminateIntercompanyTransactions(array $companyReports): array` (removes intercompany transactions to avoid double-counting), `applyConsolidationMethod(array $companyData, string $method, float $ownershipPercentage): array` (applies full, proportional, or equity method), `validateConsolidationPeriod(int $groupId, int $periodId): bool` (ensures all companies have closed period). All methods with PHPDoc. | | |
| TASK-010 | Implement `generateConsolidatedReport()` in `ConsolidationReportService`: Accept group_id, report_type ('balance_sheet', 'income_statement'), period_id, options (eliminate_intercompany boolean default true, include_company_breakdown boolean). Validate user has 'view-consolidated-reports' permission and access to all companies in group. Retrieve company_group with members. For each member company, generate individual report using `ReportGeneratorService::generate()`. Apply consolidation method (full, proportional, equity) to each company's data via `applyConsolidationMethod()`. Sum/aggregate report lines across companies. If eliminate_intercompany=true, call `eliminateIntercompanyTransactions()`. Verify financial statement balancing. Return consolidated report with optional company breakdown. Cache with 1-hour TTL. | | |
| TASK-011 | Implement `eliminateIntercompanyTransactions()` in `ConsolidationReportService`: Accept array of company reports. Query GL transactions tagged as intercompany (requires intercompany_flag in gl_postings table or via account range). Identify matching intercompany receivables/payables and revenue/expenses between group companies. Eliminate matching pairs from consolidated totals. Log eliminated transactions in execution metadata. Return adjusted report data. Note: Requires GL postings to be tagged with intercompany_counterparty_company_id for matching. Complex elimination rules may require manual journal entries. | | |
| TASK-012 | Create `src/Http/Controllers/Api/V1/ConsolidationReportController.php`: Implement methods: `generate(GenerateConsolidationReportRequest $request): JsonResponse` (POST /api/v1/financial-reports/consolidation), `listGroups(Request $request): JsonResponse` (GET /api/v1/financial-reports/consolidation/groups - lists available company groups), `groupDetails(int $groupId): JsonResponse` (GET /api/v1/financial-reports/consolidation/groups/{id} - shows group structure and members). Apply auth and tenant middleware. Check 'view-consolidated-reports' permission. Return ConsolidationReportResource with company breakdown if requested. | | |

### GOAL-003: Report Scheduling and Automated Delivery

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-006, DR-FR-002, GUD-002 | Implement report scheduling with automated generation and email delivery on configurable schedules (daily, weekly, monthly). | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create `src/Models/ReportSchedule.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `LogsActivity`. Define fillable: tenant_id, report_definition_id (FK), custom_report_id (FK, nullable), name, description, schedule_type (ENUM: 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'), schedule_config (JSONB with day_of_week for weekly, day_of_month for monthly, time_of_day, timezone), parameters (JSONB with period_type 'current_month'|'prior_month'|'ytd', filters), recipients (JSONB array of email addresses), export_format (ENUM: 'pdf', 'excel', 'csv'), is_active, last_run_at, next_run_at, created_by. Casts appropriately. Relationships: belongsTo(ReportDefinition), belongsTo(CustomReport), belongsTo(User, 'created_by'). Add scope: scopeActive(), scopeDueForExecution(). | | |
| TASK-014 | Create migration `database/migrations/create_report_schedules_table.php` and `create_report_schedule_executions_table.php`: report_schedules table: id, tenant_id (indexed), report_definition_id (nullable FK), custom_report_id (nullable FK), name (VARCHAR 255), description (TEXT), schedule_type (ENUM), schedule_config (JSONB), parameters (JSONB), recipients (JSONB), export_format (ENUM), is_active (BOOLEAN), last_run_at (TIMESTAMP nullable), next_run_at (TIMESTAMP indexed), created_by, created_at, updated_at. Add constraint: report_definition_id or custom_report_id must be set (not both null). report_schedule_executions table: id, schedule_id (FK indexed), executed_at (TIMESTAMP), status (ENUM: 'success', 'failed', 'partial'), execution_time_ms, recipients_count, delivery_status (JSONB with per-recipient status), error_message (TEXT nullable), created_at. | | |
| TASK-015 | Create `src/Actions/ExecuteScheduledReportAction.php` using Laravel Actions: Implement `handle(ReportSchedule $schedule): void` method. Generate report using appropriate service (ReportGeneratorService for standard, CustomReportBuilderService for custom). Apply schedule parameters (e.g., current_month resolves to current fiscal period). Export report to specified format (PDF, Excel, CSV) via ExportService. Send email to recipients with report attached. Update schedule: last_run_at, calculate next_run_at based on schedule_type and schedule_config. Record execution in report_schedule_executions table with status, delivery_status. Dispatch `ScheduledReportExecutedEvent`. Handle errors: log, mark status='failed', notify schedule creator. Use as invokable job: `ExecuteScheduledReportAction::dispatch($schedule)`. | | |
| TASK-016 | Create `src/Commands/ProcessScheduledReportsCommand.php`: Artisan command `reports:process-schedules` with signature. Query ReportSchedule::active()->dueForExecution() (where next_run_at <= now). For each schedule, dispatch `ExecuteScheduledReportAction::dispatch($schedule)` as queued job. Update schedule.next_run_at immediately to prevent duplicate execution. Log processing activity. Schedule command to run every hour in Kernel.php: `$schedule->command('reports:process-schedules')->hourly()`. Add --force flag to re-execute failed schedules. | | |
| TASK-017 | Create `src/Http/Controllers/Api/V1/ReportScheduleController.php`: Implement CRUD methods: `index(Request $request): JsonResponse` (list schedules with filtering), `show(int $id): JsonResponse` (get schedule details), `store(StoreReportScheduleRequest $request): JsonResponse` (create schedule), `update(UpdateReportScheduleRequest $request, int $id): JsonResponse` (update schedule), `destroy(int $id): JsonResponse` (delete), `toggle(int $id): JsonResponse` (activate/deactivate schedule), `executeNow(int $id): JsonResponse` (trigger immediate execution), `executionHistory(int $id): JsonResponse` (list executions for schedule). Apply auth and tenant middleware. Check 'manage-report-schedules' permission. Return ReportScheduleResource. | | |
| TASK-018 | Create Form Requests: `StoreReportScheduleRequest.php` (validation: name required|max:255, report_definition_id nullable|exists, custom_report_id nullable|exists, schedule_type required|in:enum, schedule_config required|array, schedule_config.time_of_day required|date_format:H:i, recipients required|array|min:1|max:50, recipients.* email, export_format required|in:pdf,excel,csv), `UpdateReportScheduleRequest.php` (same validation, all optional). Authorization: check 'manage-report-schedules' permission. Custom validation: ensure report_definition_id or custom_report_id provided (not both null). Validate recipients count <= 50 (CON-002). | | |

### GOAL-004: Report Export Functionality

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-FR-006, CON-003 | Implement report export to PDF, Excel, and CSV formats with proper formatting and pagination. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create `src/Contracts/ReportExportServiceContract.php` interface: Define methods: `exportToPDF(array $reportData, array $options): string` (returns file path), `exportToExcel(array $reportData, array $options): string` (returns file path), `exportToCSV(array $reportData, array $options): string` (returns file path), `validateExportSize(array $reportData, string $format): bool` (checks size limits), `formatForExport(array $reportData, string $format): array` (prepares data for export). All methods with PHPDoc. Options include: orientation ('portrait'|'landscape'), paper_size ('A4'|'letter'), include_charts boolean, include_logo boolean, header_text, footer_text. | | |
| TASK-020 | Create `src/Services/ReportExportService.php` implementing `ReportExportServiceContract`: Inject `Filesystem`. Implement `exportToPDF()`: Use dompdf/dompdf library. Format report data into HTML using Blade view: `financial-reporting::exports.pdf-report` with report data, apply styling (CSS for financial statement formatting), render PDF via Dompdf, save to storage/app/reports/exports/{tenant_id}/{filename}.pdf, return file path. Enforce page limit (50 pages per CON-003). Support landscape/portrait orientation. Include page numbers, header with company name/logo, footer with execution timestamp. Handle multi-page reports with page breaks at section boundaries. | | |
| TASK-021 | Implement `exportToExcel()` in `ReportExportService`: Use phpoffice/phpspreadsheet library. Create new Spreadsheet, set properties (title, subject, creator). Create worksheet for report data. Format as table with headers: bold, background color, borders. Populate rows with report line items. Apply number formatting to financial columns (accounting format with thousand separators, 2 decimal places). Auto-size columns for readability. If multi-period comparison, create separate sheet per period. Include summary sheet with chart if include_charts=true. Save as .xlsx to storage/app/reports/exports/{tenant_id}/{filename}.xlsx. Return file path. Support up to 100,000 rows (Excel limit). | | |
| TASK-022 | Implement `exportToCSV()` in `ReportExportService`: Format report data as flat CSV structure. Create CSV file with headers: Report Line, Account Code, Account Name, Debit, Credit, Balance, Period. Write rows using fputcsv(). Handle multi-period comparison by adding Period column. Save to storage/app/reports/exports/{tenant_id}/{filename}.csv. Return file path. CSV is simplest format, no styling, best for data import into other tools. Support large datasets efficiently (streaming write). | | |
| TASK-023 | Create export endpoint in `ReportExecutionController`: Implement `export(ExportReportRequest $request, int $executionId): Response` method. Retrieve execution from report_execution_history. Validate user has access (same tenant, view permission). Extract result_snapshot. Call `ReportExportService::exportToPDF/Excel/CSV()` based on requested format. Return download response with appropriate Content-Type and filename. Set Cache-Control headers. Log export activity in report_access_log. Apply rate limiting (10 exports per minute per user). For scheduled reports, attach exported file to email instead of returning download. | | |
| TASK-024 | Create Blade view `packages/financial-reporting/resources/views/exports/pdf-report.blade.php`: Design professional PDF layout with header (company logo, report title, period), body (report sections with line items formatted as table), footer (page numbers, execution timestamp, "Generated by Laravel ERP"). Use CSS for styling: financial statement conventions (assets on left, liabilities+equity on right for Balance Sheet, proper indentation for sub-accounts, bold for totals/subtotals). Support multi-page with page breaks. Include watermark for sensitive reports. Make responsive to portrait/landscape orientation. | | |

### GOAL-005: BI Tool Integration and Testing

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-FR-003, SEC-003, CON-005 | Provide API integration for external BI tools (Power BI, Tableau, Looker) with authentication and rate limiting. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-025 | Create `src/Models/BIIntegration.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `BelongsToTenant`, `LogsActivity`. Define fillable: tenant_id, name, tool_type (ENUM: 'power_bi', 'tableau', 'looker', 'custom'), api_key_hash (hashed API key for authentication), api_key_prefix (first 8 chars for identification), connection_config (JSONB with tool-specific settings), is_active, last_used_at, created_by. Casts: is_active => 'boolean', last_used_at => 'datetime'. Relationships: belongsTo(Tenant), belongsTo(User, 'created_by'). Add method: `validateApiKey(string $plainKey): bool` checks hash. Add scope: scopeActive(). | | |
| TASK-026 | Create migration `database/migrations/create_bi_integrations_table.php`: Define bi_integrations table: id, tenant_id (indexed), name (VARCHAR 255), tool_type (ENUM), api_key_hash (VARCHAR 255), api_key_prefix (VARCHAR 20), connection_config (JSONB), is_active (BOOLEAN), last_used_at (TIMESTAMP nullable), created_by, created_at, updated_at. Add unique constraint on (tenant_id, api_key_prefix). Add index on (tenant_id, is_active). Store only hashed API keys, never plain text. Use bcrypt or similar for hashing. | | |
| TASK-027 | Create `src/Http/Controllers/Api/V1/BIIntegrationController.php`: Implement endpoints: POST /api/v1/bi/data (generic data endpoint for BI tools), GET /api/v1/bi/schemas (returns available data schemas/tables), GET /api/v1/bi/reports (lists available reports), POST /api/v1/bi/execute-report (executes report and returns raw data). Authenticate via custom API key in Authorization header: "Bearer {api_key}". Validate API key against bi_integrations table. Apply rate limiting: 1000 requests per hour per API key (CON-005). Return data in JSON format optimized for BI tools (flat structure, typed columns). Log all BI API access. | | |
| TASK-028 | Implement BI data endpoint `BIIntegrationController::data()`: Accept parameters: entity (e.g., 'gl_accounts', 'gl_transactions', 'trial_balance'), period_id, filters (array), fields (array of requested fields), limit, offset. Query specified entity from database. Apply tenant_id filter automatically. Apply additional filters. Return paginated data in flat JSON format: {data: [{field1: value1, field2: value2}], pagination: {total, limit, offset}, schema: {field1: {type: 'string'}, field2: {type: 'number'}}}. Support large datasets with efficient pagination. Cache frequently accessed queries. | | |
| TASK-029 | Create Feature test `tests/Feature/ManagementReportTest.php`: Test departmental P&L generation with sub-departments, cost center analysis with budget comparison, department comparison across 3 departments, consolidation report for company group with 3 companies, intercompany elimination, unauthorized access to department report (expect 403), report with closed vs open period. Assert data structure, calculations, execution time < 5 seconds. Use factories for test data. | | |
| TASK-030 | Create Feature test `tests/Feature/ReportSchedulingTest.php`: Test create schedule with daily frequency, execute schedule immediately via executeNow(), process due schedules command finds and executes schedule, email delivery to multiple recipients, schedule execution history recorded, deactivate/activate schedule, delete schedule with executions, export report to PDF/Excel/CSV formats, BI API authentication with valid/invalid API key, BI data endpoint returns correct schema and data, rate limiting on BI API (exceed 1000 requests). Assert schedule calculations (next_run_at), export file creation, email sent, API rate limit enforced. | | |

## 3. Alternatives

- **ALT-001**: Use SaaS reporting tool (Jasper, Crystal Reports) instead of custom implementation
  - *Pros*: Feature-rich, mature, less development
  - *Cons*: Licensing costs, vendor lock-in, not API-first, deployment complexity
  - *Decision*: Not chosen - Custom implementation provides better control and API integration

- **ALT-002**: Use message queue (RabbitMQ, SQS) for report scheduling instead of cron + database
  - *Pros*: More reliable, better at-least-once delivery, scalable
  - *Cons*: Additional infrastructure, increased complexity
  - *Decision*: Deferred - Laravel Queue with database driver sufficient for MVP; can migrate to dedicated queue later

- **ALT-003**: Use dedicated reporting microservice instead of package
  - *Pros*: Independent scaling, technology flexibility, isolation
  - *Cons*: Increased complexity, deployment overhead, network latency
  - *Decision*: Not chosen - Monorepo package approach simpler for MVP; can extract later if needed

- **ALT-004**: Support only CSV export (simplest format)
  - *Pros*: Simplest implementation, universally supported
  - *Cons*: No formatting, no charts, poor user experience for presentations
  - *Decision*: Not chosen - PDF/Excel required for professional reporting and compliance

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-financial-reporting` (PLAN01-03) - Foundation, API, custom reports required
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Tenant context
- `azaharizaman/erp-backoffice` (PRD01-SUB15) - Department and cost center data
- `azaharizaman/erp-general-ledger` (PRD01-SUB08) - Transaction data
- `dompdf/dompdf` - PDF generation
- `phpoffice/phpspreadsheet` - Excel generation
- `laravel/framework` (mail component) - Email delivery

**Internal Dependencies:**
- PLAN01: ReportDefinition, ReportGeneratorService foundation
- PLAN02: API controllers, resources, multi-period comparison
- PLAN03: Custom reports, dashboard widgets
- Backoffice module for department/cost center hierarchy
- Notification module (PRD01-SUB22) for scheduled report delivery notifications

**Infrastructure Dependencies:**
- Queue worker for scheduled report jobs (Redis Queue recommended)
- SMTP server for email delivery
- File storage with adequate space for exported reports
- Cron daemon for schedule processing command

## 5. Files

**Models:**
- `packages/financial-reporting/src/Models/CompanyGroup.php` - Company group definitions
- `packages/financial-reporting/src/Models/ReportSchedule.php` - Report schedules
- `packages/financial-reporting/src/Models/BIIntegration.php` - BI tool connections

**Migrations:**
- `packages/financial-reporting/database/migrations/create_company_groups_table.php` - Company groups schema
- `packages/financial-reporting/database/migrations/create_company_group_members_table.php` - Group membership
- `packages/financial-reporting/database/migrations/create_report_schedules_table.php` - Schedules schema
- `packages/financial-reporting/database/migrations/create_report_schedule_executions_table.php` - Execution log
- `packages/financial-reporting/database/migrations/create_bi_integrations_table.php` - BI integrations schema

**Contracts:**
- `packages/financial-reporting/src/Contracts/ManagementReportServiceContract.php` - Management reports interface
- `packages/financial-reporting/src/Contracts/ConsolidationReportServiceContract.php` - Consolidation interface
- `packages/financial-reporting/src/Contracts/ReportExportServiceContract.php` - Export interface

**Services:**
- `packages/financial-reporting/src/Services/ManagementReportService.php` - Management reporting logic
- `packages/financial-reporting/src/Services/ConsolidationReportService.php` - Consolidation logic
- `packages/financial-reporting/src/Services/ReportExportService.php` - Export to PDF/Excel/CSV

**Controllers:**
- `packages/financial-reporting/src/Http/Controllers/Api/V1/ManagementReportController.php` - Management reports API
- `packages/financial-reporting/src/Http/Controllers/Api/V1/ConsolidationReportController.php` - Consolidation API
- `packages/financial-reporting/src/Http/Controllers/Api/V1/ReportScheduleController.php` - Schedule management
- `packages/financial-reporting/src/Http/Controllers/Api/V1/BIIntegrationController.php` - BI tool API

**Form Requests:**
- `packages/financial-reporting/src/Http/Requests/GenerateDepartmentalPLRequest.php` - Departmental P&L validation
- `packages/financial-reporting/src/Http/Requests/GenerateCostCenterAnalysisRequest.php` - Cost center validation
- `packages/financial-reporting/src/Http/Requests/GenerateConsolidationReportRequest.php` - Consolidation validation
- `packages/financial-reporting/src/Http/Requests/StoreReportScheduleRequest.php` - Create schedule validation
- `packages/financial-reporting/src/Http/Requests/UpdateReportScheduleRequest.php` - Update schedule validation
- `packages/financial-reporting/src/Http/Requests/ExportReportRequest.php` - Export validation

**API Resources:**
- `packages/financial-reporting/src/Http/Resources/ManagementReportResource.php` - Management report transformation
- `packages/financial-reporting/src/Http/Resources/ConsolidationReportResource.php` - Consolidation transformation
- `packages/financial-reporting/src/Http/Resources/ReportScheduleResource.php` - Schedule transformation

**Actions:**
- `packages/financial-reporting/src/Actions/ExecuteScheduledReportAction.php` - Execute scheduled report

**Commands:**
- `packages/financial-reporting/src/Commands/ProcessScheduledReportsCommand.php` - Process due schedules

**Views:**
- `packages/financial-reporting/resources/views/exports/pdf-report.blade.php` - PDF report template

**Tests:**
- `packages/financial-reporting/tests/Feature/ManagementReportTest.php` - Management reports tests
- `packages/financial-reporting/tests/Feature/ReportSchedulingTest.php` - Scheduling tests
- `packages/financial-reporting/tests/Feature/ConsolidationReportTest.php` - Consolidation tests
- `packages/financial-reporting/tests/Feature/ReportExportTest.php` - Export tests
- `packages/financial-reporting/tests/Feature/BIIntegrationTest.php` - BI API tests

## 6. Testing

- **TEST-001**: Generate departmental P&L for department with sub-departments, verify all included
- **TEST-002**: Generate cost center analysis with budget comparison, verify variance calculations
- **TEST-003**: Generate consolidation report for group of 3 companies, verify totals aggregated
- **TEST-004**: Consolidation with intercompany elimination, verify matching transactions removed
- **TEST-005**: Create report schedule with daily frequency, verify next_run_at calculated correctly
- **TEST-006**: Execute scheduled report immediately, verify email sent to all recipients
- **TEST-007**: Process due schedules command, verify all due schedules executed
- **TEST-008**: Export report to PDF, verify file created with proper formatting
- **TEST-009**: Export report to Excel, verify worksheet created with correct data and formatting
- **TEST-010**: Export report to CSV, verify flat structure with all data rows
- **TEST-011**: BI API authentication with valid API key, expect data returned
- **TEST-012**: BI API authentication with invalid API key, expect 401 Unauthorized
- **TEST-013**: BI API rate limiting, exceed 1000 requests/hour, expect 429 Too Many Requests
- **TEST-014**: Unauthorized access to department report (user not in department), expect 403

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Email delivery failures could cause scheduled reports not to reach recipients
  - *Mitigation*: Implement retry logic with exponential backoff, log delivery status, notify administrator of failures
- **RISK-002**: Large PDF exports (>50 pages) could timeout or exhaust memory
  - *Mitigation*: Enforce page limit (CON-003), use streaming PDF generation, queue large exports as background jobs
- **RISK-003**: Consolidation logic could be incorrect for complex ownership structures
  - *Mitigation*: Comprehensive testing with various scenarios, manual verification against accounting standards, clear documentation
- **RISK-004**: BI API keys could be compromised if not properly secured
  - *Mitigation*: Hash API keys, enforce HTTPS, implement key rotation, rate limiting, access logging

**Assumptions:**
- **ASSUMPTION-001**: Companies in consolidation group use consistent chart of accounts for mapping
- **ASSUMPTION-002**: Intercompany transactions are properly tagged in GL with counterparty company ID
- **ASSUMPTION-003**: Users have configured SMTP settings for email delivery
- **ASSUMPTION-004**: BI tools (Power BI, Tableau, Looker) can authenticate via API key and consume JSON data
- **ASSUMPTION-005**: Scheduled reports typically run during off-peak hours (night/weekends) to avoid performance impact

## 8. KIV for future implementations

- **KIV-001**: Support XBRL export format for regulatory compliance (SEC filing)
- **KIV-002**: Implement report versioning with rollback capability
- **KIV-003**: Add natural language report generation ("Generate Q3 revenue by region")
- **KIV-004**: Implement advanced consolidation features (currency translation, minority interest)
- **KIV-005**: Add report collaboration features (comments, annotations, approvals)
- **KIV-006**: Implement report template marketplace (pre-built industry-specific reports)
- **KIV-007**: Add AI-powered anomaly detection in management reports
- **KIV-008**: Support embedded Power BI/Tableau dashboards via iframe embedding

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md](../prd/prd-01/PRD01-SUB20-FINANCIAL-REPORTING.md)
- Related PLAN: [PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md](PRD01-SUB20-PLAN01-implement-financial-reporting-foundation.md)
- Related PLAN: [PRD01-SUB20-PLAN02-implement-financial-reporting-api-comparison.md](PRD01-SUB20-PLAN02-implement-financial-reporting-api-comparison.md)
- Related PLAN: [PRD01-SUB20-PLAN03-implement-custom-reports-dashboards.md](PRD01-SUB20-PLAN03-implement-custom-reports-dashboards.md)
- Related Sub-PRD: [../prd/prd-01/PRD01-SUB15-BACKOFFICE.md](../prd/prd-01/PRD01-SUB15-BACKOFFICE.md) - Department and cost center data
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- DomPDF Documentation: https://github.com/dompdf/dompdf
- PhpSpreadsheet Documentation: https://phpspreadsheet.readthedocs.io/
