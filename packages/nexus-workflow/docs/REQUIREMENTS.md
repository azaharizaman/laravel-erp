# nexus-workflow Package Requirements

**Package Name:** `azaharizaman/nexus-workflow`  
**Namespace:** `Nexus\Workflow`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 14, 2025

---

## Executive Summary

Complete Workflow Management package integrating workflow execution logic (stateless computation of state transitions, rule evaluation, approval logic, escalation triggers) with state persistence (tracking status, history, users, instance data of running workflows).

### Architectural Rationale

**Consolidated From:** PRD01-SUB21-WORKFLOW-ENGINE.md (originally split into Engine + Management)

**Why Consolidated:**
Workflow engine and state management are consolidated because they:
1. **Tightly coupled** - Engine requires state, state requires engine
2. **Always deployed together** - Cannot use engine without persistence
3. **Single deployment unit** - No practical benefit to separate packages
4. **Shared domain context** - Both deal with workflow execution lifecycle

**Internal Modularity:**
Maintained through:
- Namespace separation (`Nexus\Workflow\Engine`, `Nexus\Workflow\Management`)
- Clear interface boundaries between stateless computation and state persistence
- Domain events for lifecycle notifications

---

## Functional Requirements

**Source:** PRD01-SUB21-WORKFLOW-ENGINE.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-WF-001** | Provide **visual workflow designer** for creating approval chains | High |
| **FR-WF-002** | Support **multi-level approval routing** with parallel and sequential flows | High |
| **FR-WF-003** | Support **conditional routing** based on transaction amount, type, or custom rules | High |
| **FR-WF-004** | Implement **escalation rules** for overdue approvals with deadline enforcement | High |
| **FR-WF-005** | Support **delegation of approval authority** with time-bound assignments | Medium |
| **FR-WF-006** | Provide **workflow status tracking** with real-time progress visualization | High |
| **FR-WF-007** | Support **workflow templates** for common approval patterns (PO, expense, invoice) | Medium |
| **FR-WF-008** | Provide **workflow inbox** for pending approvals with filtering and sorting | High |
| **FR-WF-009** | **Persist workflow instance state** tracking current step and full history | High |
| **FR-WF-010** | Prevent **duplicate workflow instances** for the same document | High |

---

## Business Rules

| Rule ID | Description |
|---------|-------------|
| **BR-WF-001** | Approvals must be executed in **sequential order** unless parallel routing is enabled |
| **BR-WF-002** | Approvers cannot approve their **own submissions** |
| **BR-WF-003** | Escalations occur **automatically** when approval deadlines are exceeded |
| **BR-WF-004** | Workflow state changes MUST be **ACID-compliant** transactions |

---

## Data Requirements

| Requirement ID | Description |
|----------------|-------------|
| **DR-WF-001** | Store **workflow definitions** with routing rules and conditions |
| **DR-WF-002** | Maintain **workflow instance state** tracking current step and history |
| **DR-WF-003** | Track **approval actions** with timestamps, comments, and attachments |
| **DR-WF-004** | Store **delegation records** with start/end dates and delegator/delegatee |

---

## Integration Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **IR-WF-001** | Integrate with **all transactional modules** for approval workflows | High |
| **IR-WF-002** | Integrate with **nexus-notification-service** for approval notifications | High |
| **IR-WF-003** | Support **external workflow triggers** via API for third-party systems | Medium |

---

## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-WF-001** | Workflow state transition | < 100ms |
| **PR-WF-002** | Approval inbox query (1000 pending) | < 500ms |
| **PR-WF-003** | Workflow instance creation | < 200ms |

---

## Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-WF-001** | Enforce **role-based approval rights** based on user roles |
| **SR-WF-002** | Implement **audit logging** for all approval actions |
| **SR-WF-003** | Prevent **approval of own submissions** at code level |
| **SR-WF-004** | Enforce **tenant isolation** for all workflow data |

