# Nexus ERP - Atomic Enterprise Laravel ERP System

![Status: Active Development](https://img.shields.io/badge/status-Active%20Development-green)
![PHP](https://img.shields.io/badge/PHP-8.3+-blue)
![Laravel](https://img.shields.io/badge/Laravel-12+-red)
![Architecture](https://img.shields.io/badge/architecture-Atomic%20Packages-orange)
![License](https://img.shields.io/badge/license-MIT-green)

**Enterprise-grade, headless ERP system** built on atomic package architecture with Laravel 12+ and PHP 8.3+. A modern, modular alternative to SAP, Oracle, and Microsoft Dynamics, designed for maximum atomicity, testability, and composability.

---

## 🎯 Overview

Nexus ERP is a **100% headless, API-first ERP system** built on the **Maximum Atomicity** principle. Each business domain lives in its own atomic package, ensuring unparalleled modularity, testability, and reusability.

### ✨ **What Makes Nexus ERP Different**

✅ **Atomic Package Architecture:** Each domain (Inventory, Accounting, HR) is a completely independent Laravel package  
✅ **Independent Testability:** Every package can be tested in isolation without the ERP context  
✅ **Contract-Driven Design:** Zero coupling between packages, all communication via contracts and events  
✅ **Headless by Design:** Pure API and CLI interfaces, no frontend dependencies  
✅ **AI-Ready:** Designed for consumption by AI agents and automation systems  
✅ **Orchestration Layer:** Smart coordination of atomic packages via Laravel Actions  
✅ **Multi-Tenant Native:** Complete tenant isolation at the architecture level

### �️ **Architectural Philosophy**

> **"Maximum Atomicity"** - All business logic that governs a single, independent domain MUST reside in its own package. The Nexus ERP Core is responsible ONLY for Orchestration, Configuration, and API Presentation.

---

## 📦 **Current Atomic Packages**

| Package | Status | Description | Independent Testing |
|---------|---------|-------------|-------------------|
| **nexus-tenancy** | ✅ Complete | Multi-tenant isolation and context management | ✅ Fully testable |
| **nexus-sequencing** | ✅ Phase 2.3 | Advanced serial numbering with pattern templates | ✅ Fully testable |
| **nexus-settings** | ✅ Complete | Hierarchical settings management (global/tenant/user) | ✅ Fully testable |
| **nexus-backoffice** | ✅ Complete | Organizational structure (departments, teams, hierarchy) | ✅ Fully testable |
| **nexus-audit-log** | ✅ Complete | Comprehensive activity tracking and compliance | ✅ Fully testable |
| **nexus-uom** | ✅ Complete | Unit of measure conversions and compatibility | ✅ Fully testable |
| **nexus-workflow** | 🚧 Phase 2 | Business process automation and approval workflows | ✅ Fully testable |
| **nexus-inventory** | 📋 Planned | Stock management, warehouses, movements | 🔄 In Design |
| **nexus-accounting** | 📋 Planned | General ledger, AP/AR, financial reporting | 🔄 In Design |

---

## 🚀 **Edward CLI Demo - Terminal ERP Interface**

Experience the power of headless ERP through **Edward**, our pure terminal interface that pays homage to classic JD Edwards ERP systems:

```bash
cd apps/edward
php artisan edward:menu
```

### **Edward Features:**
- 🎯 **Pure Terminal Interface** - No web, no API, just CLI
- 🎮 **Interactive Menus** - Elegant terminal UX with Laravel Prompts  
- 🔗 **Package Integration** - Demonstrates atomic package consumption
- 📊 **Live Demos** - Real-time sequencing patterns, inventory operations
- 🧪 **Testing Ground** - Perfect for validating package independence

---

## 🏗️ **Repository Structure**

```
nexus-erp/
├── src/                          # Nexus ERP Core (Orchestration Layer)
│   ├── Actions/                  # Cross-package business operations
│   ├── Console/Commands/         # Global CLI commands  
│   ├── Http/                     # API endpoints and middleware
│   ├── Models/                   # Core models (User, etc.)
│   ├── Providers/                # Service providers and bindings
│   └── Support/                  # Contracts, traits, helpers
│
├── packages/                     # Atomic Packages (Zero Coupling)
│   ├── nexus-tenancy/           # Multi-tenancy (Nexus\Tenancy)
│   ├── nexus-sequencing/        # Serial numbering (Nexus\Sequencing) 
│   ├── nexus-settings/          # Settings management (Nexus\Settings)
│   ├── nexus-backoffice/        # Organization structure (Nexus\Backoffice)
│   ├── nexus-audit-log/         # Audit logging (Nexus\AuditLog)
│   ├── nexus-uom/               # Unit of measure (Nexus\Uom)
│   ├── nexus-workflow/          # Business workflows (Nexus\Workflow)
│   ├── nexus-inventory/         # Inventory operations (Nexus\Inventory)
│   └── nexus-accounting/        # Financial operations (Nexus\Accounting)
│
├── apps/edward/                 # Edward CLI Demo Application  
│   ├── app/Console/Commands/    # Terminal interface commands
│   ├── app/Models/              # Demo models extending atomic packages
│   └── composer.json            # Demo app dependencies
│
├── docs/                        # Comprehensive Documentation
│   ├── SYSTEM ARCHITECHTURAL DOCUMENT.md  # Architecture blueprint
│   └── plan/                    # Phase plans and completion reports
│
├── tests/                       # Integration test suite
├── composer.json                # Main package definition  
└── README.md                    # This file
```

---

## ✨ **Key Features & Capabilities**

### 🏛️ **Architectural Features**
- ✅ **Maximum Atomicity:** Each business domain is completely independent
- ✅ **Independent Testability:** Every package testable in isolation without ERP context
- ✅ **Contract-Driven Communication:** Zero direct coupling between atomic packages
- ✅ **Event-Driven Architecture:** Cross-package communication via Laravel events
- ✅ **Orchestration Layer:** Smart coordination via Laravel Actions pattern
- ✅ **Monorepo Structure:** Unified development with package independence

### 🔐 **Core System Features**  
- ✅ **Multi-Tenancy:** Complete tenant isolation with team-scoped permissions
- ✅ **Authentication:** API token authentication with Laravel Sanctum
- ✅ **Authorization:** Role-based access control (RBAC) with fine-grained permissions
- ✅ **Audit Logging:** Comprehensive activity tracking for compliance
- ✅ **Settings Management:** Hierarchical configuration (global → tenant → user)

### 📋 **Business Domain Features**
- ✅ **Advanced Serial Numbering:** Pattern-based sequences with custom variables, conditional logic, and business templates
- ✅ **Organizational Structure:** Departments, teams, hierarchy management with traits
- ✅ **Unit of Measure:** Conversion systems with compatibility validation
- ✅ **Workflow Engine:** Business process automation with approval chains
- 🔄 **Inventory Management:** (In development) Stock tracking, warehouses, movements
- 📋 **Financial Management:** (Planned) GL, AP/AR, reporting

### 🤖 **Developer Experience**
- ✅ **CLI-First Design:** Complete ERP operations via terminal (Edward demo)
- ✅ **API-First Architecture:** RESTful endpoints for all operations  
- ✅ **Laravel Actions Integration:** Unified invocation (CLI/API/Job/Event)
- ✅ **Contract Abstractions:** All dependencies behind interfaces
- ✅ **Comprehensive Testing:** Pest-based test suites with coverage reporting

---

## 🚀 **Quick Start Guide**

### **Method 1: Package Installation (Recommended)**

```bash
# Create new Laravel project
composer create-project laravel/laravel my-erp-app
cd my-erp-app

# Require Nexus ERP and atomic packages
composer require nexus/erp
composer require nexus/tenancy nexus/sequencing nexus/settings
composer require nexus/backoffice nexus/audit-log nexus/uom

# Publish and run migrations
php artisan vendor:publish --tag=nexus-erp-config
php artisan vendor:publish --tag=nexus-erp-migrations  
php artisan migrate

# Start developing!
```

### **Method 2: Development Environment Setup**

```bash
# Clone the repository
git clone https://github.com/azaharizaman/nexus-erp.git
cd nexus-erp

# Try the Edward CLI demo
cd apps/edward
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Launch interactive ERP terminal
php artisan edward:menu
```

### **Method 3: Integration into Existing Laravel App**

```php
// In your existing Laravel application
composer require nexus/erp

// Use atomic packages independently  
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;
use Nexus\Settings\Actions\GetSettingAction;
use Nexus\Backoffice\Repositories\DepartmentRepository;

$serialNumber = GenerateSerialNumberAction::run('INV-{YYYY}-{0000}');
$setting = GetSettingAction::run('app.currency', $tenantId);
$departments = app(DepartmentRepository::class)->getHierarchy();
```

---

## 🧪 **Testing Philosophy**

### **Independent Testability Criterion**
Every atomic package MUST pass this test:

```bash
# Each package should be testable in isolation
cd packages/nexus-sequencing
composer install  # Only package dependencies
composer test     # Should pass without Nexus ERP
```

### **Running Tests**

```bash
# Test entire monorepo
composer test

# Test specific package
cd packages/nexus-sequencing && composer test

# Test with coverage
composer test:coverage

# Test Edward demo
cd apps/edward && composer test
```

### **Test Structure Philosophy**
- **Unit Tests:** Pure domain logic, no Laravel context
- **Feature Tests:** Package integration within Laravel
- **Integration Tests:** Cross-package orchestration via Edward demo

---

## 🛠️ **Technology Stack**

| Component | Version | Purpose | Usage in Nexus ERP |
|-----------|---------|---------|-------------------|
| **PHP** | ≥ 8.3 | Latest language features | Type declarations, enums, readonly properties |
| **Laravel** | ≥ 12.x | Framework foundation | Core framework, package development |
| **Database** | Agnostic | Data persistence | MySQL, PostgreSQL, SQLite, SQL Server |
| **Laravel Sanctum** | ^4.2 | API authentication | Token-based authentication |
| **Spatie Permission** | ^6.23 | RBAC authorization | Role and permission management |
| **Laravel Actions** | ^2.0 | Action pattern | Unified command/job/API/event invocation |
| **Pest** | ^4.0 | Testing framework | BDD-style testing suite |
| **Laravel Prompts** | Latest | CLI interface | Edward demo interactive menus |

### **Package Dependencies**

Each atomic package declares only necessary dependencies:

```json
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0"
  }
}
```

**No cross-package dependencies allowed** - communication via contracts only.

---

## 🏗️ **Architecture Deep Dive**

### **The Atomic Package Pattern**

```php
// ❌ VIOLATION: Direct package coupling
use Nexus\Inventory\Models\Item;
use Nexus\Accounting\Services\CostingService;

class PurchaseOrderService 
{
    public function calculateTotal(Item $item) {
        return app(CostingService::class)->calculate($item);
    }
}

// ✅ CORRECT: Contract-based communication
use Nexus\Contracts\CostingServiceContract;
use Nexus\Contracts\ItemRepositoryContract;

class PurchaseOrderService
{
    public function __construct(
        private readonly CostingServiceContract $costingService,
        private readonly ItemRepositoryContract $itemRepository
    ) {}
    
    public function calculateTotal(string $itemId) {
        $item = $this->itemRepository->find($itemId);
        return $this->costingService->calculate($item);
    }
}
```

### **Orchestration Layer Pattern**

```php
// Nexus\Erp\Actions\Purchasing\CreatePurchaseOrderAction
class CreatePurchaseOrderAction extends Action
{
    public function handle(array $data): PurchaseOrder
    {
        // Validation
        $validated = $this->validate($data);
        
        // Orchestrate multiple atomic packages
        $serialNumber = app(SequencingContract::class)
            ->generate('PO-{YYYY}-{0000}');
            
        $supplier = app(BackofficeContract::class)
            ->findSupplier($validated['supplier_id']);
            
        // Business logic coordination
        $po = PurchaseOrder::create([
            'number' => $serialNumber,
            'supplier_id' => $supplier->id,
            'tenant_id' => tenant()->id,
        ]);
        
        // Cross-package events
        event(new PurchaseOrderCreated($po));
        
        return $po;
    }
}
```

### **Independent Testability Example**

```php
// packages/nexus-sequencing/tests/Feature/GenerateSequenceTest.php
class GenerateSequenceTest extends TestCase
{
    /** @test */
    public function it_generates_sequence_with_pattern()
    {
        // No Nexus ERP context needed!
        $sequence = Sequence::factory()->create([
            'pattern' => 'INV-{YYYY}-{0000}',
        ]);
        
        $result = GenerateSerialNumberAction::run($sequence->pattern);
        
        expect($result)->toMatch('/^INV-\d{4}-\d{4}$/');
    }
}
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

## 📖 **Usage Examples**

### **Using Atomic Packages Independently**

```php
// Each package can be used independently in any Laravel app
use Nexus\Sequencing\Actions\GenerateSerialNumberAction;
use Nexus\Settings\Actions\GetSettingAction;
use Nexus\Backoffice\Repositories\DepartmentRepository;

// Generate invoice numbers
$invoiceNumber = GenerateSerialNumberAction::run('INV-{YYYY}-{0000}');
// Result: "INV-2025-0001"

// Get hierarchical settings
$currency = GetSettingAction::run('app.currency', $tenantId);
$timezone = GetSettingAction::run('app.timezone', $tenantId, $userId);

// Organization hierarchy
$deptRepo = app(DepartmentRepository::class);
$hierarchy = $deptRepo->getHierarchy();
$rootDepts = $deptRepo->getRoots();
```

### **Edward CLI Demo Usage**

```bash
# Launch interactive ERP terminal
cd apps/edward
php artisan edward:menu

# Direct sequencing demo
php artisan edward:sequencing-demo

# Direct tenant management
php artisan edward:tenant-demo

# Show all available demos
php artisan list edward
```

### **API Integration**

```bash
# Multi-tenant authentication
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@company.com",
    "password": "password",
    "device_name": "api-client",
    "tenant_id": "uuid-here"
  }'

# Generate serial numbers via API
curl -X POST http://localhost:8000/api/v1/sequences/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "pattern": "PO-{YYYY}-{000000}",
    "context": {"department": "purchasing"}
  }'
```

### **Orchestration Layer Usage**

```php
// Cross-package orchestration via Actions
use Nexus\Erp\Actions\Purchasing\CreatePurchaseOrderAction;
use Nexus\Erp\Actions\Inventory\AdjustStockAction;
use Nexus\Erp\Actions\Audit\LogActivityAction;

// Create purchase order (orchestrates multiple packages)
$purchaseOrder = CreatePurchaseOrderAction::run([
    'supplier_id' => $supplierId,
    'items' => $items,
    'delivery_date' => $deliveryDate,
]);

// Available as CLI command automatically
php artisan purchase-order:create --supplier=123 --items="item1,item2"

// Available as queued job automatically
CreatePurchaseOrderAction::dispatch($data)->onQueue('default');

// Available in event listeners automatically
Event::listen(SupplierCreated::class, function ($event) {
    CreatePurchaseOrderAction::run($event->data);
});
```

---

## 🧪 **Testing & Quality Assurance**

### **Test Execution**

```bash
# Run all tests (monorepo)
composer test

# Test specific atomic package in isolation  
cd packages/nexus-sequencing
composer test  # Should work without any ERP dependencies!

# Integration testing via Edward
cd apps/edward
composer test

# Coverage reporting
composer test:coverage
vendor/bin/pest --coverage --min=80
```

### **Quality Metrics**

| Package | Test Coverage | Independence Score | Complexity Score |
|---------|---------------|-------------------|------------------|
| nexus-tenancy | >95% | ✅ Fully Independent | Low |
| nexus-sequencing | >90% | ✅ Fully Independent | Medium |
| nexus-settings | >95% | ✅ Fully Independent | Low |
| nexus-backoffice | >85% | ✅ Fully Independent | Medium |
| nexus-audit-log | >90% | ✅ Fully Independent | Low |
| nexus-uom | >95% | ✅ Fully Independent | Low |
| nexus-workflow | >80% | ✅ Fully Independent | High |

### **Architectural Validation**

```bash
# Validate atomic package independence
./scripts/validate-atomicity.sh

# Check for cross-package coupling violations  
./scripts/check-coupling.sh

# Test contract compliance
./scripts/test-contracts.sh
```

---

## 📚 **Documentation & Resources**

### **Core Documentation**
- **[SYSTEM ARCHITECTURAL DOCUMENT.md](docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)** - Complete architecture blueprint with atomicity principles
- **[CODING_GUIDELINES.md](CODING_GUIDELINES.md)** - Development standards and coding patterns
- **[RDP_SUMMARY.md](RDP_SUMMARY.md)** - Analysis of architectural terminology and design decisions

### **Package-Specific Documentation**
- **[nexus-sequencing README](packages/nexus-sequencing/README.md)** - Advanced pattern system and serial number generation
- **[nexus-tenancy README](packages/nexus-tenancy/README.md)** - Multi-tenant architecture and context management
- **[nexus-workflow README](packages/nexus-workflow/README.md)** - Business process automation and approval workflows

### **Implementation Plans**
- **[docs/plan/](docs/plan/)** - Detailed phase plans for each atomic package
- **[Completion Reports](packages/*/PHASE_*_COMPLETION_REPORT.md)** - Phase completion summaries and achievements

### **Edward CLI Demo Documentation**
- **[Edward README](apps/edward/README.md)** - Terminal interface usage and capabilities
- **[Implementation Summary](apps/edward/IMPLEMENTATION_SUMMARY_CONVERSION_ENGINE.md)** - Technical implementation details

---

## 🔧 **Development Workflow**

### **Code Quality Standards**

```bash
# Format code (Laravel Pint)
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Run mutation testing (Infection)
./vendor/bin/infection

# Static analysis
./vendor/bin/phpstan analyse
```

### **Available Scripts**

```json
{
  "scripts": {
    "test": "pest",                    // Run all tests
    "test:app": "cd apps/edward && composer test",  // Test Edward demo
    "lint": "pint",                   // Format code
    "lint:check": "pint --test"       // Check formatting
  }
}
```

### **Development Checklist**

Before committing code:

- [ ] ✅ **Independent Testability:** Package tests pass in isolation
- [ ] ✅ **Code Formatting:** `./vendor/bin/pint` applied
- [ ] ✅ **Test Coverage:** All new code covered by tests
- [ ] ✅ **Contract Compliance:** No direct cross-package dependencies
- [ ] ✅ **Documentation:** PHPDoc blocks for all public methods
- [ ] ✅ **Type Declarations:** Strict typing for all parameters and returns
- [ ] ✅ **Atomicity Validation:** Code belongs in correct layer (atomic vs orchestration)

---

## 🗺️ **Roadmap & Status**

### ✅ **Completed Phases**

| Phase | Package | Features | Status |
|-------|---------|----------|---------|
| **1.0** | nexus-tenancy | Multi-tenant isolation, context management | ✅ Complete |
| **1.1** | nexus-settings | Hierarchical configuration management | ✅ Complete |
| **1.2** | nexus-audit-log | Activity tracking and compliance logging | ✅ Complete |
| **2.0** | nexus-backoffice | Organizational structure and hierarchy | ✅ Complete |
| **2.1** | nexus-sequencing | Basic serial number generation | ✅ Complete |
| **2.2** | nexus-sequencing | Advanced pattern variables and date functions | ✅ Complete |
| **2.3** | nexus-sequencing | Conditional logic, business templates, Core/Adapter pattern | ✅ Complete |
| **2.0** | nexus-workflow | Business process automation framework | 🚧 Phase 2 |

### 🚧 **In Progress**

- **nexus-workflow Phase 2:** Advanced workflow features and integration
- **Performance Optimization:** Caching, database query optimization
- **Documentation Enhancement:** API documentation, usage examples

### 📋 **Planned Phases**

| Phase | Package | Features | Timeline |
|-------|---------|----------|----------|
| **3.0** | nexus-inventory | Stock management, warehouse operations | Q1 2026 |
| **3.1** | nexus-inventory | Lot tracking, expiry management | Q1 2026 |
| **4.0** | nexus-accounting | Chart of accounts, general ledger | Q2 2026 |
| **4.1** | nexus-accounting | AP/AR, financial reporting | Q2 2026 |
| **5.0** | nexus-sales | Customer management, order processing | Q3 2026 |
| **5.1** | nexus-purchasing | Vendor management, procurement | Q3 2026 |

### 🎯 **Strategic Goals**

- **Q4 2025:** Complete workflow engine, performance optimization
- **Q1 2026:** Inventory management atomic package
- **Q2 2026:** Financial management system
- **Q3 2026:** Complete sales and purchasing modules
- **Q4 2026:** Advanced reporting, analytics, and AI integration

---

## 📝 **License & Legal**

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for complete details.

### **Copyright Notice**
```
Copyright (c) 2025 Azahari Zaman and the Nexus ERP Contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
```

---

## 🤝 **Contributing**

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

### **Contribution Areas**
- 🏗️ **Architecture:** Help refine atomic package patterns
- 🧪 **Testing:** Improve test coverage and quality
- 📖 **Documentation:** Enhance guides and examples  
- 🔌 **Packages:** Develop new atomic packages
- 🛠️ **Edward CLI:** Expand demo capabilities
- 🐛 **Bug Fixes:** Identify and resolve issues

### **Development Philosophy**
All contributions must adhere to the **Maximum Atomicity** principle and pass the **Independent Testability** criterion.

---

## 🙏 **Acknowledgments**

- **Laravel Team** - For the exceptional PHP framework that powers Nexus ERP
- **Spatie** - For outstanding Laravel packages that enhance functionality  
- **Laravel Actions (Lorisleiva)** - For elegant action pattern implementation
- **Pest PHP** - For the delightful testing experience
- **Classic ERP Systems** - SAP, JD Edwards, Oracle for inspiration and patterns
- **Open Source Community** - For continuous innovation and collaboration

---

## 📞 **Support & Community**

### **Getting Help**
- 📖 **Documentation:** Start with [SYSTEM ARCHITECTURAL DOCUMENT.md](docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md)
- 🐛 **Issues:** [GitHub Issues](https://github.com/azaharizaman/nexus-erp/issues)
- 💬 **Discussions:** [GitHub Discussions](https://github.com/azaharizaman/nexus-erp/discussions)
- 📧 **Email:** azahari@nexusenvision.com

### **Project Status**
- **Current Version:** 2.3.0 (Phase 2.3 Complete)
- **Development Status:** Active Development
- **Next Milestone:** nexus-workflow Phase 2 completion
- **Last Updated:** November 14, 2025

---

> **"Maximum Atomicity"** - Building the future of Enterprise Software, one atomic package at a time.

**Nexus ERP** - Where traditional ERP meets modern architecture. 🚀
