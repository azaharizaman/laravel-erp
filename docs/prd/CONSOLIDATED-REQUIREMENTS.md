# Consolidated Package Requirements

**Document Version:** 1.0.0  
**Created:** November 14, 2025  
**Purpose:** Consolidate requirements from multiple Sub-PRDs after architectural package consolidation  

---

## Document Overview

This document consolidates requirements from multiple Sub-PRD documents that have been merged into unified packages based on functional cohesion and practical shareability (as per SYSTEM ARCHITECTURAL DOCUMENT Section 10 revisions).

### Architectural Consolidation Summary

| Unified Package | Original Sub-PRDs | Rationale |
|-----------------|-------------------|-----------|
| **nexus-accounting** | SUB07 (Chart of Accounts), SUB08 (General Ledger), SUB09 (Journal Entries), SUB10 (Banking), SUB11 (Accounts Payable), SUB12 (Accounts Receivable) | Tightly coupled financial components that communicate constantly (AP→GL, AR→GL, Payments→Bank→GL). Eliminates orchestration overhead. |
| **nexus-workflow** | SUB21 (Workflow Engine + Workflow Management) | Execution logic and state persistence are always deployed together. |
| **Settings Management** (Nexus/Erp namespace) | SUB05 (Settings Management) | Cannot be published standalone; only makes sense as orchestration layer. Manages feature toggling. |

---

## 1. nexus-accounting Package

**Consolidated From:** 6 Sub-PRDs  
**Package Name:** `azaharizaman/nexus-accounting`  
**Namespace:** `Nexus\Accounting`  
**Monorepo Location:** `/packages/nexus-accounting/`

### 1.1 Executive Summary

Complete Financial Management package integrating General Ledger, Chart of Accounts, Journal Entries, Accounts Payable, Accounts Receivable, Cash and Bank Management, and Payment Processing. These components are consolidated because they:

1. Communicate constantly (AP → GL, AR → GL, Payments → Bank → GL)
2. Share the same domain context (financial accounting)
3. Are always deployed together in production
4. Cannot be meaningfully used independently

Internal modularity is maintained through:
- Namespace separation (Nexus\Accounting\ChartOfAccounts, Nexus\Accounting\GeneralLedger, etc.)
- Bounded contexts with clear interfaces
- Domain events for internal communication

### 1.2 Consolidated Functional Requirements

#### 1.2.1 Chart of Accounts (COA)

**Source:** PRD01-SUB07-CHART-OF-ACCOUNTS.md

| Requirement ID | Description | Priority | Source |
|----------------|-------------|----------|--------|
| **FR-ACC-COA-001** | Maintain **hierarchical chart of accounts** with unlimited depth using nested set model | High | FR-COA-001 |
| **FR-ACC-COA-002** | Support **5 standard account types** (Asset, Liability, Equity, Revenue, Expense) with type inheritance | High | FR-COA-002 |
| **FR-ACC-COA-003** | Allow tagging accounts by **category and reporting group** for financial statement organization | High | FR-COA-003 |
| **FR-ACC-COA-004** | Support **flexible account code format** (e.g., 1000-00, 1.1.1) per tenant configuration | Medium | FR-COA-004 |
| **FR-ACC-COA-005** | Provide **account activation/deactivation** without deletion to preserve history | Medium | FR-COA-005 |
| **FR-ACC-COA-006** | Support **account templates** for quick COA setup (manufacturing, retail, services) | Low | FR-COA-006 |

#### 1.2.2 General Ledger (GL)

**Source:** PRD01-SUB08-GENERAL-LEDGER.md

| Requirement ID | Description | Priority | Source |
|----------------|-------------|----------|--------|
| **FR-ACC-GL-001** | **Automatically post entries** from all submodules (AP, AR, Inventory, Payroll) to GL with full audit trail | High | FR-GL-001 |
| **FR-ACC-GL-002** | Support **multi-currency** transactions with automatic exchange rate conversion and revaluation | High | FR-GL-002 |
| **FR-ACC-GL-003** | Implement **period closing** process with validation and lock-down to prevent backdated entries | High | FR-GL-003 |
| **FR-ACC-GL-004** | Provide **account balance inquiries** at any point in time with drill-down to transaction detail | High | FR-GL-004 |
| **FR-ACC-GL-005** | Support **batch journal entry posting** with validation and error reporting | Medium | FR-GL-005 |
| **FR-ACC-GL-006** | Generate **trial balance report** with comparative periods and variance analysis | High | FR-GL-006 |