---

## Dependencies

**Mandatory Package Dependencies:**
- `azaharizaman/nexus-tenancy` - Multi-tenancy isolation
- `azaharizaman/nexus-audit-log` - Change tracking
- `azaharizaman/nexus-notification-service` - Approval notifications

**Optional Package Dependencies:**
- All transactional packages that require approval workflows

**Framework Dependencies:**
- Laravel Framework ≥ 12.x
- PHP ≥ 8.2
- PostgreSQL or MySQL

---

## Implementation Notes

### Internal Package Structure

```
packages/nexus-workflow/
├── src/
│   ├── Engine/                    # Stateless computation
│   │   ├── Services/
│   │   │   ├── StateTransitionService.php
│   │   │   ├── RuleEvaluationService.php
│   │   │   ├── ApprovalLogicService.php
│   │   │   └── EscalationService.php
│   │   └── Contracts/
│   │
│   ├── Management/                # State persistence
│   │   ├── Models/
│   │   │   ├── WorkflowDefinition.php
│   │   │   ├── WorkflowInstance.php
│   │   │   └── ApprovalAction.php
│   │   ├── Repositories/
│   │   └── Services/
│   │
│   ├── Shared/                    # Shared resources
│   │   ├── Events/
│   │   ├── Enums/
│   │   └── DTOs/
│   │
│   └── WorkflowServiceProvider.php
│
├── database/
│   ├── migrations/
│   └── seeders/
├── tests/
│   ├── Unit/
│   │   ├── Engine/
│   │   └── Management/
│   └── Feature/
└── docs/
    └── REQUIREMENTS.md (this file)
```

### Development Phases

**Phase 1: Engine Foundation (Week 1)**
- State transition logic
- Rule evaluation engine
- Approval logic
- Escalation triggers

**Phase 2: State Management (Week 2)**
- Workflow definition storage
- Instance state persistence
- ACID transaction handling
- Duplicate prevention

**Phase 3: Integration (Week 3)**
- API for transactional modules
- Notification integration
- Event dispatching

**Phase 4: UI & Templates (Week 4)**
- Workflow designer
- Approval inbox
- Workflow templates

---

## Workflow Lifecycle

```
1. Definition Phase
   └── Create workflow definition (routes, rules, conditions)

2. Instantiation Phase
   └── Create workflow instance for document
   └── Validate no duplicate instance exists

3. Execution Phase
   └── Evaluate current step
   └── Route to appropriate approvers
   └── Wait for approval actions
   └── Check escalation deadlines

4. Transition Phase
   └── Process approval/rejection
   └── Evaluate conditions for next step
   └── Transition to next state
   └── Dispatch notifications

5. Completion Phase
   └── Mark workflow as completed/rejected
   └── Archive workflow instance
   └── Update document status
```

---

## Event-Driven Architecture

**Events Emitted:**
- `WorkflowInstanceCreatedEvent` - New workflow instance started
- `ApprovalRequestedEvent` - Approval request sent to user
- `ApprovalGrantedEvent` - User approved step
- `ApprovalRejectedEvent` - User rejected step
- `WorkflowEscalatedEvent` - Approval deadline exceeded
- `WorkflowCompletedEvent` - Workflow completed successfully
- `WorkflowCancelledEvent` - Workflow cancelled

**Events Consumed:**
- Transaction submission events from transactional modules
- User delegation events from identity management

---

**Document Maintenance:**
- Update after each sprint or major feature completion
- Review during architectural changes
- Sync with master SYSTEM ARCHITECTURAL DOCUMENT

**Related Documents:**
- [SYSTEM ARCHITECTURAL DOCUMENT](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Master PRD](../../../docs/prd/PRD01-MVP.md)
- [PRD01-SUB21-WORKFLOW-ENGINE.md](../../../docs/prd/prd-01/PRD01-SUB21-WORKFLOW-ENGINE.md)
