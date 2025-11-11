# PRD01-SUB02: Authentication & Authorization

**Master PRD:** [PRD01-MVP.md](./PRD01-MVP.md)
**Module:** Core.002
**Implementation Plan:** [PLAN02-implement-authentication.md](../plan/PLAN02-implement-authentication.md)
**Status:** 🚧 In Progress

---

## 1. Overview

This document specifies the requirements for the Authentication and Authorization system. It covers user identity, secure access via API tokens, and a robust, tenant-scoped Role-Based Access Control (RBAC) system.

## 2. Requirements

### 2.1 Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **FR-AA-001** | **User Model** | The system MUST have a `User` model with a UUID primary key and a mandatory relationship to a `Tenant`. |
| **FR-AA-002** | **API Authentication** | All API access MUST be authenticated using stateless tokens provided via Laravel Sanctum. |
| **FR-AA-003** | **RBAC System** | The system MUST implement a tenant-scoped Role-Based Access Control system using `spatie/laravel-permission`. |
| **FR-AA-004** | **Role Hierarchy** | A default role hierarchy MUST be created for each new tenant: Super Admin (global), Tenant Admin, Manager, User, API Client. |
| **FR-AA-005** | **Permission Structure** | Permissions MUST follow a `domain.action` naming convention (e.g., `inventory.view`, `sales.create`). |
| **FR-AA-006** | **Password Security** | Passwords MUST be hashed using bcrypt with a cost factor of 12. |
| **FR-AA-007** | **Password Policy** | Passwords MUST have a minimum length of 12 characters and include mixed case, numbers, and symbols. |
| **FR-AA-008** | **Account Lockout** | User accounts MUST be locked for 30 minutes after 5 failed login attempts. |
| **FR-AA-009** | **Token Expiration** | API tokens MUST have a configurable expiration time, defaulting to 8 hours. |
| **FR-AA-010** | **Auth Endpoints** | The API MUST provide endpoints for user registration, login, and logout. |
| **FR-AA-011** | **Password Reset** | A secure password reset flow MUST be implemented. |

### 2.2 Non-Functional Requirements

| ID | Requirement | Details |
|---|---|---|
| **SR-AA-001** | **Tenant Scoping** | All roles and permissions MUST be strictly scoped to a tenant. A user's roles in Tenant A must not grant any access in Tenant B. |
| **SR-AA-002** | **MFA (Future)** | The architecture MUST allow for the future implementation of Multi-Factor Authentication (TOTP). |
| **SR-AA-003** | **Rate Limiting** | Authentication endpoints MUST be rate-limited to prevent brute-force attacks. |
| **PR-AA-001** | **Performance** | Authentication and permission checks should add less than 20ms to the total request time. |

## 3. Roles & Permissions

| Role | Scope | Description |
|---|---|---|
| **Super Admin** | Global | Has all permissions across all tenants. For system administration only. |
| **Tenant Admin** | Tenant | Full control over all resources and settings within their own tenant. |
| **Manager** | Tenant | Can manage users and perform most business operations within their tenant. |
| **User** | Tenant | Can perform basic day-to-day business operations. |
| **API Client** | Tenant | Limited, often read-only, access for third-party integrations. |

## 4. Acceptance Criteria

- A user can register, log in, and receive a Sanctum API token.
- A user's access is strictly limited by the permissions granted to their roles within their assigned tenant.
- A Tenant Admin can create, assign, and revoke roles and permissions for users within their tenant.
- Account lockout and password policies are enforced.
- All authentication-related events (login, logout, failed login) are logged in the audit trail.
