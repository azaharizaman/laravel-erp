# Nexus ERP - Enterprise Laravel ERP Package

![Status: In Development](https://img.shields.io/badge/status-In%20Development-yellow)
![PHP](https://img.shields.io/badge/PHP-8.3+-blue)
![Laravel](https://img.shields.io/badge/Laravel-12+-red)
![License](https://img.shields.io/badge/license-MIT-green)

**Enterprise-grade, headless ERP backend system** built with Laravel 12+ and PHP 8.3+. Designed to rival SAP, Odoo, and Microsoft Dynamics while maintaining superior modularity, extensibility, and agentic capabilities.

---

## 🎯 Overview

Nexus ERP is a **headless, API-first ERP system** providing comprehensive business management capabilities through RESTful APIs and CLI commands. This is a **composable Laravel package** that can be:

✅ **Required as a package:** `composer require nexus/erp`  
✅ **Run as a standalone application:** Clone and serve  
✅ **Integrated into existing Laravel apps:** Extend with your own features

### Key Characteristics

- 🏗️ **Architecture:** Package-first design with modular sub-packages
- 🔌 **Integration:** RESTful APIs (`/api/v1/`) and Artisan commands (`erp:`)
- 🎨 **Patterns:** Contract-driven, Domain-driven, Event-driven
- 🤖 **Target Users:** AI agents, custom frontends, automated systems
- 🧩 **Modularity:** Enable/disable modules without system-wide impact
- 🔒 **Security:** Zero-trust model, RBAC, multi-tenancy
- 📦 **Distribution:** Private Packagist / Satis ready

---

## 🚀 Quick Start

### As a Composer Package

```bash
# Create a new Laravel project
composer create-project laravel/laravel my-erp-app

# Require nexus/erp
cd my-erp-app
composer require nexus/erp

# Publish configuration and migrations
php artisan vendor:publish --tag=nexus-erp-config
php artisan vendor:publish --tag=nexus-erp-migrations

# Run migrations
php artisan migrate

# Start using!
```

**Use in your app:**
```php
use Nexus\Erp\Actions\Auth\LoginAction;
use Nexus\Erp\Models\User;

$token = LoginAction::run($email, $password, $deviceName, $tenantId);
```

### As a Standalone Application

```bash
# Clone the repository
git clone https://github.com/azaharizaman/nexus-erp.git
cd nexus-erp

# Install dependencies
cd apps/headless-erp-app
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database and run migrations
php artisan migrate

# Start the server
php artisan serve
```

---

## 📦 Repository Structure

```
nexus-erp/
├── src/                          # Package source (Nexus\Erp namespace)
│   ├── Actions/                  # Business operations
│   ├── Console/Commands/         # CLI commands
│   ├── Http/
│   │   ├── Controllers/          # API controllers
│   │   ├── Middleware/           # Request middleware
│   │   ├── Requests/             # Form requests
│   │   └── Resources/            # API resources
│   ├── Models/                   # Eloquent models
│   ├── Providers/                # Service providers
│   ├── Support/
│   │   ├── Contracts/            # Service contracts
│   │   ├── Services/             # Service implementations
│   │   ├── Traits/               # Reusable traits
│   │   └── Helpers/              # Helper functions
│   └── ErpServiceProvider.php    # Main service provider
│
├── apps/headless-erp-app/        # Optional standalone application
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── routes/
│   └── storage/
│
├── packages/                     # Sub-packages (modular)
│   ├── nexus-tenancy/            # Multi-tenancy (Nexus\Tenancy)
│   ├── nexus-sequencing/         # Serial numbering (Nexus\Sequencing)
│   ├── nexus-settings/           # Settings management (Nexus\Settings)
│   ├── nexus-backoffice/         # Organization structure (Nexus\Backoffice)
│   ├── nexus-inventory/          # Inventory operations (Nexus\Inventory)
│   ├── nexus-uom/                # Unit of measure (Nexus\Uom)
│   ├── nexus-audit-log/          # Audit logging (Nexus\AuditLog)
│   └── nexus-contracts/          # Shared contracts (Nexus\Contracts)
│
├── tests/                        # Test suite
├── docs/                         # Documentation
├── composer.json                 # Package definition
└── README.md                     # This file
```

---

## ✨ Features

### Core Features

- ✅ **Multi-Tenancy:** Complete tenant isolation with team-scoped permissions
- ✅ **Authentication:** API token authentication (Laravel Sanctum)
- ✅ **Authorization:** Role-based access control (RBAC) with Spatie Permission
- ✅ **Audit Logging:** Complete activity tracking with Spatie Activitylog
- ✅ **Search:** Full-text search with Laravel Scout
- ✅ **Settings Management:** Hierarchical settings (global → tenant → user)
- ✅ **Serial Numbering:** Auto-incrementing sequences for invoices, orders, etc.

### Domain Modules

- 📋 **Backoffice:** Company, Office, Department management
- 📦 **Inventory:** Items, warehouses, stock movements
- 📏 **Unit of Measure:** Conversion, compatibility checking
- 🏗️ **Sales:** (Coming soon) Customers, orders, pricing
- 🛒 **Purchasing:** (Coming soon) Vendors, POs, receipts
- 💰 **Accounting:** (Coming soon) GL, AP/AR, reporting

---

## 🛠️ Technology Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| **PHP** | ≥ 8.3 | Latest PHP features |
| **Laravel** | ≥ 12.x | Framework |
| **Database** | Agnostic | MySQL, PostgreSQL, SQLite, SQL Server |
| **Laravel Scout** | ^10.0 | Search (Meilisearch, Algolia, etc.) |
| **Laravel Sanctum** | ^4.2 | API authentication |
| **Spatie Permission** | ^6.0 | RBAC authorization |
| **Spatie Activitylog** | ^4.0 | Audit logging |
| **Laravel Actions** | ^2.0 | Action pattern |
| **Pest** | ^3.8 | Testing framework |

---

## 🏗️ Architecture

### Design Principles

1. **Package-First:** Core functionality in `src/` as a distributable package
2. **Contract-Driven:** All dependencies abstracted behind interfaces
3. **Domain-Driven:** Clear domain boundaries (Inventory, Sales, Purchasing, etc.)
4. **Event-Driven:** Cross-domain communication via events
5. **Repository Pattern:** Data access abstracted from business logic

### Namespace Structure

```
Nexus\Erp\              # Main package namespace
├── Actions\            # Business operations (Action pattern)
├── Http\               # HTTP layer (Controllers, Middleware, Requests)
├── Models\             # Eloquent models
├── Support\            # Infrastructure (Contracts, Services, Traits)
└── Providers\          # Service providers

Nexus\Tenancy\          # Sub-package: Multi-tenancy
Nexus\Inventory\        # Sub-package: Inventory management
Nexus\Backoffice\       # Sub-package: Organization structure
...                     # Other sub-packages
```

### Service Contracts

All external package dependencies are abstracted:

```php
// ❌ Never in business code
use Spatie\Activitylog\Traits\LogsActivity;
activity()->log('Action performed');

// ✅ Always use contracts
use Nexus\Erp\Support\Contracts\ActivityLoggerContract;

public function __construct(
    private readonly ActivityLoggerContract $activityLogger
) {}

$this->activityLogger->log('Action performed', $model);
```

---

## 🚀 Getting Started

### System Requirements

- PHP >= 8.3
- Composer >= 2.0
- MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.35+
- Redis (optional, for cache/sessions)
- Meilisearch (optional, for search)

### Installation (as Package)

1. **Require the package:**
   ```bash
   composer require nexus/erp
   ```

2. **Publish configuration:**
   ```bash
   php artisan vendor:publish --tag=nexus-erp-config
   ```

3. **Publish migrations:**
   ```bash
   php artisan vendor:publish --tag=nexus-erp-migrations
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   ```

5. **Seed database (optional):**
   ```bash
   php artisan db:seed --class=Nexus\\Erp\\Database\\Seeders\\ErpSeeder
   ```

### Installation (Standalone)

1. **Clone repository:**
   ```bash
   git clone https://github.com/azaharizaman/nexus-erp.git
   cd nexus-erp
   ```

2. **Install dependencies:**
   ```bash
   cd apps/headless-erp-app
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setup database:**
   ```bash
   # Configure DB_* in .env
   php artisan migrate
   php artisan db:seed
   ```

5. **Start server:**
   ```bash
   php artisan serve
   ```

---

## 📖 Usage Examples

### Authentication

```php
use Nexus\Erp\Actions\Auth\LoginAction;
use Nexus\Erp\Actions\Auth\RegisterUserAction;

// Login
$result = LoginAction::run(
    email: 'user@example.com',
    password: 'password',
    deviceName: 'web-browser',
    tenantId: $tenant->id
);

$token = $result['token'];
$user = $result['user'];

// Register
$user = RegisterUserAction::run([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password',
    'tenant_id' => $tenant->id,
]);
```

### API Requests

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password",
    "device_name": "web-browser",
    "tenant_id": "uuid-here"
  }'

# Use token
curl -X GET http://localhost:8000/api/v1/tenants \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Multi-Tenancy

```php
use function tenant;

// Get current tenant (set by middleware)
$currentTenant = tenant();

// Scoped queries (automatic via BelongsToTenant trait)
$users = User::all(); // Only returns users for current tenant
```

### RBAC Authorization

```php
use Nexus\Erp\Actions\Permission\CreateRoleAction;
use Nexus\Erp\Actions\Permission\AssignRoleToUserAction;

// Create role
$role = CreateRoleAction::run(
    name: 'inventory-manager',
    permissions: ['view-items', 'create-items', 'adjust-stock'],
    tenantId: $tenant->id
);

// Assign role
AssignRoleToUserAction::run($user, $role);

// Check permissions
if ($user->can('adjust-stock')) {
    // Allow stock adjustment
}
```

---

## 🧪 Testing

### Run Tests

```bash
# All tests
composer test

# Specific suite
composer test:feature
composer test:unit

# With coverage
composer test:coverage

# Parallel execution
composer test -- --parallel
```

### Test Structure

```
tests/
├── Feature/              # Feature tests (HTTP, Integration)
│   ├── Auth/
│   ├── Tenancy/
│   └── Support/
└── Unit/                 # Unit tests
    ├── Actions/
    ├── Services/
    └── Repositories/
```

---

## 📚 Documentation

- **[Architectural Digest](docs/ARCHITECTURAL_DIGEST.md)** - System architecture overview
- **[Coding Guidelines](CODING_GUIDELINES.md)** - Development standards and patterns
- **[Package Decoupling](docs/architecture/PACKAGE-DECOUPLING-STRATEGY.md)** - Service abstraction guide
- **[Phase 8 Complete](PHASE_8_COMPLETE.md)** - Package transformation summary
- **[Authentication Guide](docs/SANCTUM_AUTHENTICATION.md)** - API authentication setup

### API Documentation

API documentation is automatically generated and available at:
- Swagger UI: `/api/documentation`
- OpenAPI Spec: `/api/documentation.json`

---

## 🔧 Development

### Code Style

```bash
# Fix code style (Laravel Pint)
./vendor/bin/pint

# Check only (CI)
./vendor/bin/pint --test
```

### Available Scripts

```bash
# Root monorepo scripts
composer lint              # Format all code
composer test              # Run all tests

# App-specific scripts
composer lint:app          # Format app code
composer test:app          # Run app tests
```

### Pre-Commit Checklist

Before committing:

- [ ] Run `./vendor/bin/pint` to fix code style
- [ ] Run `./vendor/bin/pest` to verify tests pass
- [ ] All methods have return type declarations
- [ ] All public/protected methods have PHPDoc blocks
- [ ] Using repository pattern (no direct Model access in services)
- [ ] Authentication and authorization checks in place
- [ ] Complete validation rules for all fillable fields

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 🙏 Acknowledgments

- **Laravel** - The PHP framework for web artisans
- **Spatie** - Excellent Laravel packages (Permission, Activitylog, etc.)
- **Lorisleiva Laravel Actions** - Action pattern implementation
- **Community** - All contributors and users

---

## 📞 Support

- **Documentation:** [docs/](docs/)
- **Issues:** [GitHub Issues](https://github.com/azaharizaman/nexus-erp/issues)
- **Email:** azahari@nexusenvision.com

---

## 🗺️ Roadmap

### ✅ Completed (Phase 0-8)
- Multi-tenancy with team-scoped permissions
- Authentication (Sanctum) and Authorization (RBAC)
- Audit logging
- Settings management
- Serial numbering
- Unit of measure management
- **Package transformation (Phase 8)**

### 🚧 In Progress
- Testing and verification
- Performance optimization

### 📋 Planned
- Sales module (customers, orders, pricing)
- Purchasing module (vendors, POs, receipts)
- Accounting module (GL, AP/AR, reporting)
- Advanced inventory features (lot tracking, expiry)
- Reporting and analytics
- API versioning enhancements

---

**Status:** Ready for testing and deployment  
**Version:** 2.0.0 (Package release)  
**Last Updated:** November 13, 2025
