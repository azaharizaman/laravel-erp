# nexus-accounting Package Requirements

**Package Name:** `azaharizaman/nexus-accounting`  
**Namespace:** `Nexus\Accounting`  
**Version:** 1.0.0  
**Status:** Draft  
**Created:** November 14, 2025

---

## Executive Summary

Complete Financial Management package integrating General Ledger, Chart of Accounts, Journal Entries, Accounts Payable, Accounts Receivable, Cash and Bank Management, and Payment Processing.

### Architectural Rationale

**Consolidated From:** 6 Sub-PRDs
- PRD01-SUB07-CHART-OF-ACCOUNTS.md
- PRD01-SUB08-GENERAL-LEDGER.md
- PRD01-SUB09-JOURNAL-ENTRIES.md
- PRD01-SUB10-BANKING.md
- PRD01-SUB11-ACCOUNTS-PAYABLE.md
- PRD01-SUB12-ACCOUNTS-RECEIVABLE.md

**Why Consolidated:**
These components are consolidated because they:
1. **Communicate constantly** (AP → GL, AR → GL, Payments → Bank → GL)
2. **Share domain context** (financial accounting)
3. **Always deployed together** in production
4. **Cannot be meaningfully used independently**

**Internal Modularity:**
Maintained through:
- Namespace separation (`Nexus\Accounting\ChartOfAccounts`, `Nexus\Accounting\GeneralLedger`, etc.)
- Bounded contexts with clear interfaces
- Domain events for internal communication

---

## Functional Requirements

### 1. Chart of Accounts (COA)

**Source:** PRD01-SUB07-CHART-OF-ACCOUNTS.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-COA-001** | Maintain **hierarchical chart of accounts** with unlimited depth using nested set model | High |
| **FR-ACC-COA-002** | Support **5 standard account types** (Asset, Liability, Equity, Revenue, Expense) with type inheritance | High |
| **FR-ACC-COA-003** | Allow tagging accounts by **category and reporting group** for financial statement organization | High |
| **FR-ACC-COA-004** | Support **flexible account code format** (e.g., 1000-00, 1.1.1) per tenant configuration | Medium |
| **FR-ACC-COA-005** | Provide **account activation/deactivation** without deletion to preserve history | Medium |
| **FR-ACC-COA-006** | Support **account templates** for quick COA setup (manufacturing, retail, services) | Low |

### 2. General Ledger (GL)

**Source:** PRD01-SUB08-GENERAL-LEDGER.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-GL-001** | **Automatically post entries** from all submodules (AP, AR, Inventory, Payroll) to GL with full audit trail | High |
| **FR-ACC-GL-002** | Support **multi-currency** transactions with automatic exchange rate conversion and revaluation | High |
| **FR-ACC-GL-003** | Implement **period closing** process with validation and lock-down to prevent backdated entries | High |
| **FR-ACC-GL-004** | Provide **account balance inquiries** at any point in time with drill-down to transaction detail | High |
| **FR-ACC-GL-005** | Support **batch journal entry posting** with validation and error reporting | Medium |
| **FR-ACC-GL-006** | Generate **trial balance report** with comparative periods and variance analysis | High |

### 3. Journal Entries (JE)

**Source:** PRD01-SUB09-JOURNAL-ENTRIES.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-JE-001** | Support **manual journal entry creation** with multi-line debit/credit allocation | High |
| **FR-ACC-JE-002** | Enforce **balanced entry validation** (total debits = total credits) before posting | High |
| **FR-ACC-JE-003** | Provide **recurring journal entry templates** with scheduling capabilities | Medium |
| **FR-ACC-JE-004** | Support **journal entry reversal** with automatic offsetting entries | High |
| **FR-ACC-JE-005** | Enable **attachment of supporting documents** to journal entries | Medium |

### 4. Banking & Cash Management

**Source:** PRD01-SUB10-BANKING.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-BANK-001** | Maintain **bank account master** with account details and currency | High |
| **FR-ACC-BANK-002** | Record **bank transactions** (deposits, withdrawals, transfers) with reconciliation status | High |
| **FR-ACC-BANK-003** | Support **bank reconciliation** process matching transactions with bank statements | High |
| **FR-ACC-BANK-004** | Track **cash accounts** with petty cash management and replenishment | Medium |
| **FR-ACC-BANK-005** | Generate **cashflow statements** with operating, investing, financing activities | High |

### 5. Accounts Payable (AP)

**Source:** PRD01-SUB11-ACCOUNTS-PAYABLE.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-AP-001** | Record **vendor invoices** with line items, taxes, and payment terms | High |
| **FR-ACC-AP-002** | Support **three-way matching** (PO, Goods Receipt, Invoice) with variance handling | High |
| **FR-ACC-AP-003** | Process **vendor payments** with batch payment runs and check printing | High |
| **FR-ACC-AP-004** | Track **vendor aging** and generate aging reports (30, 60, 90+ days) | High |
| **FR-ACC-AP-005** | Support **vendor credit notes** and payment application | Medium |

### 6. Accounts Receivable (AR)

**Source:** PRD01-SUB12-ACCOUNTS-RECEIVABLE.md

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **FR-ACC-AR-001** | Generate **customer invoices** from sales orders with line items and taxes | High |
| **FR-ACC-AR-002** | Record **customer payments** with payment allocation to invoices | High |
| **FR-ACC-AR-003** | Track **customer aging** and generate aging reports with collection status | High |
| **FR-ACC-AR-004** | Support **credit notes** and refund processing | Medium |
| **FR-ACC-AR-005** | Implement **payment terms** with automatic due date calculation | Medium |

---

## Business Rules

