# Package Requirements Index

**Document Version:** 1.0.0  
**Created:** November 14, 2025  
**Purpose:** Master index linking to requirements documents in their respective package folders

---

## Overview

Requirements documents are distributed across package folders to avoid cluttering the main repository. This index provides quick navigation to all package requirements.

### Organizational Principle

**✅ DO:** Keep requirements in package-specific `/docs/REQUIREMENTS.md`  
**❌ DON'T:** Duplicate requirements in main `/docs/prd/` folder

---

## Package Requirements Documents

### 1. Core Financial Packages

#### nexus-accounting
**Location:** [`/packages/nexus-accounting/docs/REQUIREMENTS.md`](../../packages/nexus-accounting/docs/REQUIREMENTS.md)  
**Status:** Draft - To be implemented  
**Priority:** Phase 2 (Weeks 3-6)

**Scope:** Complete Financial Management
- Chart of Accounts (COA)
- General Ledger (GL)
- Journal Entries (JE)
- Banking & Cash Management
- Accounts Payable (AP)
- Accounts Receivable (AR)

**Consolidated From:**
- PRD01-SUB07-CHART-OF-ACCOUNTS.md
- PRD01-SUB08-GENERAL-LEDGER.md
- PRD01-SUB09-JOURNAL-ENTRIES.md
- PRD01-SUB10-BANKING.md
- PRD01-SUB11-ACCOUNTS-PAYABLE.md
- PRD01-SUB12-ACCOUNTS-RECEIVABLE.md

**Requirements Summary:**
- 31 Functional Requirements
- 9 Business Rules
- 7 Data Requirements
- 5 Integration Requirements

---

### 2. Cross-Cutting Capability Packages

#### nexus-workflow
**Location:** [`/packages/nexus-workflow/docs/REQUIREMENTS.md`](../../packages/nexus-workflow/docs/REQUIREMENTS.md)  
**Status:** Draft - To be implemented  
**Priority:** Phase 1 (Weeks 1-2)

**Scope:** Complete Workflow Management
- Workflow Engine (stateless computation)
- Workflow Management (state persistence)

**Consolidated From:**
- PRD01-SUB21-WORKFLOW-ENGINE.md (engine + management combined)

**Requirements Summary:**
- 10 Functional Requirements
- 4 Business Rules
- 4 Data Requirements
- 3 Integration Requirements

---

### 3. Orchestration Layer Components

#### Settings Management
**Location:** [`/src/Settings/docs/REQUIREMENTS.md`](../../src/Settings/docs/REQUIREMENTS.md)  
**Status:** ✅ Implemented  
**Priority:** Phase 1 (Complete)

**Scope:** Settings & Feature Flag Orchestration
- Hierarchical settings management
- Feature flag control
- Multi-tenant settings isolation
- Encrypted sensitive settings

**Why NOT a Package:**
- Cannot be meaningfully used standalone
- Core orchestration responsibility
- Controls package interaction and availability

**Consolidated From:**
- PRD01-SUB05-SETTINGS-MANAGEMENT.md

**Requirements Summary:**
- 10 Functional Requirements (all implemented ✅)
- 4 Business Rules
- 3 Data Requirements
- 3 Integration Requirements

---

## Implementation Roadmap

### Phase 1: Foundation (Weeks 1-2) ✅ CURRENT
- [x] Settings Management (Nexus/Erp namespace) - **COMPLETE**
- [ ] nexus-workflow - **NEXT**

### Phase 2: Financial Core (Weeks 3-6)
- [ ] nexus-accounting
  - [ ] Chart of Accounts
  - [ ] General Ledger
  - [ ] Journal Entries
  - [ ] Banking
  - [ ] AP/AR

### Phase 3: Business Operations (Weeks 7+)
- [ ] Other packages per SYSTEM ARCHITECTURAL DOCUMENT Section 10

---

## Source PRD Documents