#### 1.2.3 Journal Entries (JE)

**Source:** PRD01-SUB09-JOURNAL-ENTRIES.md

| Requirement ID | Description | Priority | Source |
|----------------|-------------|----------|--------|
| **FR-ACC-JE-001** | Support **manual journal entry creation** with multi-line debit/credit allocation | High | FR-JE-001 |
| **FR-ACC-JE-002** | Enforce **balanced entry validation** (total debits = total credits) before posting | High | FR-JE-002 |
| **FR-ACC-JE-003** | Provide **recurring journal entry templates** with scheduling capabilities | Medium | FR-JE-003 |
| **FR-ACC-JE-004** | Support **journal entry reversal** with automatic offsetting entries | High | FR-JE-004 |
| **FR-ACC-JE-005** | Enable **attachment of supporting documents** to journal entries | Medium | FR-JE-005 |

#### 1.2.4 Banking & Cash Management

**Source:** PRD01-SUB10-BANKING.md

| Requirement ID | Description | Priority | Source |
|----------------|-------------|----------|--------|
| **FR-ACC-BANK-001** | Maintain **bank account master** with account details and currency | High | FR-BANK-001 |
| **FR-ACC-BANK-002** | Record **bank transactions** (deposits, withdrawals, transfers) with reconciliation status | High | FR-BANK-002 |
| **FR-ACC-BANK-003** | Support **bank reconciliation** process matching transactions with bank statements | High | FR-BANK-003 |
| **FR-ACC-BANK-004** | Track **cash accounts** with petty cash management and replenishment | Medium | FR-BANK-004 |
| **FR-ACC-BANK-005** | Generate **cashflow statements** with operating, investing, financing activities | High | FR-BANK-005 |

#### 1.2.5 Accounts Payable (AP)

**Source:** PRD01-SUB11-ACCOUNTS-PAYABLE.md

| Requirement ID | Description | Priority | Source |
|----------------|-------------|----------|--------|
| **FR-ACC-AP-001** | Record **vendor invoices** with line items, taxes, and payment terms | High | FR-AP-001 |
| **FR-ACC-AP-002** | Support **three-way matching** (PO, Goods Receipt, Invoice) with variance handling | High | FR-AP-002 |
| **FR-ACC-AP-003** | Process **vendor payments** with batch payment runs and check printing | High | FR-AP-003 |
| **FR-ACC-AP-004** | Track **vendor aging** and generate aging reports (30, 60, 90+ days) | High | FR-AP-004 |
| **FR-ACC-AP-005** | Support **vendor credit notes** and payment application | Medium | FR-AP-005 |

#### 1.2.6 Accounts Receivable (AR)

**Source:** PRD01-SUB12-ACCOUNTS-RECEIVABLE.md

| Requirement ID | Description | Priority | Source |
|----------------|-------------|----------|--------|
| **FR-ACC-AR-001** | Generate **customer invoices** from sales orders with line items and taxes | High | FR-AR-001 |
| **FR-ACC-AR-002** | Record **customer payments** with payment allocation to invoices | High | FR-AR-002 |
| **FR-ACC-AR-003** | Track **customer aging** and generate aging reports with collection status | High | FR-AR-003 |
| **FR-ACC-AR-004** | Support **credit notes** and refund processing | Medium | FR-AR-004 |
| **FR-ACC-AR-005** | Implement **payment terms** with automatic due date calculation | Medium | FR-AR-005 |

### 1.3 Consolidated Business Rules

| Rule ID | Description | Scope | Source |
|---------|-------------|-------|--------|
| **BR-ACC-001** | All journal entries MUST be **balanced (debit = credit)** before posting | GL, JE | BR-GL-001, BR-JE-001 |
| **BR-ACC-002** | **Posted entries** cannot be modified; only reversed with offsetting entries | GL, JE | BR-GL-002 |
| **BR-ACC-003** | Prevent **deletion of accounts** with associated transactions or child accounts | COA | BR-COA-001 |
| **BR-ACC-004** | **Account codes** MUST be unique within tenant scope | COA | BR-COA-002 |
| **BR-ACC-005** | Only **leaf accounts** (no children) can have transactions posted to them | COA, GL | BR-COA-003 |
| **BR-ACC-006** | Entries can only be posted to **active fiscal periods**; closed periods reject entries | GL | BR-GL-003 |
| **BR-ACC-007** | Foreign currency transactions MUST record both **base and foreign amounts** with exchange rate | GL | BR-GL-004 |
| **BR-ACC-008** | **Three-way matching** required for vendor invoice posting (PO, GR, Invoice) | AP | BR-AP-002 |
| **BR-ACC-009** | Customer payments MUST be allocated to specific invoices for proper aging tracking | AR | BR-AR-002 |

