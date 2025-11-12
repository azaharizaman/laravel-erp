---
plan: Implement Workflow Visual Designer and Monitoring
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, workflow-engine, visual-designer, monitoring, analytics, workflow-templates, bpmn]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan completes the Workflow Engine module by adding a visual workflow designer API, workflow templates, comprehensive monitoring and analytics, and integration testing with all transactional modules. It enables users to design workflows through a structured API (supporting future UI), provides pre-built templates for common approval scenarios, and delivers detailed analytics on workflow performance and bottlenecks.

## 1. Requirements & Constraints

### Requirements

- **REQ-FR-WF-001**: Provide visual workflow designer for creating approval chains
- **REQ-FR-WF-007**: Support workflow templates for common approval patterns
- **REQ-FR-WF-009**: Provide workflow analytics dashboard with performance metrics
- **REQ-IR-WF-001**: Integrate with all transactional modules for approval workflows
- **REQ-DR-WF-002**: Maintain workflow instance state tracking current step and history
- **REQ-PR-WF-002**: Support 1,000+ concurrent workflow instances
- **REQ-ARCH-WF-001**: Use SQL for workflow definitions with JSON configuration

### Security Constraints

- **SEC-001**: Workflow designer API must validate all workflow configurations
- **SEC-002**: Analytics must respect tenant isolation and role-based data access
- **SEC-003**: Template cloning must enforce tenant ownership
- **SEC-004**: Monitoring dashboards must not expose sensitive workflow data to unauthorized users

### Guidelines

- **GUD-001**: All PHP files must include `declare(strict_types=1);`
- **GUD-002**: Use Laravel 12+ conventions for all implementations
- **GUD-003**: Follow PSR-12 coding standards, enforced by Laravel Pint
- **GUD-004**: Designer API should support future visual UI development
- **GUD-005**: Analytics queries must be optimized for performance

### Patterns to Follow

- **PAT-001**: Use Builder pattern for workflow definition construction
- **PAT-002**: Use Template Method pattern for workflow templates
- **PAT-003**: Use Facade pattern for simplified workflow designer API
- **PAT-004**: Use Repository pattern for analytics data access
- **PAT-005**: Use DTO pattern for analytics metrics representation

### Constraints

- **CON-001**: Workflow templates limited to 20 predefined templates
- **CON-002**: Analytics dashboard queries must return within 2 seconds
- **CON-003**: Designer API validation must complete within 500ms
- **CON-004**: Workflow definition JSON size limited to 100KB
- **CON-005**: Analytics data retention limited to 12 months

## 2. Implementation Steps

