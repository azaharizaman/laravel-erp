# PRD01-SUB03: Audit Logging System

**Master PRD:** [PRD01-MVP.md](./PRD01-MVP.md)
**Module:** Core.003
**Implementation Plan:** [PLAN03-implement-audit-logging.md](../plan/PLAN03-implement-audit-logging.md)
**Status:** 📋 Planned

---

## 1. Overview

This document defines the requirements for a comprehensive Audit Logging and Activity Tracking system. The goal is to record all significant events and data changes within the system to ensure accountability, security, and compliance.

## 2. Requirements

### 2.1 Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **FR-AL-001** | **Activity Logging** | The system MUST log all CRUD (Create, Read, Update, Delete) operations on critical models. |
| **FR-AL-002** | **Custom Events** | The system MUST provide a service (`ActivityLoggerContract`) to log custom business events (e.g., "User logged in", "Invoice paid"). |
| **FR-AL-003** | **Critical Operations** | Specific, high-impact operations (e.g., tenant impersonation, permission changes, data exports) MUST be identified and logged with a 'critical' severity level. |
| **FR-AL-004** | **Data Context** | Each log entry MUST include context: who performed the action, what was changed (old and new values), when it happened, and from what IP address. |
| **FR-AL-005** | **Audit Export** | An API endpoint MUST be available for authorized users to export audit logs in CSV or JSON format, filterable by date, user, and event type. |
| **FR-AL-006** | **Auth Event Logging** | All authentication events (successful login, failed login, logout, password reset) MUST be logged. |
| **FR-AL-007** | **Permission Logging** | Any changes to roles or permissions MUST be logged as critical events. |

### 2.2 Non-Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **SR-AL-001** | **Tenant Isolation** | Audit logs MUST be strictly isolated by tenant. Users can only view logs for their own tenant. |
| **SR-AL-002** | **Immutability (Optional)** | The system SHOULD support optional integration with a blockchain service to create an immutable, verifiable hash of the audit trail. |
| **SR-AL-003** | **Log Retention** | Active logs MUST be retained in the primary database for 90 days. Archived logs MUST be moved to cold storage for 7 years. |
| **PR-AL-001** | **Performance Impact** | Activity logging MUST have a minimal performance impact on user-facing requests, leveraging queues for processing where necessary. |

## 3. Data Model

### `activity_log` table (from `spatie/laravel-activitylog`)

| Column | Type | Description |
|---|---|---|
| `id` | `bigint` | Primary Key. |
| `log_name` | `string` | The name of the log (e.g., 'default', 'security'). |
| `description` | `text` | A human-readable description of the event. |
| `subject_type` | `string` | The model class of the subject. |
| `subject_id` | `uuid` | The ID of the subject model. |
| `causer_type` | `string` | The model class of the user who caused the event. |
| `causer_id` | `uuid` | The ID of the causer. |
| `properties` | `json` | A JSON object containing old and new attributes. |
| `created_at` | `timestamp` | |

## 4. Acceptance Criteria

- When a user updates a customer's record, a new entry is created in the `activity_log` table showing the old and new values.
- A failed login attempt creates a security log entry with the user's email and IP address.
- A Tenant Admin can view a complete history of all activities within their tenant.
- A Super Admin can export the audit log for a specific tenant for a given date range.
- The `ActivityLoggerContract` is successfully decoupled from the underlying `spatie/laravel-activitylog` package.