### 1.4 Data Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **DR-ACC-001** | Accounts table with: code, name, type, category, parent_id, lft, rgt, level, is_active, reporting_group | COA |
| **DR-ACC-002** | Use **nested set model** (lft, rgt columns) for hierarchical queries with `kalnoy/nestedset` | COA |
| **DR-ACC-003** | Store **aggregated monthly balances** for high-performance reporting | GL |
| **DR-ACC-004** | GL entries with: date, account_id, amount, currency, exchange_rate, memo, posted_status, source_module | GL |
| **DR-ACC-005** | Bank accounts with: account_number, bank_name, currency, balance, is_active | BANK |
| **DR-ACC-006** | Vendor invoices with: vendor_id, invoice_number, amount, due_date, payment_status | AP |
| **DR-ACC-007** | Customer invoices with: customer_id, invoice_number, amount, due_date, payment_status | AR |

### 1.5 Integration Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **IR-ACC-001** | Integrate with **nexus-inventory** for automatic COGS/inventory GL posting | High |
| **IR-ACC-002** | Integrate with **nexus-purchase-order** for PO-GR-Invoice three-way matching | High |
| **IR-ACC-003** | Integrate with **nexus-sales-order** for automatic AR invoice generation | High |
| **IR-ACC-004** | Expose **posting API** for external modules to post GL entries | High |
| **IR-ACC-005** | Integrate with **nexus-tax-management** for automatic tax calculation | Medium |

### 1.6 Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-ACC-001** | Trial balance generation for 100K transactions | < 2 seconds |
| **PR-ACC-002** | Account balance inquiry with drill-down | < 500ms |
| **PR-ACC-003** | Bank reconciliation for 10K transactions | < 5 seconds |
| **PR-ACC-004** | Aging report generation (30/60/90 days) | < 3 seconds |

### 1.7 Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-ACC-001** | Enforce **role-based access control** for all accounting operations |
| **SR-ACC-002** | Implement **audit logging** for all GL postings and reversals |
| **SR-ACC-003** | Require **dual authorization** for payment runs above threshold |
| **SR-ACC-004** | Enforce **tenant isolation** for all accounting data |

---

## 2. nexus-workflow Package

**Consolidated From:** 1 Sub-PRD (Engine + Management combined)  
**Package Name:** `azaharizaman/nexus-workflow`  
**Namespace:** `Nexus\Workflow`  
**Monorepo Location:** `/packages/nexus-workflow/`

### 2.1 Executive Summary

Complete Workflow Management package integrating:
- **Workflow Engine:** Stateless computation of state transitions, rule evaluation, approval logic, escalation triggers
- **Workflow Management:** State persistence, tracking status, history, users, instance data of running workflows

These are consolidated because workflow engine and state management are tightly coupled and always deployed together.

### 2.2 Functional Requirements

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

### 2.3 Business Rules

| Rule ID | Description |
|---------|-------------|
| **BR-WF-001** | Approvals must be executed in **sequential order** unless parallel routing is enabled |
| **BR-WF-002** | Approvers cannot approve their **own submissions** |
| **BR-WF-003** | Escalations occur **automatically** when approval deadlines are exceeded |
| **BR-WF-004** | Workflow state changes MUST be **ACID-compliant** transactions |

### 2.4 Data Requirements

| Requirement ID | Description |
|----------------|-------------|
| **DR-WF-001** | Store **workflow definitions** with routing rules and conditions |
| **DR-WF-002** | Maintain **workflow instance state** tracking current step and history |
| **DR-WF-003** | Track **approval actions** with timestamps, comments, and attachments |
| **DR-WF-004** | Store **delegation records** with start/end dates and delegator/delegatee |

### 2.5 Integration Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **IR-WF-001** | Integrate with **all transactional modules** for approval workflows | High |
| **IR-WF-002** | Integrate with **nexus-notification-service** for approval notifications | High |
| **IR-WF-003** | Support **external workflow triggers** via API for third-party systems | Medium |

---

## 3. Settings Management (Nexus/Erp Namespace)