All original Sub-PRD documents remain in [`/docs/prd/prd-01/`](./prd-01/) for reference and traceability:

- PRD01-SUB01-MULTITENANCY.md
- PRD01-SUB02-AUTHENTICATION.md
- PRD01-SUB03-AUDIT-LOGGING.md
- PRD01-SUB04-SERIAL-NUMBERING.md
- PRD01-SUB05-SETTINGS-MANAGEMENT.md
- PRD01-SUB06-UOM.md
- PRD01-SUB07-CHART-OF-ACCOUNTS.md
- PRD01-SUB08-GENERAL-LEDGER.md
- PRD01-SUB09-JOURNAL-ENTRIES.md
- PRD01-SUB10-BANKING.md
- PRD01-SUB11-ACCOUNTS-PAYABLE.md
- PRD01-SUB12-ACCOUNTS-RECEIVABLE.md
- PRD01-SUB13-HCM.md
- PRD01-SUB14-INVENTORY-MANAGEMENT.md
- PRD01-SUB15-BACKOFFICE.md
- PRD01-SUB16-PURCHASING.md
- PRD01-SUB17-SALES.md
- PRD01-SUB18-MASTER-DATA-MANAGEMENT.md
- PRD01-SUB19-TAXATION.md
- PRD01-SUB20-FINANCIAL-REPORTING.md
- PRD01-SUB21-WORKFLOW-ENGINE.md
- PRD01-SUB22-NOTIFICATIONS-EVENTS.md
- PRD01-SUB23-API-GATEWAY-AND-DOCUMENTATION.md
- PRD01-SUB24-INTEGRATION-CONNECTORS.md
- PRD01-SUB25-LOCALIZATION.md

---

## Adding New Package Requirements

When creating a new package, follow this process:

### 1. Create Package Directory Structure
```bash
mkdir -p packages/{package-name}/docs
```

### 2. Create Requirements Document
```bash
touch packages/{package-name}/docs/REQUIREMENTS.md
```

### 3. Use Template Structure
```markdown
# {package-name} Package Requirements

**Package Name:** `azaharizaman/{package-name}`
**Namespace:** `Nexus\{Namespace}`
**Version:** 1.0.0
**Status:** Draft
**Created:** {date}

## Executive Summary
## Functional Requirements
## Business Rules
## Data Requirements
## Integration Requirements
## Performance Requirements
## Security Requirements
## Dependencies
## Implementation Notes
```

### 4. Update This Index
Add entry to relevant section above with:
- Link to requirements document
- Status and priority
- Scope summary
- Requirements count

### 5. Cross-Reference
Link to:
- Original Sub-PRD documents
- SYSTEM ARCHITECTURAL DOCUMENT
- Master PRD

---

## Cross-Package Dependencies

```
Settings Management (Nexus/Erp)
    ↓ (feature flags, configuration)
nexus-workflow
    ↓ (approval workflows)
nexus-accounting
    ↓ (financial transactions)
nexus-purchase-order, nexus-sales-order, etc.
```

**Universal Dependencies:**
All packages depend on:
- `nexus-tenancy` - Multi-tenancy isolation
- `nexus-identity-management` - Authentication, authorization
- `nexus-audit-log` - Change tracking
- Settings Management - Configuration, feature flags

---

## Document Maintenance

**Update Frequency:** After each package creation or architectural change  
**Owner:** Development Team  
**Approval Required:** Technical Lead sign-off

**Change Log:**
- 2025-11-14: Initial index created after requirements distribution

---

## Related Architecture Documents

- [SYSTEM ARCHITECTURAL DOCUMENT](../SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Master PRD](./PRD01-MVP.md)
- [Package Decoupling Strategy](../architecture/PACKAGE-DECOUPLING-STRATEGY.md)

---

**Navigation:**
- 🏠 [Documentation Index](../DOCUMENTATION_INDEX.md)
- 📋 [Master PRD](./PRD01-MVP.md)
- 🏗️ [System Architecture](../SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