### GOAL-001: Workflow Designer API Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-001, ARCH-WF-001, SEC-001 | Create structured API for workflow definition management with comprehensive validation supporting future visual designer UI. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create `src/Contracts/WorkflowBuilderContract.php` interface: Define methods: `create(string $name, string $entityType): WorkflowBuilder` (starts building new workflow), `addStep(string $name, array $approver, array $options = []): self` (adds approval step), `setRoutingType(string $type): self` (sets sequential/parallel/conditional), `addCondition(int $stepNumber, array $condition): self` (adds routing condition), `setEscalationRule(int $stepNumber, array $rule): self` (adds escalation), `validate(): bool` (validates complete definition), `save(): WorkflowDefinition` (persists to database), `toArray(): array` (exports as array), `fromArray(array $definition): self` (imports from array). All methods with fluent interface for chaining. Full PHPDoc. | | |
| TASK-002 | Create `src/Services/WorkflowBuilder.php` implementing `WorkflowBuilderContract`: Include `declare(strict_types=1);`. Inject `WorkflowDefinitionRepository`, `Validator`. Implement fluent builder pattern: maintain internal state (steps array, routing_type, conditions, escalation_rules). Implement `addStep()`: validate step configuration (approver_role_id or approver_user_id required, step_name max 255 chars), add to steps array with auto-increment step_number. Implement `validate()`: check minimum 1 step, maximum 50 steps (CON from PLAN01), validate all references (role_ids, user_ids exist), validate condition syntax, validate escalation rules, ensure no circular dependencies. Throw `WorkflowValidationException` with detailed errors. | | |
| TASK-003 | Implement `save()` in `WorkflowBuilder`: Generate workflow definition code (auto-increment: WF-001, WF-002, etc.). Validate complete workflow via `validate()`. Create WorkflowDefinition record with steps_config from internal state, escalation_rules, routing_type. Set created_by from authenticated user. Dispatch `WorkflowDefinitionCreatedEvent`. Clear internal state after save. Return created WorkflowDefinition. Use database transaction. Validate JSON size < 100KB (CON-004). Log creation for audit trail. | | |
| TASK-004 | Create `src/Http/Controllers/Api/V1/WorkflowDesignerController.php`: Include `declare(strict_types=1);`. Implement methods: `create(CreateWorkflowRequest $request): JsonResponse` (creates workflow via builder), `update(UpdateWorkflowRequest $request, int $id): JsonResponse` (updates definition), `validate(ValidateWorkflowRequest $request): JsonResponse` (validates without saving), `preview(int $id): JsonResponse` (returns visual representation for UI), `clone(int $id): JsonResponse` (clones existing workflow), `export(int $id): Response` (exports as JSON file), `import(ImportWorkflowRequest $request): JsonResponse` (imports from JSON). Apply auth:sanctum and tenant middleware. Check 'manage-workflows' permission. Return WorkflowDesignerResource. | | |
| TASK-005 | Create Form Requests: `CreateWorkflowRequest.php` (validation: name required|max:255|unique per tenant, entity_type required|string|max:100, routing_type required|in:sequential,parallel,conditional, steps required|array|min:1|max:50, steps.*.step_name required|string|max:255, steps.*.approver_role_id or approver_user_id required, conditions nullable|array|max:10, escalation_rules nullable|array|max:5), `UpdateWorkflowRequest.php` (same validation, all optional except id), `ValidateWorkflowRequest.php` (same as create, returns validation result without saving). Authorization checks 'manage-workflows' permission. Custom validation ensures steps numbering sequential, references valid. | | |
| TASK-006 | Create `src/Http/Resources/WorkflowDesignerResource.php`: Transform WorkflowDefinition to designer-friendly JSON format: Return array with keys: id, code, name, entity_type, routing_type, steps (array with step_number, step_name, approver, conditions, position for visual layout), escalation_rules, is_active, validation_errors (if any), metadata (step_count, has_conditions, has_escalations), links (self, execute, preview, clone, export), visual_representation (nodes and edges for graph rendering). Include JSON schema documentation for frontend developers. Format supports drag-and-drop visual designer UI development. | | |