**Source:** PRD01-SUB05-SETTINGS-MANAGEMENT.md  
**Package:** NOT a standalone package - part of Nexus/Erp orchestration layer  
**Namespace:** `Nexus\Erp\Settings`  
**Location:** `/src/Settings/`

### 3.1 Executive Summary

Settings Management is part of the orchestration layer (NOT a publishable package) because:
1. Cannot be meaningfully used standalone
2. Manages feature toggling orchestration (controls what features are available to end users)
3. Core orchestration responsibility controlling how packages interact

**Key Responsibilities:**
- Key-value store for global application, tenant, or user-specific settings
- Feature flag orchestration (determining which features are enabled)
- Hierarchical settings resolution (user → module → tenant → system)
- Settings encryption for sensitive values (API keys, passwords)

### 3.2 Functional Requirements

**Source:** PRD01-SUB05-SETTINGS-MANAGEMENT.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-SET-001** | Support **hierarchical settings** with automatic inheritance (user → module → tenant → system) | High |
| **FR-SET-002** | Provide **type-safe values** (string, integer, boolean, array, json, encrypted) | High |
| **FR-SET-003** | Implement **multi-tenant isolation** with automatic tenant context injection | High |
| **FR-SET-004** | Support **high-performance caching** with Redis/Memcached and automatic invalidation | High |
| **FR-SET-005** | Encrypt **sensitive settings** (API keys, passwords) using AES-256 | High |
| **FR-SET-006** | Provide **RESTful API** for CRUD operations with bulk update and import/export | Medium |
| **FR-SET-007** | Dispatch **events** when settings change for reactive updates | Medium |
| **FR-SET-008** | Integrate **Laravel Scout** for searchable settings | Low |
| **FR-SET-009** | Maintain **complete audit trail** with user attribution | Medium |
| **FR-SET-010** | **Feature Flag Orchestration:** Control which packages/features are enabled per tenant/user | High |

### 3.3 Business Rules

| Rule ID | Description |
|---------|-------------|
| **BR-SET-001** | Settings are resolved hierarchically: **user → module → tenant → system** |
| **BR-SET-002** | **System-level settings** can only be modified by super-admins |
| **BR-SET-003** | Encrypted values are **masked in API responses** unless user has 'view-encrypted-settings' permission |
| **BR-SET-004** | Feature flags control **package availability** - packages check flags before operations |

### 3.4 Data Requirements

| Requirement ID | Description |
|----------------|-------------|
| **DR-SET-001** | Settings table with: key, value, type, scope, module_name, user_id, tenant_id, metadata |
| **DR-SET-002** | Settings history table for audit trail with: setting_id, old_value, new_value, changed_by, changed_at |
| **DR-SET-003** | Feature flags stored as boolean settings with scope=system or scope=tenant |

### 3.5 Integration Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **IR-SET-001** | All packages MUST check feature flags before executing operations | High |
| **IR-SET-002** | Settings service MUST be injectable via `SettingsServiceContract` | High |
| **IR-SET-003** | Cache invalidation MUST trigger across all application instances | High |

---

## 4. Implementation Priority

### Phase 1: Foundation (Weeks 1-2)
1. **Settings Management** (Nexus/Erp namespace) - Already implemented ✅
2. **nexus-workflow** - Universal capability needed by all transactional modules

### Phase 2: Financial Core (Weeks 3-6)
3. **nexus-accounting** - Core financial management
   - Start with Chart of Accounts
   - Then General Ledger
   - Then Journal Entries
   - Then Banking
   - Then AP/AR

### Phase 3: Business Operations (Weeks 7+)
4. Other packages per SYSTEM ARCHITECTURAL DOCUMENT Section 10

---

## 5. Cross-Package Dependencies

```
Settings Management (Nexus/Erp)
    ↓ (feature flags, configuration)
nexus-workflow
    ↓ (approval workflows)
nexus-accounting
    ↓ (financial transactions)
nexus-purchase-order, nexus-sales-order, etc.
```

All packages depend on:
- **nexus-tenancy** (multi-tenancy isolation)
- **nexus-identity-management** (authentication, authorization)
- **nexus-audit-log** (change tracking)
- **Settings Management** (configuration, feature flags)

---

## 6. Document Maintenance

**Update Frequency:** After each architectural consolidation or package scope change  
**Owner:** Development Team  
**Approval Required:** Technical Lead sign-off before implementation

**Change Log:**
- 2025-11-14: Initial consolidation after Section 10 restructuring
