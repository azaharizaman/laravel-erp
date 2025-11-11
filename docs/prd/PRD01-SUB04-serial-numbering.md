# PRD01-SUB04: Serial Numbering System

**Master PRD:** [PRD01-MVP.md](./PRD01-MVP.md)
**Module:** Core.004
**Implementation Plan:** [PLAN04-implement-serial-numbering.md](../plan/PLAN04-implement-serial-numbering.md)
**Status:** 📋 Planned

---

## 1. Overview

This document describes the requirements for a configurable, automated serial numbering system for all major documents and records within the ERP. This ensures that all transactions are uniquely identifiable and follow a consistent, predictable format.

## 2. Requirements

### 2.1 Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **FR-SN-001** | **Configurable Patterns** | Administrators MUST be able to define unique numbering patterns for different document types. |
| **FR-SN-002** | **Pattern Variables** | The system MUST support dynamic variables in patterns, including `{year}`, `{month}`, `{day}`, `{number}`, and tenant-specific custom tags. |
| **FR-SN-003** | **Reset Periods** | The numbering sequence MUST be configurable to reset on a `daily`, `monthly`, `yearly`, or `never` basis. |
| **FR-SN-004** | **Document Types** | The system MUST support serial numbering for at least the following types: Sales Order, Purchase Order, Invoice, Stock Movement, Item, Customer, Vendor, Quotation, GRN. |
| **FR-SN-005** | **Manual Override** | Users with appropriate permissions MUST be able to manually override an auto-generated serial number, subject to uniqueness validation. |
| **FR-SN-006** | **Multi-Tenant Uniqueness** | Serial number sequences MUST be unique per tenant. Tenant A and Tenant B can both have an invoice `INV-001`. |

### 2.2 Example Patterns

| Document Type | Example Pattern | Result |
|---|---|---|
| Sales Order | `SO-{year}{month}-{number}` | `SO-202511-00001` |
| Invoice | `INV/{tenant_prefix}/{number}` | `INV/ACME/00123` |
| Purchase Order | `PO-{number}` | `PO-10001` |

### 2.3 Non-Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **SR-SN-001** | **Race Condition Prevention** | The system MUST be architected to prevent race conditions where two concurrent processes could generate the same number. |
| **PR-SN-001** | **Performance** | Generating a new serial number should not introduce significant latency into the transaction creation process. |

## 3. Integration

- The system will be implemented using the `azaharizaman/laravel-serial-numbering` package.
- The serial number generation will be triggered by model `creating` events for relevant Eloquent models.

## 4. Acceptance Criteria

- When a new Sales Order is created, it is automatically assigned a serial number like `SO-202511-00001`.
- A Tenant Admin can change the pattern for Invoices to `I-{year}-{number}` and the next invoice generated will follow the new pattern.
- An attempt to manually set a serial number that already exists for the same document type within the same tenant fails with a validation error.
- Two simultaneous API requests to create a Purchase Order result in two unique, sequential serial numbers.