### GOAL-002: Workflow Templates System

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-007, CON-001 | Implement pre-built workflow templates for common approval patterns with cloning and customization capabilities. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/Models/WorkflowTemplate.php` Eloquent model: Include `declare(strict_types=1);`. Use traits: `Searchable`, `LogsActivity`. Define fillable: code (unique globally), name, description, category (e.g., 'purchasing', 'expenses', 'hr'), entity_type, template_definition (JSONB with steps structure), is_system_template (boolean, true for predefined), tags (JSONB array), created_by (nullable for system templates). Define casts appropriately. Add relationships: belongsTo(User, 'created_by'). Add scopes: scopeSystem(), scopeByCategory($category). Add method: `instantiate(int $tenantId): WorkflowDefinition` creates workflow definition from template. Note: Templates are global (no tenant_id), but instantiated workflows are tenant-specific. | | |
| TASK-008 | Create migration `database/migrations/create_workflow_templates_table.php`: Define `workflow_templates` table with columns: id (BIGSERIAL), code (VARCHAR 50, unique), name (VARCHAR 255), description (TEXT), category (VARCHAR 100, indexed), entity_type (VARCHAR 100), template_definition (JSONB, NOT NULL), is_system_template (BOOLEAN, default false), tags (JSONB, nullable), created_by (BIGINT, nullable FK to users.id), created_at, updated_at. Add index on (category, is_system_template). Add index on (entity_type). No tenant_id as templates are global. System templates have created_by=null. | | |
| TASK-009 | Create `database/seeders/WorkflowTemplatesSeeder.php`: Seed 10-15 common workflow templates: 1) 'Simple Two-Step Approval' (Manager -> Director), 2) 'Three-Tier Approval' (Supervisor -> Manager -> Director), 3) 'Amount-Based Purchase Order' (< $10k: Manager, >= $10k: Director), 4) 'Expense Claim Approval' (Manager -> Finance), 5) 'Leave Request Approval' (Manager -> HR), 6) 'Budget Approval' (Department Head -> CFO -> CEO), 7) 'Invoice Approval' (AP Manager -> Controller), 8) 'Contract Approval' (Manager -> Legal -> Director), 9) 'Capital Expenditure' (Manager -> Director -> CFO -> CEO), 10) 'Travel Request' (Manager -> Travel Coordinator). Each template with proper steps_config, conditions, escalation_rules. Set is_system_template=true. Provide descriptions and tags. Run seeder in service provider or installation command. | | |
| TASK-010 | Create `src/Contracts/WorkflowTemplateServiceContract.php` interface: Define methods: `listTemplates(array $filters = []): Collection` (lists available templates), `getTemplate(int $id): WorkflowTemplate`, `instantiateTemplate(int $templateId, int $tenantId, array $customizations = []): WorkflowDefinition` (creates workflow from template), `createCustomTemplate(array $data): WorkflowTemplate` (creates tenant-specific template), `cloneTemplate(int $templateId, array $modifications = []): WorkflowTemplate` (clones and modifies template). All methods with PHPDoc. | | |
| TASK-011 | Create `src/Services/WorkflowTemplateService.php` implementing `WorkflowTemplateServiceContract`: Implement `instantiateTemplate()`: 1) Load template by ID, 2) Extract template_definition, 3) Apply customizations (replace role_ids, adjust conditions, modify escalation rules), 4) Use WorkflowBuilder to create workflow definition, 5) Set tenant_id, 6) Validate and save, 7) Return WorkflowDefinition. Customizations format: {name: 'Custom Name', steps: [{step_number: 1, approver_role_id: 5}], conditions: [...]}. Log template usage for analytics. Validate customizations don't break workflow logic. | | |
| TASK-012 | Create `src/Http/Controllers/Api/V1/WorkflowTemplateController.php`: Implement methods: `index(Request $request): JsonResponse` (list templates with filtering by category, entity_type), `show(int $id): JsonResponse` (get template details), `instantiate(InstantiateTemplateRequest $request, int $id): JsonResponse` (creates workflow from template), `preview(int $id): JsonResponse` (shows template structure). Apply auth middleware. No special permissions required for viewing system templates. Return WorkflowTemplateResource. Templates accessible to all tenants for instantiation (SEC-003: cloned workflow inherits tenant). | | |

### GOAL-003: Workflow Analytics Foundation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-009, PR-WF-002, CON-002 | Implement workflow analytics service with performance metrics, bottleneck identification, and approval time tracking. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Create `src/DTOs/WorkflowAnalyticsDTO.php`: Define data transfer object with properties: total_workflows (int), active_workflows (int), completed_workflows (int), rejected_workflows (int), cancelled_workflows (int), average_completion_time_hours (float), median_completion_time_hours (float), approval_rate_percentage (float), rejection_rate_percentage (float), overdue_workflows (int), workflows_by_entity_type (array), workflows_by_status (array), top_bottlenecks (array with step_name, average_wait_time, occurrence_count), approver_performance (array with user_name, approvals_count, average_response_time). Use readonly properties (PHP 8.2). Factory method `fromAggregateData(array $data): self`. | | |
| TASK-014 | Create `src/Contracts/WorkflowAnalyticsServiceContract.php` interface: Define methods: `getOverview(int $tenantId, ?Carbon $startDate = null, ?Carbon $endDate = null): WorkflowAnalyticsDTO` (overall metrics), `getEntityTypeBreakdown(int $tenantId, string $period = 'month'): array` (breakdown by entity type), `getApproverPerformance(int $tenantId, ?int $userId = null): Collection` (approver stats), `getBottleneckAnalysis(int $tenantId): array` (identifies workflow steps causing delays), `getCompletionTrends(int $tenantId, string $period = 'month', int $months = 12): array` (time series data), `exportMetrics(int $tenantId, array $options = []): string` (CSV export). All methods with PHPDoc. | | |
| TASK-015 | Create `src/Services/WorkflowAnalyticsService.php` implementing `WorkflowAnalyticsServiceContract`: Inject `WorkflowInstanceRepository`, `WorkflowStepRepository`, `Cache`. Implement `getOverview()`: Query aggregations from workflow_instances and workflow_steps tables. Count by status. Calculate average/median completion time: (completed_at - initiated_at) for completed workflows. Calculate approval rate: approved / (approved + rejected) * 100. Identify overdue: steps where status='pending' AND due_at < now(). Group by entity_type. Cache results for 5 minutes. Return WorkflowAnalyticsDTO. Queries must complete < 2 seconds (CON-002). Use database indexes for optimization. | | |
| TASK-016 | Implement `getBottleneckAnalysis()` in `WorkflowAnalyticsService`: Query workflow_steps where status='completed'. Calculate wait time: completed_at - assigned_at for each step. Group by step_name (or step_number if names not unique). Calculate: average_wait_time, max_wait_time, occurrence_count. Order by average_wait_time DESC. Return top 10 bottlenecks. Include step details: workflow_definition_name, entity_type, approver_role. Identify steps consistently taking > 48 hours. Provide recommendations: "Step 'CFO Approval' averages 72 hours - consider adding deputy approver". Cache for 1 hour. | | |
| TASK-017 | Implement `getApproverPerformance()` in `WorkflowAnalyticsService`: Query approval_actions joined with users and workflow_steps. For each approver, calculate: total_approvals (count of approve actions), total_rejections (count of reject actions), average_response_time (time from step assigned_at to action performed_at), approval_rate (approvals / total * 100), current_pending_count (steps assigned to user with status='pending'). If userId provided, filter to specific user. Order by total_approvals DESC. Return Collection of arrays with user details and performance metrics. Support manager view (see team performance) vs individual view. | | |
| TASK-018 | Create database indexes for analytics performance: Create index on workflow_instances(tenant_id, status, completed_at) for completion metrics. Create index on workflow_instances(tenant_id, entity_type, initiated_at) for entity type breakdown. Create index on workflow_steps(status, assigned_at, completed_at) for bottleneck analysis. Create index on approval_actions(performed_by, performed_at) for approver performance. Document index strategy in migration comments. Test analytics queries with 10,000+ workflow_instance records. Use EXPLAIN ANALYZE to validate index usage. Add composite indexes where beneficial. | | |

### GOAL-004: Analytics Dashboard API

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-WF-009, SEC-002, SEC-004 | Create RESTful API endpoints for workflow analytics dashboards with role-based access and proper data filtering. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-019 | Create `src/Http/Controllers/Api/V1/WorkflowAnalyticsController.php`: Include `declare(strict_types=1);`. Implement methods: `overview(AnalyticsRequest $request): JsonResponse` (get overview metrics), `entityTypeBreakdown(AnalyticsRequest $request): JsonResponse` (breakdown by entity), `approverPerformance(AnalyticsRequest $request): JsonResponse` (approver stats), `bottlenecks(Request $request): JsonResponse` (bottleneck analysis), `completionTrends(TrendsRequest $request): JsonResponse` (time series), `export(ExportAnalyticsRequest $request): Response` (CSV export). Apply auth:sanctum and tenant middleware. Check 'view-workflow-analytics' permission. Support filtering by date_from, date_to, entity_type, user_id. Return AnalyticsResource. | | |
| TASK-020 | Create Form Requests: `AnalyticsRequest.php` (validation: date_from nullable|date, date_to nullable|date|after_or_equal:date_from, entity_type nullable|string, user_id nullable|exists:users,id, period nullable|in:day,week,month,quarter,year), `TrendsRequest.php` (validation: period required|in:day,week,month, months nullable|integer|min:1|max:24), `ExportAnalyticsRequest.php` (validation: format required|in:csv,excel, date_from required|date, date_to required|date). Authorization checks 'view-workflow-analytics' permission. For user_id filter, check user has permission to view that user's data (manager can view team, admin can view all). | | |
| TASK-021 | Create `src/Http/Resources/WorkflowAnalyticsResource.php`: Transform analytics data to JSON format: Return array with keys: summary (overall metrics from DTO), charts_data (formatted for Chart.js/similar libraries with labels and datasets), tables_data (tabular data for data grids), trends (time series arrays), recommendations (array of suggested actions based on analytics), metadata (query_time_ms, cached boolean, data_as_of timestamp). Format numbers with appropriate precision. Include visual indicators for KPIs (good/warning/critical thresholds). Support multiple chart types (line, bar, pie, donut). | | |
| TASK-022 | Implement caching strategy for analytics: Use Redis cache with hierarchical keys: 'analytics:{tenant_id}:overview:{date_range_hash}', 'analytics:{tenant_id}:bottlenecks', etc. Set TTL based on data freshness requirements: overview 5 minutes, bottlenecks 1 hour, trends 4 hours. Invalidate cache on workflow completion events. Implement cache warming for frequently accessed metrics. Add cache hit rate monitoring. Provide ?force_refresh query parameter to bypass cache. Log cache performance metrics. Balance freshness vs performance per CON-002. | | |

### GOAL-005: Integration Testing and Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| IR-WF-001, PR-WF-002 | Create comprehensive integration tests with transactional modules and complete workflow engine documentation. | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-023 | Create Feature test `tests/Feature/WorkflowDesignerTest.php`: Use Pest syntax. Test scenarios: 1) Create workflow via builder API (expect workflow created with correct steps), 2) Add conditional routing to workflow (expect conditions saved), 3) Validate invalid workflow definition (expect validation errors), 4) Clone existing workflow (expect duplicate with modifications), 5) Export workflow as JSON (expect valid JSON structure), 6) Import workflow from JSON (expect workflow created), 7) Update workflow definition (expect changes saved, active instances unaffected), 8) Designer API validation completes < 500ms (CON-003). Use factories for test data. Assert JSON structure matches schema. | | |
| TASK-024 | Create Feature test `tests/Feature/WorkflowTemplateTest.php`: Test scenarios: 1) List available templates (expect system templates returned), 2) Instantiate template for tenant (expect workflow definition created with tenant_id), 3) Customize template during instantiation (expect customizations applied), 4) Create custom template (expect template saved, is_system_template=false), 5) Clone template with modifications (expect new template created), 6) Template instantiation with invalid customizations (expect validation error), 7) Cross-tenant template access (expect templates accessible to all tenants for instantiation). Assert template structure and instantiated workflows. | | |
| TASK-025 | Create Feature test `tests/Feature/WorkflowAnalyticsTest.php`: Test scenarios: 1) Get overview metrics (expect correct counts and calculations), 2) Entity type breakdown (expect grouped data), 3) Approver performance (expect user stats), 4) Bottleneck analysis (expect steps with high wait times identified), 5) Completion trends (expect time series data), 6) Analytics query < 2 seconds with 1000+ workflows (CON-002), 7) Cache behavior (second request faster), 8) Role-based analytics access (manager sees team, admin sees all), 9) Export analytics to CSV (expect valid CSV file). Use database seeding for large dataset. Assert calculation accuracy. | | |
| TASK-026 | Create Integration test `tests/Integration/WorkflowModuleIntegrationTest.php`: Test workflow integration with transactional modules: 1) Purchase Order approval workflow (create PO, start workflow, approve steps, PO status updated), 2) Expense Claim workflow (create expense, workflow routes based on amount), 3) Journal Entry approval (create JE, workflow with accounting role validation), 4) Concurrent workflows (start 100 workflows simultaneously, all complete successfully per PR-WF-002), 5) Workflow with escalation and delegation (complete flow end-to-end), 6) Template-based workflow creation (use template, modify, execute). Simulate real business scenarios. Assert data consistency across modules. | | |
| TASK-027 | Create Performance test `tests/Feature/WorkflowEnginePerformanceTest.php`: Test scenarios: 1) Support 1000 concurrent workflow instances (PR-WF-002), 2) Workflow routing decision < 100ms (from PLAN02), 3) Escalation check for 1000 overdue steps completes within acceptable time, 4) Inbox query < 500ms for 1000 items (from PLAN03), 5) Designer API validation < 500ms (CON-003), 6) Analytics query < 2 seconds (CON-002), 7) Bulk operations (approve 100 items) complete within 10 seconds. Use database seeding for scale testing. Measure with microtime(). Tag with @group performance. Compare with and without caching. | | |
| TASK-028 | Create comprehensive documentation `packages/workflow-engine/README.md`: Document workflow engine features, architecture, usage examples. Sections: Introduction, Installation, Configuration, Workflow Definition (JSON structure, routing types, conditions, escalation rules), Workflow Execution (starting, approving, rejecting, delegating), Workflow Templates (using templates, customization), Analytics (metrics, dashboard), API Reference (endpoints, request/response formats), Integration Guide (integrating with modules), Performance Tuning (caching, indexing, optimization), Troubleshooting (common issues, debugging). Include code examples in PHP and API cURL requests. Document all configuration options. Provide migration guide for workflow definition changes. | | |
| TASK-029 | Create API documentation using OpenAPI/Swagger specification: Document all workflow engine endpoints in `packages/workflow-engine/docs/openapi.yaml`: WorkflowDesignerController endpoints (create, update, validate, clone, export, import), WorkflowInboxController endpoints (inbox, approve, reject, bulk operations), WorkflowStatusController endpoints (status, history, timeline), WorkflowTemplateController endpoints (list, instantiate), WorkflowAnalyticsController endpoints (overview, performance, bottlenecks), DelegationController endpoints (create, revoke, list). Include request/response schemas, authentication requirements, error responses. Generate API docs using Swagger UI or similar tool. | | |

## 3. Alternatives

- **ALT-001**: Use BPMN 2.0 XML format for workflow definitions instead of custom JSON
  - *Pros*: Industry standard, compatible with external tools (Camunda, etc.), rich notation
  - *Cons*: Overkill for simple approvals, complexity, harder to work with in Laravel
  - *Decision*: Not chosen for MVP - Custom JSON simpler for ERP approval workflows; BPMN can be added later

- **ALT-002**: Build visual designer UI as part of Laravel package (Vue.js components)
  - *Pros*: Integrated solution, single package
  - *Cons*: Violates headless architecture, limits frontend flexibility, increases package size
  - *Decision*: Not chosen - API-only approach allows any frontend implementation

- **ALT-003**: Use dedicated analytics service (Elasticsearch, ClickHouse) for workflow analytics
  - *Pros*: Better performance for analytics queries, real-time aggregations, scalability
  - *Cons*: Additional infrastructure, cost, complexity, overkill for MVP
  - *Decision*: Deferred - SQL with caching sufficient for MVP; can migrate to analytics DB later if needed

- **ALT-004**: Store workflow templates in files (YAML/JSON) instead of database
  - *Pros*: Version controlled, easier to review changes, simpler deployment
  - *Cons*: Not runtime-editable, no custom templates, harder to query/search
  - *Decision*: Not chosen - Database storage allows custom templates and better querying

## 4. Dependencies

**Package Dependencies:**
- `azaharizaman/erp-workflow-engine` (PLAN01-03) - Foundation, routing, escalation, inbox required
- `azaharizaman/erp-multitenancy` (PRD01-SUB01) - Tenant context
- `azaharizaman/erp-authentication` (PRD01-SUB02) - User roles and permissions
- `azaharizaman/erp-audit-logging` (PRD01-SUB03) - Workflow audit trail
- All transactional modules (Purchasing, Expenses, etc.) - Workflow integration points

**Internal Dependencies:**
- PLAN01: WorkflowDefinition, WorkflowInstance, WorkflowExecutorService
- PLAN02: ConditionalRoutingService, DelegationService
- PLAN03: EscalationService, WorkflowInboxRepository
- Chart.js or similar library for frontend visualization (optional, frontend responsibility)

**Infrastructure Dependencies:**
- Redis for analytics caching (recommended)
- Database with analytics indexes for query performance

## 5. Files

**Models:**
- `packages/workflow-engine/src/Models/WorkflowTemplate.php` - Workflow template model

**Migrations:**
- `packages/workflow-engine/database/migrations/create_workflow_templates_table.php` - Templates schema
- `packages/workflow-engine/database/migrations/add_analytics_indexes.php` - Performance indexes

**DTOs:**
- `packages/workflow-engine/src/DTOs/WorkflowAnalyticsDTO.php` - Analytics metrics DTO

**Contracts:**
- `packages/workflow-engine/src/Contracts/WorkflowBuilderContract.php` - Builder interface
- `packages/workflow-engine/src/Contracts/WorkflowTemplateServiceContract.php` - Template service interface
- `packages/workflow-engine/src/Contracts/WorkflowAnalyticsServiceContract.php` - Analytics service interface

**Services:**
- `packages/workflow-engine/src/Services/WorkflowBuilder.php` - Workflow builder implementation
- `packages/workflow-engine/src/Services/WorkflowTemplateService.php` - Template management
- `packages/workflow-engine/src/Services/WorkflowAnalyticsService.php` - Analytics logic

**Controllers:**
- `packages/workflow-engine/src/Http/Controllers/Api/V1/WorkflowDesignerController.php` - Designer API
- `packages/workflow-engine/src/Http/Controllers/Api/V1/WorkflowTemplateController.php` - Template API
- `packages/workflow-engine/src/Http/Controllers/Api/V1/WorkflowAnalyticsController.php` - Analytics API

**Form Requests:**
- `packages/workflow-engine/src/Http/Requests/CreateWorkflowRequest.php` - Create workflow validation
- `packages/workflow-engine/src/Http/Requests/UpdateWorkflowRequest.php` - Update workflow validation
- `packages/workflow-engine/src/Http/Requests/ValidateWorkflowRequest.php` - Workflow validation request
- `packages/workflow-engine/src/Http/Requests/InstantiateTemplateRequest.php` - Template instantiation validation
- `packages/workflow-engine/src/Http/Requests/AnalyticsRequest.php` - Analytics query validation
- `packages/workflow-engine/src/Http/Requests/TrendsRequest.php` - Trends query validation
- `packages/workflow-engine/src/Http/Requests/ExportAnalyticsRequest.php` - Export validation

**API Resources:**
- `packages/workflow-engine/src/Http/Resources/WorkflowDesignerResource.php` - Designer response transformation
- `packages/workflow-engine/src/Http/Resources/WorkflowTemplateResource.php` - Template transformation
- `packages/workflow-engine/src/Http/Resources/WorkflowAnalyticsResource.php` - Analytics transformation

**Seeders:**
- `packages/workflow-engine/database/seeders/WorkflowTemplatesSeeder.php` - Predefined templates

**Tests:**
- `packages/workflow-engine/tests/Feature/WorkflowDesignerTest.php` - Designer tests
- `packages/workflow-engine/tests/Feature/WorkflowTemplateTest.php` - Template tests
- `packages/workflow-engine/tests/Feature/WorkflowAnalyticsTest.php` - Analytics tests
- `packages/workflow-engine/tests/Integration/WorkflowModuleIntegrationTest.php` - Integration tests
- `packages/workflow-engine/tests/Feature/WorkflowEnginePerformanceTest.php` - Performance tests

**Documentation:**
- `packages/workflow-engine/README.md` - Complete workflow engine documentation
- `packages/workflow-engine/docs/openapi.yaml` - OpenAPI/Swagger API specification

## 6. Testing

- **TEST-001**: Create workflow via builder API, verify workflow saved with correct configuration
- **TEST-002**: Validate invalid workflow definition, expect validation errors with details
- **TEST-003**: Clone existing workflow, verify duplicate created with modifications applied
- **TEST-004**: Export workflow as JSON, verify valid JSON structure matches schema
- **TEST-005**: Import workflow from JSON file, verify workflow created correctly
- **TEST-006**: Designer API validation completes < 500ms (CON-003)
- **TEST-007**: Instantiate workflow template, verify workflow definition created for tenant
- **TEST-008**: Customize template during instantiation, verify customizations applied correctly
- **TEST-009**: Get analytics overview, verify metrics calculations (approval rate, completion time) correct
- **TEST-010**: Bottleneck analysis identifies steps with high average wait times
- **TEST-011**: Approver performance metrics calculated correctly (response time, approval rate)
- **TEST-012**: Analytics query completes < 2 seconds with 1000+ workflows (CON-002)
- **TEST-013**: Integration: Purchase Order workflow completes end-to-end, PO status updated
- **TEST-014**: Support 1000 concurrent workflow instances (PR-WF-002)
- **TEST-015**: Workflow engine performance: routing < 100ms, escalation processing efficient, inbox < 500ms

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Visual designer API may not cover all UI requirements without iterations
  - *Mitigation*: Design API with extensibility in mind, gather frontend developer feedback early, provide comprehensive documentation
- **RISK-002**: Analytics queries could slow down with very large datasets (100k+ workflows)
  - *Mitigation*: Aggressive caching, database indexing, query optimization, consider analytics database if needed
- **RISK-003**: Workflow templates may not fit all business scenarios
  - *Mitigation*: Provide template customization, allow custom templates, gather feedback on missing patterns
- **RISK-004**: Integration testing complexity across all transactional modules
  - *Mitigation*: Prioritize core modules (Purchasing, Expenses), test common workflows first, add more integration tests iteratively

**Assumptions:**
- **ASSUMPTION-001**: Frontend developers will build visual designer UI consuming the designer API
- **ASSUMPTION-002**: Common approval patterns captured in 10-15 templates cover 80% of use cases
- **ASSUMPTION-003**: Workflow analytics sufficient with 12-month data retention (CON-005)
- **ASSUMPTION-004**: Analytics dashboard accessed by managers and administrators, not all users
- **ASSUMPTION-005**: Workflow definition JSON structure stable after MVP (minimal breaking changes)

## 8. KIV for future implementations

- **KIV-001**: BPMN 2.0 support for interoperability with external workflow engines
- **KIV-002**: Visual workflow designer frontend (Vue.js/React components)
- **KIV-003**: Workflow simulation mode (test workflows without actual approvals)
- **KIV-004**: AI-powered workflow optimization recommendations based on analytics
- **KIV-005**: Workflow versioning with rollback and A/B testing capabilities
- **KIV-006**: Mobile workflow approval app with offline capability
- **KIV-007**: Advanced analytics: predictive completion time, anomaly detection
- **KIV-008**: Workflow marketplace for sharing templates across organizations

## 9. Related PRD / Further Reading

- Master PRD: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- Sub-PRD: [../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md](../prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md)
- Related PLAN: [PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md](PRD01-SUB21-PLAN01-implement-workflow-engine-foundation.md)
- Related PLAN: [PRD01-SUB21-PLAN02-implement-conditional-routing-delegation.md](PRD01-SUB21-PLAN02-implement-conditional-routing-delegation.md)
- Related PLAN: [PRD01-SUB21-PLAN03-implement-escalation-workflow-inbox.md](PRD01-SUB21-PLAN03-implement-escalation-workflow-inbox.md)
- Coding Guidelines: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
- Builder Pattern: https://refactoring.guru/design-patterns/builder
- OpenAPI Specification: https://swagger.io/specification/
