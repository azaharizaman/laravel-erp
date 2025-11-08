---
goal: Implement Authentication & Authorization System with RBAC
version: 1.0
date_created: 2025-11-08
last_updated: 2025-11-08
owner: Core Domain Team
status: 'Planned'
tags: [infrastructure, core, authentication, authorization, rbac, security, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan covers the complete authentication and authorization system for the Laravel ERP. The system provides secure API token-based authentication using Laravel Sanctum, role-based access control (RBAC) using Spatie Laravel Permission, and comprehensive user management capabilities. This infrastructure is critical for securing all ERP operations and enforcing proper access controls across tenant boundaries.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Implement User model with UUID primary key and tenant relationship
- **REQ-002**: Integrate Laravel Sanctum for stateless API token authentication
- **REQ-003**: Implement multi-factor authentication (MFA) support via OTP
- **REQ-004**: Integrate Spatie Laravel Permission for role and permission management
- **REQ-005**: Implement predefined roles: Super Admin, Tenant Admin, Manager, User, API Client
- **REQ-006**: Support role hierarchy with permission inheritance
- **REQ-007**: Implement per-resource and per-action permissions
- **REQ-008**: Create authorization policies for all domain models
- **REQ-009**: Implement password reset with secure token generation
- **REQ-010**: Support user status management (active, inactive, locked, suspended)
- **REQ-011**: Implement API rate limiting per user and per tenant
- **REQ-012**: Support token scoping for fine-grained API access control
- **REQ-013**: Implement token expiration and refresh mechanism
- **REQ-014**: Create RESTful API endpoints for authentication operations
- **REQ-015**: Implement CLI commands for user and role management

**Security Requirements:**
- **SEC-001**: Hash all passwords using bcrypt with minimum cost factor of 12
- **SEC-002**: Enforce password complexity: minimum 12 characters, mixed case, numbers, symbols
- **SEC-003**: Implement account lockout after 5 failed login attempts
- **SEC-004**: Expire password reset tokens after 1 hour
- **SEC-005**: Log all authentication events (login, logout, failed attempts)
- **SEC-006**: Implement secure session management with CSRF protection
- **SEC-007**: Validate tenant_id in all authorization checks to prevent cross-tenant access
- **SEC-008**: Encrypt MFA secrets using Laravel's encryption
- **SEC-009**: Implement API token revocation mechanism
- **SEC-010**: Support IP whitelisting for API tokens (optional)

**Performance Constraints:**
- **CON-001**: Authentication operations must complete within 500ms
- **CON-002**: Permission checks must not add more than 10ms overhead per request
- **CON-003**: Support minimum 100 concurrent authentication requests per second
- **CON-004**: Cache user permissions for 1 hour to reduce database queries

**Integration Guidelines:**
- **GUD-001**: All API routes must use auth:sanctum middleware
- **GUD-002**: Apply rate limiting middleware to auth endpoints
- **GUD-003**: Use Laravel's built-in authorization gates and policies
- **GUD-004**: Follow PSR-4 autoloading standards for all classes
- **GUD-005**: Use typed properties and strict types throughout

**Design Patterns:**
- **PAT-001**: Use Laravel Sanctum's HasApiTokens trait for User model
- **PAT-002**: Use Spatie's HasRoles trait for RBAC functionality
- **PAT-003**: Implement policy-based authorization for all resources
- **PAT-004**: Use action pattern for complex authentication operations
- **PAT-005**: Apply service layer pattern for business logic

## 2. Implementation Steps

### Implementation Phase 1: User Model & Database Schema

- GOAL-001: Set up user model, database schema, and relationships

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Modify existing users table migration to use UUID for id column | | |
| TASK-002 | Add columns to users: tenant_id (FK), status (enum), email_verified_at, password, remember_token, last_login_at | | |
| TASK-003 | Add columns to users: mfa_enabled (boolean), mfa_secret (encrypted), failed_login_attempts (integer), locked_until (timestamp) | | |
| TASK-004 | Add indexes: (tenant_id, email) unique, (tenant_id, status), email, last_login_at | | |
| TASK-004 | Create UserStatus enum in app/Domains/Core/Enums/UserStatus.php with values: ACTIVE, INACTIVE, LOCKED, SUSPENDED | | |
| TASK-005 | Update User model in app/Models/User.php to use UUID, HasApiTokens, HasRoles, BelongsToTenant, LogsActivity traits | | |
| TASK-006 | Add fillable fields, hidden fields (password, mfa_secret), and casts to User model | | |
| TASK-007 | Add tenant() relationship to User model | | |
| TASK-008 | Create UserFactory in database/factories/UserFactory.php with realistic test data | | |

### Implementation Phase 2: Laravel Sanctum Integration

- GOAL-002: Configure Laravel Sanctum for API token authentication

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Install laravel/sanctum package via composer require | | |
| TASK-010 | Publish Sanctum configuration: php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" | | |
| TASK-011 | Run Sanctum migrations for personal_access_tokens table | | |
| TASK-012 | Configure Sanctum in config/sanctum.php: token expiration (8 hours), stateful domains, middleware | | |
| TASK-013 | Add Sanctum middleware to api middleware group in app/Http/Kernel.php | | |
| TASK-014 | Configure token abilities/scopes in config/sanctum.php for fine-grained access control | | |

### Implementation Phase 3: Spatie Permission Integration

- GOAL-003: Integrate Spatie Laravel Permission for RBAC

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Install spatie/laravel-permission package via composer require | | |
| TASK-016 | Publish Spatie Permission configuration and migrations | | |
| TASK-017 | Run permission migrations for roles, permissions, and pivot tables | | |
| TASK-018 | Configure permission in config/permission.php: enable teams (tenants), cache configuration | | |
| TASK-019 | Add HasRoles trait to User model | | |
| TASK-020 | Register permission service provider in config/app.php | | |

### Implementation Phase 4: Roles & Permissions Setup

- GOAL-004: Define and seed predefined roles and permissions

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create PermissionSeeder in database/seeders/PermissionSeeder.php | | |
| TASK-022 | Define core permissions: view users, create users, update users, delete users, manage roles, manage permissions | | |
| TASK-023 | Define domain permissions: inventory.*, sales.*, purchasing.*, backoffice.*, accounting.* with CRUD operations | | |
| TASK-024 | Create RoleSeeder in database/seeders/RoleSeeder.php | | |
| TASK-025 | Define Super Admin role with all permissions | | |
| TASK-026 | Define Tenant Admin role with tenant-scoped permissions | | |
| TASK-027 | Define Manager role with limited management permissions | | |
| TASK-028 | Define User role with basic operational permissions | | |
| TASK-029 | Define API Client role with read-only API access | | |
| TASK-030 | Run seeders to populate roles and permissions | | |

### Implementation Phase 5: Authentication Actions

- GOAL-005: Implement authentication business logic using Action pattern

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-031 | Create RegisterUserAction in app/Domains/Core/Actions/Auth/RegisterUserAction.php | | |
| TASK-032 | Implement validation: unique email per tenant, password complexity, required fields | | |
| TASK-033 | Hash password, create user record, dispatch UserRegisteredEvent | | |
| TASK-034 | Create LoginUserAction in app/Domains/Core/Actions/Auth/LoginUserAction.php | | |
| TASK-035 | Implement credential validation, status check, lockout check, MFA verification if enabled | | |
| TASK-036 | Generate Sanctum token with abilities based on user roles | | |
| TASK-037 | Update last_login_at, reset failed_login_attempts, dispatch UserLoggedInEvent | | |
| TASK-038 | Create LogoutUserAction in app/Domains/Core/Actions/Auth/LogoutUserAction.php | | |
| TASK-039 | Revoke current token, dispatch UserLoggedOutEvent | | |
| TASK-040 | Create RefreshTokenAction in app/Domains/Core/Actions/Auth/RefreshTokenAction.php | | |
| TASK-041 | Revoke old token, generate new token with extended expiration | | |
| TASK-042 | Create RequestPasswordResetAction in app/Domains/Core/Actions/Auth/RequestPasswordResetAction.php | | |
| TASK-043 | Generate secure token, store in password_resets table, send email notification | | |
| TASK-044 | Create ResetPasswordAction in app/Domains/Core/Actions/Auth/ResetPasswordAction.php | | |
| TASK-045 | Validate token, update password, invalidate all existing tokens, dispatch PasswordResetEvent | | |

### Implementation Phase 6: MFA (Multi-Factor Authentication)

- GOAL-006: Implement TOTP-based multi-factor authentication

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-046 | Install pragmarx/google2fa package via composer require | | |
| TASK-047 | Create EnableMfaAction in app/Domains/Core/Actions/Auth/EnableMfaAction.php | | |
| TASK-048 | Generate MFA secret using Google2FA, encrypt and store in user record | | |
| TASK-049 | Return QR code data URL for user to scan with authenticator app | | |
| TASK-050 | Create VerifyMfaAction in app/Domains/Core/Actions/Auth/VerifyMfaAction.php | | |
| TASK-051 | Validate OTP code against stored secret using Google2FA | | |
| TASK-052 | Implement backup codes generation (10 single-use codes) | | |
| TASK-053 | Create DisableMfaAction in app/Domains/Core/Actions/Auth/DisableMfaAction.php | | |
| TASK-054 | Verify password before disabling, clear mfa_secret and backup codes | | |

### Implementation Phase 7: Account Security Features

- GOAL-007: Implement account lockout, failed login tracking, and security features

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-055 | Create HandleFailedLoginAction in app/Domains/Core/Actions/Auth/HandleFailedLoginAction.php | | |
| TASK-056 | Increment failed_login_attempts counter on failed login | | |
| TASK-057 | Lock account for 30 minutes after 5 failed attempts by setting locked_until | | |
| TASK-058 | Dispatch AccountLockedEvent for notification | | |
| TASK-059 | Create UnlockAccountAction in app/Domains/Core/Actions/Auth/UnlockAccountAction.php | | |
| TASK-060 | Reset failed_login_attempts, clear locked_until, dispatch AccountUnlockedEvent | | |
| TASK-061 | Create ChangePasswordAction in app/Domains/Core/Actions/Auth/ChangePasswordAction.php | | |
| TASK-062 | Verify current password, validate new password, update password hash | | |
| TASK-063 | Revoke all tokens except current, dispatch PasswordChangedEvent | | |

### Implementation Phase 8: API Endpoints

- GOAL-008: Build RESTful API endpoints for authentication

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-064 | Create AuthController in app/Http/Controllers/Api/V1/AuthController.php | | |
| TASK-065 | Implement register() method calling RegisterUserAction, return user and token | | |
| TASK-066 | Implement login() method calling LoginUserAction, return user and token | | |
| TASK-067 | Implement logout() method calling LogoutUserAction, return 204 No Content | | |
| TASK-068 | Implement refresh() method calling RefreshTokenAction, return new token | | |
| TASK-069 | Implement forgotPassword() method calling RequestPasswordResetAction, return 200 OK | | |
| TASK-070 | Implement resetPassword() method calling ResetPasswordAction, return 200 OK | | |
| TASK-071 | Implement me() method returning authenticated user with roles and permissions | | |
| TASK-072 | Create MfaController in app/Http/Controllers/Api/V1/MfaController.php | | |
| TASK-073 | Implement enable() method calling EnableMfaAction, return QR code | | |
| TASK-074 | Implement verify() method calling VerifyMfaAction, confirm activation | | |
| TASK-075 | Implement disable() method calling DisableMfaAction, return 200 OK | | |

### Implementation Phase 9: Request Validation

- GOAL-009: Create form request classes for input validation

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-076 | Create RegisterRequest in app/Http/Requests/Auth/RegisterRequest.php | | |
| TASK-077 | Add validation rules: email (required, email, unique:users,email), password (required, min:12, confirmed), name (required, string, max:255) | | |
| TASK-078 | Add password regex for complexity: uppercase, lowercase, numbers, symbols | | |
| TASK-079 | Create LoginRequest in app/Http/Requests/Auth/LoginRequest.php | | |
| TASK-080 | Add validation rules: email (required, email), password (required), otp_code (nullable, digits:6) | | |
| TASK-081 | Create ForgotPasswordRequest in app/Http/Requests/Auth/ForgotPasswordRequest.php | | |
| TASK-082 | Add validation rules: email (required, email, exists:users,email) | | |
| TASK-083 | Create ResetPasswordRequest in app/Http/Requests/Auth/ResetPasswordRequest.php | | |
| TASK-084 | Add validation rules: token (required), email (required, email), password (required, min:12, confirmed) | | |
| TASK-085 | Create ChangePasswordRequest in app/Http/Requests/Auth/ChangePasswordRequest.php | | |
| TASK-086 | Add validation rules: current_password (required), new_password (required, min:12, confirmed, different:current_password) | | |

### Implementation Phase 10: User Management API

- GOAL-010: Create user management endpoints for admin operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-087 | Create UserController in app/Http/Controllers/Api/V1/UserController.php | | |
| TASK-088 | Implement index() method with pagination, filtering by status, role, search term | | |
| TASK-089 | Implement show() method returning user with roles, permissions, tenant | | |
| TASK-090 | Implement store() method creating new user (admin only) | | |
| TASK-091 | Implement update() method updating user details (admin only) | | |
| TASK-092 | Implement destroy() method soft-deleting user (admin only) | | |
| TASK-093 | Implement assignRole() method to assign roles to user | | |
| TASK-094 | Implement removeRole() method to remove roles from user | | |
| TASK-095 | Create UserResource in app/Http/Resources/UserResource.php for response transformation | | |
| TASK-096 | Include roles, permissions, tenant data in UserResource | | |

### Implementation Phase 11: Role & Permission Management API

- GOAL-011: Create role and permission management endpoints

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-097 | Create RoleController in app/Http/Controllers/Api/V1/RoleController.php | | |
| TASK-098 | Implement index() method listing all roles with permissions count | | |
| TASK-099 | Implement show() method returning role with associated permissions | | |
| TASK-100 | Implement store() method creating new role with permissions (super admin only) | | |
| TASK-101 | Implement update() method updating role and permissions (super admin only) | | |
| TASK-102 | Implement destroy() method deleting role (super admin only) | | |
| TASK-103 | Create PermissionController in app/Http/Controllers/Api/V1/PermissionController.php | | |
| TASK-104 | Implement index() method listing all permissions grouped by domain | | |
| TASK-105 | Implement store() method creating new permission (super admin only) | | |
| TASK-106 | Create RoleResource and PermissionResource for response transformation | | |

### Implementation Phase 12: Authorization Policies

- GOAL-012: Implement authorization policies for key resources

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-107 | Create UserPolicy in app/Domains/Core/Policies/UserPolicy.php | | |
| TASK-108 | Implement viewAny() checking 'view users' permission | | |
| TASK-109 | Implement view() checking 'view users' permission and same tenant | | |
| TASK-110 | Implement create() checking 'create users' permission | | |
| TASK-111 | Implement update() checking 'update users' permission and same tenant | | |
| TASK-112 | Implement delete() checking 'delete users' permission and same tenant | | |
| TASK-113 | Implement assignRole() checking 'manage roles' permission | | |
| TASK-114 | Register UserPolicy in AuthServiceProvider | | |
| TASK-115 | Apply authorizeResource() in UserController constructor | | |

### Implementation Phase 13: CLI Commands

- GOAL-013: Create CLI commands for user and role management

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-116 | Create CreateUserCommand in app/Console/Commands/User/CreateUserCommand.php with signature erp:user:create | | |
| TASK-117 | Add options: --email, --password, --name, --role, --tenant with interactive prompts | | |
| TASK-118 | Call RegisterUserAction and assign role, display success with user ID | | |
| TASK-119 | Create AssignRoleCommand in app/Console/Commands/User/AssignRoleCommand.php with signature erp:user:assign-role {user} {role} | | |
| TASK-120 | Validate user and role exist, assign role, display confirmation | | |
| TASK-121 | Create CreateRoleCommand in app/Console/Commands/Role/CreateRoleCommand.php with signature erp:role:create {name} | | |
| TASK-122 | Add option --permissions for comma-separated permission list | | |
| TASK-123 | Create role with permissions, display success message | | |
| TASK-124 | Create CreatePermissionCommand in app/Console/Commands/Permission/CreatePermissionCommand.php with signature erp:permission:create {name} | | |
| TASK-125 | Add options: --domain, --action for structured permission naming | | |
| TASK-126 | Create permission, display success message | | |
| TASK-127 | Register all commands in app/Console/Kernel.php | | |

### Implementation Phase 14: Rate Limiting

- GOAL-014: Implement API rate limiting per user and tenant

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-128 | Configure rate limiting in app/Providers/RouteServiceProvider.php | | |
| TASK-129 | Define auth rate limiter: 5 requests per minute for login/register endpoints | | |
| TASK-130 | Define api rate limiter: 60 requests per minute per user | | |
| TASK-131 | Define tenant rate limiter: 1000 requests per minute per tenant | | |
| TASK-132 | Apply throttle middleware to auth routes in routes/api.php | | |
| TASK-133 | Apply throttle middleware to API routes in routes/api.php | | |
| TASK-134 | Return 429 Too Many Requests with Retry-After header on limit exceeded | | |

### Implementation Phase 15: Events & Listeners

- GOAL-015: Implement event-driven architecture for authentication lifecycle

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-135 | Create UserRegisteredEvent in app/Domains/Core/Events/UserRegisteredEvent.php | | |
| TASK-136 | Create UserLoggedInEvent in app/Domains/Core/Events/UserLoggedInEvent.php | | |
| TASK-137 | Create UserLoggedOutEvent in app/Domains/Core/Events/UserLoggedOutEvent.php | | |
| TASK-138 | Create PasswordResetEvent in app/Domains/Core/Events/PasswordResetEvent.php | | |
| TASK-139 | Create PasswordChangedEvent in app/Domains/Core/Events/PasswordChangedEvent.php | | |
| TASK-140 | Create AccountLockedEvent in app/Domains/Core/Events/AccountLockedEvent.php | | |
| TASK-141 | Create AccountUnlockedEvent in app/Domains/Core/Events/AccountUnlockedEvent.php | | |
| TASK-142 | Create LogAuthenticationAttemptListener in app/Domains/Core/Listeners/LogAuthenticationAttemptListener.php | | |
| TASK-143 | Implement handle() method to log all authentication events to activity log | | |
| TASK-144 | Create SendWelcomeEmailListener in app/Domains/Core/Listeners/SendWelcomeEmailListener.php | | |
| TASK-145 | Implement handle() method to send welcome email on user registration | | |
| TASK-146 | Create NotifyAccountLockedListener in app/Domains/Core/Listeners/NotifyAccountLockedListener.php | | |
| TASK-147 | Implement handle() method to send email notification on account lock | | |
| TASK-148 | Register events and listeners in EventServiceProvider | | |

### Implementation Phase 16: Routes Definition

- GOAL-016: Define all authentication and user management routes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-149 | Define public routes in routes/api.php: POST /api/v1/auth/register, POST /api/v1/auth/login, POST /api/v1/auth/forgot-password, POST /api/v1/auth/reset-password | | |
| TASK-150 | Apply throttle:auth middleware to auth routes | | |
| TASK-151 | Define protected routes with auth:sanctum middleware: POST /api/v1/auth/logout, POST /api/v1/auth/refresh, GET /api/v1/auth/me | | |
| TASK-152 | Define MFA routes with auth:sanctum: POST /api/v1/auth/mfa/enable, POST /api/v1/auth/mfa/verify, POST /api/v1/auth/mfa/disable | | |
| TASK-153 | Define user management routes with auth:sanctum: GET/POST /api/v1/users, GET/PATCH/DELETE /api/v1/users/{id} | | |
| TASK-154 | Define role management routes with auth:sanctum: GET/POST /api/v1/roles, GET/PATCH/DELETE /api/v1/roles/{id} | | |
| TASK-155 | Define permission routes with auth:sanctum: GET/POST /api/v1/permissions | | |
| TASK-156 | Apply throttle:api middleware to all authenticated routes | | |

### Implementation Phase 17: Testing

- GOAL-017: Create comprehensive test suite for authentication and authorization

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-157 | Create AuthenticationTest feature test in tests/Feature/Core/AuthenticationTest.php | | |
| TASK-158 | Test user registration with valid data returns 201 and token | | |
| TASK-159 | Test registration fails with duplicate email (422) | | |
| TASK-160 | Test registration fails with weak password (422) | | |
| TASK-161 | Test user login with valid credentials returns token | | |
| TASK-162 | Test login fails with invalid credentials (401) | | |
| TASK-163 | Test login fails for locked account (403) | | |
| TASK-164 | Test account locks after 5 failed login attempts | | |
| TASK-165 | Test MFA login flow with valid OTP code | | |
| TASK-166 | Test MFA login fails with invalid OTP code | | |
| TASK-167 | Test logout revokes token successfully | | |
| TASK-168 | Test token refresh generates new token | | |
| TASK-169 | Test password reset flow end-to-end | | |
| TASK-170 | Create AuthorizationTest feature test in tests/Feature/Core/AuthorizationTest.php | | |
| TASK-171 | Test user with permission can access resource | | |
| TASK-172 | Test user without permission receives 403 | | |
| TASK-173 | Test user cannot access another tenant's resources | | |
| TASK-174 | Test role assignment and permission inheritance | | |
| TASK-175 | Test Super Admin has access to all resources | | |
| TASK-176 | Create RateLimitingTest feature test in tests/Feature/Core/RateLimitingTest.php | | |
| TASK-177 | Test rate limiting returns 429 after exceeding limit | | |
| TASK-178 | Test Retry-After header is present in 429 response | | |

## 3. Alternatives

- **ALT-001**: JWT-based authentication instead of Laravel Sanctum - Rejected because Sanctum is Laravel's official solution, better integrated with the ecosystem, and simpler to implement for API-only applications. JWT adds unnecessary complexity.

- **ALT-002**: Session-based authentication - Rejected because the system is headless/API-only. Stateless token authentication is more appropriate for this architecture and better supports mobile/third-party clients.

- **ALT-003**: Custom RBAC implementation - Rejected in favor of Spatie Laravel Permission which is battle-tested, well-documented, and provides all required features including team/tenant scoping.

- **ALT-004**: OAuth2 with Laravel Passport - Rejected as overkill for current requirements. Sanctum provides sufficient token-based auth. Passport could be added later if third-party OAuth flows are needed.

- **ALT-005**: SMS-based MFA instead of TOTP - Considered but TOTP chosen as primary method due to lower cost and better user experience. SMS-based MFA can be added as alternative method in future.

## 4. Dependencies

- **DEP-001**: Laravel 12.x framework installed and configured
- **DEP-002**: PHP 8.2+ for enum support and typed properties
- **DEP-003**: Tenant system (PRD-01) must be implemented first as User model depends on tenant_id
- **DEP-004**: laravel/sanctum package (install via composer)
- **DEP-005**: spatie/laravel-permission package (install via composer)
- **DEP-006**: pragmarx/google2fa package for MFA (install via composer)
- **DEP-007**: spatie/laravel-activitylog for authentication audit logging
- **DEP-008**: Email configuration for password reset and notifications
- **DEP-009**: Redis or cache driver for rate limiting and permission caching

## 5. Files

**New Files to Create:**
- **FILE-001**: app/Domains/Core/Enums/UserStatus.php - User status enum
- **FILE-002**: app/Domains/Core/Actions/Auth/RegisterUserAction.php - User registration
- **FILE-003**: app/Domains/Core/Actions/Auth/LoginUserAction.php - User login
- **FILE-004**: app/Domains/Core/Actions/Auth/LogoutUserAction.php - User logout
- **FILE-005**: app/Domains/Core/Actions/Auth/RefreshTokenAction.php - Token refresh
- **FILE-006**: app/Domains/Core/Actions/Auth/RequestPasswordResetAction.php - Password reset request
- **FILE-007**: app/Domains/Core/Actions/Auth/ResetPasswordAction.php - Password reset
- **FILE-008**: app/Domains/Core/Actions/Auth/ChangePasswordAction.php - Change password
- **FILE-009**: app/Domains/Core/Actions/Auth/EnableMfaAction.php - Enable MFA
- **FILE-010**: app/Domains/Core/Actions/Auth/VerifyMfaAction.php - Verify MFA
- **FILE-011**: app/Domains/Core/Actions/Auth/DisableMfaAction.php - Disable MFA
- **FILE-012**: app/Domains/Core/Actions/Auth/HandleFailedLoginAction.php - Failed login handling
- **FILE-013**: app/Domains/Core/Actions/Auth/UnlockAccountAction.php - Unlock account
- **FILE-014**: app/Http/Controllers/Api/V1/AuthController.php - Auth API controller
- **FILE-015**: app/Http/Controllers/Api/V1/MfaController.php - MFA API controller
- **FILE-016**: app/Http/Controllers/Api/V1/UserController.php - User management API
- **FILE-017**: app/Http/Controllers/Api/V1/RoleController.php - Role management API
- **FILE-018**: app/Http/Controllers/Api/V1/PermissionController.php - Permission management API
- **FILE-019**: app/Http/Requests/Auth/RegisterRequest.php - Registration validation
- **FILE-020**: app/Http/Requests/Auth/LoginRequest.php - Login validation
- **FILE-021**: app/Http/Requests/Auth/ForgotPasswordRequest.php - Forgot password validation
- **FILE-022**: app/Http/Requests/Auth/ResetPasswordRequest.php - Reset password validation
- **FILE-023**: app/Http/Requests/Auth/ChangePasswordRequest.php - Change password validation
- **FILE-024**: app/Http/Resources/UserResource.php - User API resource
- **FILE-025**: app/Http/Resources/RoleResource.php - Role API resource
- **FILE-026**: app/Http/Resources/PermissionResource.php - Permission API resource
- **FILE-027**: app/Domains/Core/Policies/UserPolicy.php - User authorization policy
- **FILE-028**: app/Domains/Core/Events/UserRegisteredEvent.php - User registered event
- **FILE-029**: app/Domains/Core/Events/UserLoggedInEvent.php - User logged in event
- **FILE-030**: app/Domains/Core/Events/UserLoggedOutEvent.php - User logged out event
- **FILE-031**: app/Domains/Core/Events/PasswordResetEvent.php - Password reset event
- **FILE-032**: app/Domains/Core/Events/PasswordChangedEvent.php - Password changed event
- **FILE-033**: app/Domains/Core/Events/AccountLockedEvent.php - Account locked event
- **FILE-034**: app/Domains/Core/Events/AccountUnlockedEvent.php - Account unlocked event
- **FILE-035**: app/Domains/Core/Listeners/LogAuthenticationAttemptListener.php - Log auth attempts
- **FILE-036**: app/Domains/Core/Listeners/SendWelcomeEmailListener.php - Send welcome email
- **FILE-037**: app/Domains/Core/Listeners/NotifyAccountLockedListener.php - Notify account locked
- **FILE-038**: app/Console/Commands/User/CreateUserCommand.php - CLI create user
- **FILE-039**: app/Console/Commands/User/AssignRoleCommand.php - CLI assign role
- **FILE-040**: app/Console/Commands/Role/CreateRoleCommand.php - CLI create role
- **FILE-041**: app/Console/Commands/Permission/CreatePermissionCommand.php - CLI create permission
- **FILE-042**: database/seeders/PermissionSeeder.php - Permission seeder
- **FILE-043**: database/seeders/RoleSeeder.php - Role seeder

**Files to Modify:**
- **FILE-044**: app/Models/User.php - Add traits, relationships, and properties
- **FILE-045**: database/migrations/YYYY_MM_DD_HHMMSS_create_users_table.php - Modify schema
- **FILE-046**: database/migrations/YYYY_MM_DD_HHMMSS_add_auth_fields_to_users_table.php - Add MFA and security fields
- **FILE-047**: config/sanctum.php - Configure Sanctum
- **FILE-048**: config/permission.php - Configure Spatie Permission
- **FILE-049**: app/Http/Kernel.php - Register middleware
- **FILE-050**: app/Providers/AuthServiceProvider.php - Register policies
- **FILE-051**: app/Providers/EventServiceProvider.php - Register events
- **FILE-052**: app/Providers/RouteServiceProvider.php - Configure rate limiting
- **FILE-053**: routes/api.php - Define auth routes
- **FILE-054**: database/seeders/DatabaseSeeder.php - Call permission and role seeders

**Test Files:**
- **FILE-055**: tests/Feature/Core/AuthenticationTest.php - Authentication feature tests
- **FILE-056**: tests/Feature/Core/AuthorizationTest.php - Authorization feature tests
- **FILE-057**: tests/Feature/Core/RateLimitingTest.php - Rate limiting tests
- **FILE-058**: tests/Unit/Core/Actions/Auth/RegisterUserActionTest.php - Unit tests
- **FILE-059**: tests/Unit/Core/Actions/Auth/LoginUserActionTest.php - Unit tests

## 6. Testing

**Unit Tests:**
- **TEST-001**: Test RegisterUserAction validates unique email per tenant
- **TEST-002**: Test RegisterUserAction hashes password correctly
- **TEST-003**: Test RegisterUserAction dispatches UserRegisteredEvent
- **TEST-004**: Test LoginUserAction validates credentials
- **TEST-005**: Test LoginUserAction checks account status
- **TEST-006**: Test LoginUserAction generates token with correct abilities
- **TEST-007**: Test LoginUserAction updates last_login_at
- **TEST-008**: Test HandleFailedLoginAction increments counter
- **TEST-009**: Test HandleFailedLoginAction locks account after 5 failures
- **TEST-010**: Test EnableMfaAction generates valid secret
- **TEST-011**: Test VerifyMfaAction validates OTP correctly
- **TEST-012**: Test ResetPasswordAction invalidates old tokens

**Feature Tests:**
- **TEST-013**: Test POST /api/v1/auth/register creates user and returns token
- **TEST-014**: Test POST /api/v1/auth/register fails with duplicate email (422)
- **TEST-015**: Test POST /api/v1/auth/register fails with weak password (422)
- **TEST-016**: Test POST /api/v1/auth/login returns token with valid credentials
- **TEST-017**: Test POST /api/v1/auth/login fails with invalid credentials (401)
- **TEST-018**: Test POST /api/v1/auth/login fails for locked account (403)
- **TEST-019**: Test account locks after 5 failed login attempts
- **TEST-020**: Test POST /api/v1/auth/logout revokes token
- **TEST-021**: Test POST /api/v1/auth/refresh generates new token
- **TEST-022**: Test GET /api/v1/auth/me returns authenticated user
- **TEST-023**: Test password reset flow from request to reset
- **TEST-024**: Test MFA enable flow returns QR code
- **TEST-025**: Test MFA verify confirms activation
- **TEST-026**: Test login with MFA requires OTP code
- **TEST-027**: Test user with 'view users' permission can list users
- **TEST-028**: Test user without permission receives 403
- **TEST-029**: Test user cannot access another tenant's users
- **TEST-030**: Test Super Admin can access all resources
- **TEST-031**: Test role assignment grants permissions
- **TEST-032**: Test rate limiting returns 429 after limit exceeded

**Integration Tests:**
- **TEST-033**: Test complete registration to login flow
- **TEST-034**: Test authentication events trigger listeners
- **TEST-035**: Test permission caching reduces database queries
- **TEST-036**: Test tenant isolation in authorization checks

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Brute force attacks on login endpoint - Mitigation: Rate limiting, account lockout, CAPTCHA consideration for future
- **RISK-002**: Token theft/leakage - Mitigation: Short expiration times, HTTPS only, token revocation on password change
- **RISK-003**: Permission check performance at scale - Mitigation: Aggressive permission caching, database query optimization
- **RISK-004**: MFA secret compromise - Mitigation: Encryption at rest, secure backup codes, ability to disable MFA
- **RISK-005**: Account takeover via password reset - Mitigation: Secure token generation, short expiration, email verification
- **RISK-006**: Privilege escalation bugs - Mitigation: Comprehensive testing, code review, audit logging of permission changes
- **RISK-007**: Session fixation attacks - Mitigation: Regenerate tokens on login, invalidate old tokens

**Assumptions:**
- **ASSUMPTION-001**: Email service is properly configured for notifications
- **ASSUMPTION-002**: HTTPS is enforced in production for secure token transmission
- **ASSUMPTION-003**: Redis or similar cache is available for rate limiting and permission caching
- **ASSUMPTION-004**: Users will primarily use email/password authentication (no social login required)
- **ASSUMPTION-005**: Token expiration of 8 hours is acceptable for user experience
- **ASSUMPTION-006**: TOTP-based MFA is sufficient (no hardware token requirement)
- **ASSUMPTION-007**: Five-tier role hierarchy is sufficient for initial implementation
- **ASSUMPTION-008**: Account lockout of 30 minutes is acceptable security tradeoff

## 8. Related Specifications / Further Reading

- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Overall Phase 1 requirements
- [PRD-01-infrastructure-multitenancy-1.md](./PRD-01-infrastructure-multitenancy-1.md) - Multi-tenancy system (prerequisite)
- [Laravel Sanctum Documentation](https://laravel.com/docs/12.x/sanctum)
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission/v6/introduction)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- [Google2FA Documentation](https://github.com/antonioribeiro/google2fa)
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Module development guidelines