| Rule ID | Description | Scope |
|---------|-------------|-------|
| **BR-ACC-001** | All journal entries MUST be **balanced (debit = credit)** before posting | GL, JE |
| **BR-ACC-002** | **Posted entries** cannot be modified; only reversed with offsetting entries | GL, JE |
| **BR-ACC-003** | Prevent **deletion of accounts** with associated transactions or child accounts | COA |
| **BR-ACC-004** | **Account codes** MUST be unique within tenant scope | COA |
| **BR-ACC-005** | Only **leaf accounts** (no children) can have transactions posted to them | COA, GL |
| **BR-ACC-006** | Entries can only be posted to **active fiscal periods**; closed periods reject entries | GL |
| **BR-ACC-007** | Foreign currency transactions MUST record both **base and foreign amounts** with exchange rate | GL |
| **BR-ACC-008** | **Three-way matching** required for vendor invoice posting (PO, GR, Invoice) | AP |
| **BR-ACC-009** | Customer payments MUST be allocated to specific invoices for proper aging tracking | AR |

---

## Data Requirements

| Requirement ID | Description | Scope |
|----------------|-------------|-------|
| **DR-ACC-001** | Accounts table with: code, name, type, category, parent_id, lft, rgt, level, is_active, reporting_group | COA |
| **DR-ACC-002** | Use **nested set model** (lft, rgt columns) for hierarchical queries with `kalnoy/nestedset` | COA |
| **DR-ACC-003** | Store **aggregated monthly balances** for high-performance reporting | GL |
| **DR-ACC-004** | GL entries with: date, account_id, amount, currency, exchange_rate, memo, posted_status, source_module | GL |
| **DR-ACC-005** | Bank accounts with: account_number, bank_name, currency, balance, is_active | BANK |
| **DR-ACC-006** | Vendor invoices with: vendor_id, invoice_number, amount, due_date, payment_status | AP |
| **DR-ACC-007** | Customer invoices with: customer_id, invoice_number, amount, due_date, payment_status | AR |

---

## Integration Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **IR-ACC-001** | Integrate with **nexus-inventory** for automatic COGS/inventory GL posting | High |
| **IR-ACC-002** | Integrate with **nexus-purchase-order** for PO-GR-Invoice three-way matching | High |
| **IR-ACC-003** | Integrate with **nexus-sales-order** for automatic AR invoice generation | High |
| **IR-ACC-004** | Expose **posting API** for external modules to post GL entries | High |
| **IR-ACC-005** | Integrate with **nexus-tax-management** for automatic tax calculation | Medium |

---

## Performance Requirements

| Requirement ID | Description | Target |
|----------------|-------------|--------|
| **PR-ACC-001** | Trial balance generation for 100K transactions | < 2 seconds |
| **PR-ACC-002** | Account balance inquiry with drill-down | < 500ms |
| **PR-ACC-003** | Bank reconciliation for 10K transactions | < 5 seconds |
| **PR-ACC-004** | Aging report generation (30/60/90 days) | < 3 seconds |

---

## Security Requirements

| Requirement ID | Description |
|----------------|-------------|
| **SR-ACC-001** | Enforce **role-based access control** for all accounting operations |
| **SR-ACC-002** | Implement **audit logging** for all GL postings and reversals |
| **SR-ACC-003** | Require **dual authorization** for payment runs above threshold |
| **SR-ACC-004** | Enforce **tenant isolation** for all accounting data |

---

## Dependencies

**Mandatory Package Dependencies:**
- `azaharizaman/nexus-tenancy` - Multi-tenancy isolation
- `azaharizaman/nexus-audit-log` - Change tracking
- `kalnoy/nestedset` - Hierarchical account structure

**Optional Package Dependencies:**
- `azaharizaman/nexus-inventory` - Inventory GL posting
- `azaharizaman/nexus-purchase-order` - Purchase order integration
- `azaharizaman/nexus-sales-order` - Sales order integration
- `azaharizaman/nexus-tax-management` - Tax calculation

**Framework Dependencies:**
- Laravel Framework ≥ 12.x
- PHP ≥ 8.2
- PostgreSQL or MySQL

---

## Implementation Notes

### Internal Package Structure

```
packages/nexus-accounting/
├── src/
│   ├── ChartOfAccounts/
│   │   ├── Models/
│   │   ├── Actions/
│   │   ├── Services/
│   │   └── Repositories/
│   ├── GeneralLedger/
│   │   ├── Models/
│   │   ├── Actions/
│   │   └── Services/
│   ├── JournalEntries/
│   ├── Banking/
│   ├── AccountsPayable/
│   └── AccountsReceivable/
├── database/
│   ├── migrations/
│   └── seeders/
├── tests/
└── docs/
    └── REQUIREMENTS.md (this file)
```

### Development Phases

**Phase 1: Foundation (Week 1)**
- Chart of Accounts structure
- Basic account CRUD operations
- Nested set implementation

**Phase 2: Core GL (Week 2)**
- GL entry posting
- Balance calculations
- Multi-currency support

**Phase 3: Journal Entries (Week 3)**
- Manual JE creation
- Entry validation
- Reversal functionality

**Phase 4: Banking (Week 4)**
- Bank account management
- Transaction recording
- Bank reconciliation

**Phase 5: AP/AR (Weeks 5-6)**
- Vendor invoice management
- Customer invoice management
- Payment processing
- Aging reports

---

**Document Maintenance:**
- Update after each sprint or major feature completion
- Review during architectural changes
- Sync with master SYSTEM ARCHITECTURAL DOCUMENT

**Related Documents:**
- [SYSTEM ARCHITECTURAL DOCUMENT](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- [Master PRD](../../../docs/prd/PRD01-MVP.md)
- Original Sub-PRDs in `/docs/prd/prd-01/`
