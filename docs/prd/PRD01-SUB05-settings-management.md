# PRD01-SUB05: Settings Management

**Master PRD:** [PRD01-MVP.md](./PRD01-MVP.md)
**Module:** Core.005
**Implementation Plan:** [PLAN05-implement-settings-management.md](../plan/PLAN05-implement-settings-management.md)
**Status:** 📋 Planned

---

## 1. Overview

This document outlines the requirements for a hierarchical, database-backed settings management system. This will allow for flexible configuration of the application at different levels, from global system settings down to individual user preferences.

## 2. Requirements

### 2.1 Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **FR-SM-001** | **Hierarchical Structure** | The system MUST support a settings hierarchy: System (global default) -> Tenant (overrides system) -> User (overrides tenant). |
| **FR-SM-002** | **Database Storage** | All settings MUST be stored in the database to allow for dynamic changes without code deployments. |
| **FR-SM-003** | **Type Validation** | The system MUST support and validate different setting types, including `string`, `integer`, `boolean`, `json`, and `encrypted` string. |
| **FR-SM-004** | **Default Values** | Every setting MUST have a default value defined in a configuration file, which is used if no value is set in the database. |
| **FR-SM-005** | **Setting Groups** | Settings MUST be organized into logical groups (e.g., `general`, `email`, `security`) for easier management. |
| **FR-SM-006** | **API Access** | A secure API MUST be provided for authorized users to read and write settings at the appropriate level (e.g., Tenant Admins can change tenant settings). |
| **FR-SM-007** | **CLI Commands** | CLI commands (`setting:get`, `setting:set`) MUST be available for system administrators to manage settings. |
| **FR-SM-008** | **Module Configuration** | The settings system MUST be used for module-level configuration (e.g., enabling/disabling features within a module). |

### 2.2 Non-Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **SR-SM-001** | **Encrypted Settings** | Settings of type `encrypted` MUST be securely encrypted and decrypted automatically when accessed. |
| **PR-SM-001** | **Caching** | All settings MUST be cached to minimize database queries. The cache MUST be automatically cleared when a setting is updated. |
| **EV-SM-001** | **Event-Driven** | An event (`SettingChanged`) MUST be dispatched whenever a setting is updated, allowing other parts of the system to react. |

## 3. Data Model

### `settings` table

| Column | Type | Modifiers | Description |
|---|---|---|---|
| `id` | `uuid` | Primary Key | Unique identifier for the setting entry. |
| `group` | `string` | | The group the setting belongs to (e.g., 'security'). |
| `key` | `string` | | The unique key for the setting (e.g., 'password_expiration_days'). |
| `value` | `text` | | The value of the setting. |
| `type` | `string` | | The data type (`string`, `integer`, `boolean`, `json`, `encrypted`). |
| `tenant_id` | `uuid` | Nullable, Foreign Key | The tenant this setting applies to. `NULL` for system-level. |
| `user_id` | `uuid` | Nullable, Foreign Key | The user this preference applies to. `NULL` for system/tenant level. |

## 4. Acceptance Criteria

- A Super Admin sets a system-wide default for `session_timeout` to 60 minutes.
- A Tenant Admin for Tenant A overrides `session_timeout` to 30 minutes for their tenant.
- A user in Tenant A logs in and their session correctly times out after 30 minutes.
- A user in Tenant B (which has no override) logs in and their session times out after 60 minutes.
- An API key stored as an `encrypted` setting is not readable directly from the database but is usable by the application.
- Updating a setting via the API or CLI clears the relevant cache entry.
