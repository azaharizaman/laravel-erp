# PRD01-SUB06: Unit of Measure (UOM)

**Master PRD:** [PRD01-MVP.md](./PRD01-MVP.md)
**Module:** Core.006
**Implementation Plan:** [PLAN06-implement-uom.md](../plan/PLAN06-implement-uom.md)
**Status:** 📋 Planned

---

## 1. Overview

This document specifies the requirements for a comprehensive Unit of Measure (UOM) management system. This system is critical for handling items in various units across inventory, sales, and purchasing, and for performing accurate conversions between them.

## 2. Requirements

### 2.1 Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **FR-UOM-001** | **Base Units** | The system MUST support a predefined set of base units for different measure types (e.g., Kilogram for weight, Meter for length, Piece for quantity). |
| **FR-UOM-002** | **UOM Groups** | Units MUST be organized into groups (e.g., Weight, Length, Volume, Quantity) to prevent illogical conversions (e.g., converting kilograms to meters). |
| **FR-UOM-003** | **Conversion Factors** | The system MUST allow defining conversion factors between units within the same group (e.g., 1 Kilogram = 1000 Grams). |
| **FR-UOM-004** | **Multi-Unit Items** | Items in the inventory MUST support multiple units of measure (e.g., an item can be purchased in 'Boxes', stored in 'Packs', and sold in 'Pieces'). |
| **FR-UOM-005** | **Automatic Conversion** | The system MUST automatically perform UOM conversions during transactions. For example, if an item is sold in 'Pieces' but stocked in 'Boxes', the stock level should be updated correctly. |
| **FR-UOM-006** | **Custom UOMs** | Tenants MUST be able to define their own custom units of measure and conversion factors. |

### 2.2 Non-Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **PR-UOM-001** | **Accuracy** | All UOM conversions MUST use high-precision mathematics to avoid rounding errors, utilizing a library like `brick/math`. |
| **SR-UOM-001** | **Tenant Isolation** | Custom UOMs created by one tenant MUST NOT be visible or usable by another tenant. |

## 3. Integration

- The system will be implemented using the `azaharizaman/laravel-uom-management` package.
- The UOM system will be tightly integrated with the Inventory, Sales, and Purchasing domains.

## 4. Acceptance Criteria

- A user defines that 1 'Box' contains 12 'Pieces' for 'Item A'.
- A purchase order is created for 10 'Boxes' of 'Item A'.
- Upon receipt, the inventory stock for 'Item A' increases by 120 'Pieces' (its base unit).
- A sales order for 20 'Pieces' of 'Item A' is created.
- After the sale, the inventory stock correctly shows 100 'Pieces' remaining.
- An attempt to convert 'Kilograms' to 'Liters' fails with an error because they belong to different UOM groups.
