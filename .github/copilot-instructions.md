# Laravel ERP System - GitHub Copilot Instructions

**Version:** 1.0.0  
**Last Updated:** November 8, 2025  
**Project:** Laravel Headless ERP Backend System

---

## Project Overview

This is an enterprise-grade, headless ERP backend system built with Laravel 12+ and PHP 8.2+. The system is designed to rival SAP, Odoo, and Microsoft Dynamics while maintaining superior modularity, extensibility, and agentic capabilities.

### Key Characteristics

- **Architecture:** Headless backend-only system (no UI components)
- **Integration:** RESTful APIs and CLI commands only
- **Design Philosophy:** Contract-driven, domain-driven, event-driven
- **Target:** AI agents, custom frontends, and automated systems
- **Modularity:** Enable/disable modules without system-wide impact
- **Security:** Zero-trust model with blockchain verification

---

## Technology Stack

### Core Requirements

- **PHP:** ≥ 8.2 (use latest PHP 8.2+ features)
- **Laravel:** ≥ 12.x (latest stable version)
- **Database:** Agnostic design (MySQL, PostgreSQL, SQLite, SQL Server)
- **Composer Stability:** `dev` for internal packages

### Required Packages

```json
{
  "azaharizaman/laravel-uom-management": "dev-main",
  "azaharizaman/laravel-inventory-management": "dev-main",
  "azaharizaman/laravel-backoffice": "dev-main",
  "azaharizaman/laravel-serial-numbering": "dev-main",
  "azaharizaman/php-blockchain": "dev-main",
  "laravel/scout": "^10.0",
  "lorisleiva/laravel-actions": "^2.0",
  "spatie/laravel-permission": "^6.0",
  "spatie/laravel-model-status": "^2.0",
  "spatie/laravel-activitylog": "^4.0",
  "brick/math": "^0.12"
}
```

### Architecture Patterns

- **Contract-Driven Development:** All functionality defined by interfaces
- **Domain-Driven Design:** Business logic organized by domain boundaries
- **Event-Driven Architecture:** Module communication via Laravel events
- **Repository Pattern:** Data access abstraction layer
- **Service Layer Pattern:** Business logic encapsulation
- **Action Pattern:** Discrete business operations using `lorisleiva/laravel-actions`
- **Search Pattern:** All Eloquent models MUST use Laravel Scout for search functionality
- **SOLID Principles:** Single responsibility, dependency injection throughout

---

## Laravel Scout Integration

### Search Functionality Requirements

**MANDATORY:** All Eloquent models in the Laravel ERP system MUST implement Laravel Scout for search functionality. This ensures consistent, performant search capabilities across all domains.

#### Model Implementation
```php
namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class InventoryItem extends Model
{
    use Searchable;
    
    // Scout configuration
    public function searchableAs(): string
    {
        return 'inventory_items';
    }
    
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category?->name,
            'tenant_id' => $this->tenant_id,
        ];
    }
    
    // Scout automatically indexes on create/update/delete
}
```

#### Search Usage
```php
// Basic search
$results = InventoryItem::search('laptop')->get();

// Advanced search with filters
$results = InventoryItem::search('laptop')
    ->where('tenant_id', $tenantId)
    ->take(20)
    ->get();

// Search with pagination
$results = InventoryItem::search('laptop')->paginate(15);
```

#### Configuration
- **Driver:** Use `collection` for development, configure production driver (Algolia, MeiliSearch, etc.) via `SCOUT_DRIVER` env variable
- **Queue:** Enable queued indexing via `SCOUT_QUEUE=true` for production performance
- **Tenant Isolation:** Include `tenant_id` in searchable array for proper multi-tenant search

#### Requirements
- ✅ All models MUST use `Laravel\Scout\Searchable` trait
- ✅ Implement `searchableAs()` method for index naming
- ✅ Implement `toSearchableArray()` method for search data
- ✅ Include tenant_id for multi-tenant isolation
- ✅ Test search functionality in feature tests
- ✅ Configure Scout driver in production environment

---

## Code Standards

### PHP Standards

#### Version Features
- Use PHP 8.2+ features: typed properties, constructor property promotion, enums, readonly properties
- Use strict types: `declare(strict_types=1);` in all PHP files
- Use null coalescing operator `??` and null safe operator `?->`
- Use match expressions instead of switch statements where appropriate
- Use array spread operator `...` for array merging
- Use named arguments for better readability in complex function calls

#### Type Declarations
```php
// ALWAYS use strict type declarations
declare(strict_types=1);

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

// Use typed properties
class AdjustStockAction
{
    public function __construct(
        private readonly InventoryItemInterface $inventoryRepository,
        private readonly AuditLogService $auditLog
    ) {}
    
    // Always specify return types
    public function execute(InventoryItem $item, float $quantity, string $reason): bool
    {
        // Implementation
    }
}
```

#### Naming Conventions
- **Classes:** PascalCase - `InventoryItemController`, `PurchaseOrderService`
- **Methods:** camelCase - `createPurchaseOrder()`, `getActiveItems()`
- **Variables:** camelCase - `$purchaseOrder`, `$itemQuantity`
- **Constants:** UPPER_SNAKE_CASE - `MAX_QUANTITY`, `DEFAULT_CURRENCY`
- **Database Tables:** snake_case, plural - `inventory_items`, `purchase_orders`
- **Database Columns:** snake_case - `created_at`, `unit_price`
- **Contracts/Interfaces:** Suffix with `Interface` - `InventoryItemInterface`
- **Actions:** Suffix with `Action` - `CreatePurchaseOrderAction`
- **Events:** Suffix with `Event` - `StockAdjustedEvent`
- **Listeners:** Suffix with `Listener` - `SendLowStockNotificationListener`
- **Policies:** Suffix with `Policy` - `InventoryItemPolicy`
- **Repositories:** Suffix with `Repository` - `InventoryItemRepository`
- **Services:** Suffix with `Service` - `SerialNumberService`

#### Code Style
- Follow PSR-12 coding standards
- Use Laravel best practices and conventions
- Keep methods focused and small (≤ 50 lines)
- Maximum cyclomatic complexity: 10
- Use early returns and guard clauses
- Avoid nested conditionals (max depth: 3)

### Documentation Standards

#### PHPDoc Blocks
```php
/**
 * Adjust inventory stock level with audit trail
 *
 * @param InventoryItem $item The inventory item to adjust
 * @param float $quantity The adjustment quantity (positive or negative)
 * @param string $reason The reason for adjustment
 * @return bool True if adjustment successful
 * @throws InsufficientStockException If adjustment would result in negative stock
 * @throws InvalidQuantityException If quantity is zero or invalid
 */
public function execute(InventoryItem $item, float $quantity, string $reason): bool
```

#### Comments
- Write self-documenting code (clear naming reduces comment needs)
- Comment "why" not "what"
- Use TODO, FIXME, NOTE markers with issue numbers
- Document complex business logic and calculations
- Explain non-obvious design decisions

---

## Project Structure

### Domain Organization

```
app/
├── Domains/                         # Business domains (DDD)
│   ├── Core/                        # Foundation (tenancy, auth, audit)
│   │   ├── Actions/
│   │   ├── Contracts/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   ├── Models/
│   │   ├── Observers/
│   │   ├── Policies/
│   │   ├── Repositories/
│   │   └── Services/
│   ├── Backoffice/                  # Organization structure
│   ├── Inventory/                   # Inventory management
│   ├── Sales/                       # Sales operations
│   ├── Purchasing/                  # Procurement
│   ├── Manufacturing/               # Production
│   ├── HumanResources/              # HR operations
│   ├── Accounting/                  # Financial accounting
│   ├── SupplyChain/                 # Supply chain
│   ├── Quality/                     # Quality management
│   ├── Maintenance/                 # CMMS (optional)
│   └── Analytics/                   # Business intelligence
│
├── Http/
│   ├── Controllers/                 # API controllers only
│   │   └── Api/
│   │       └── V1/
│   │           ├── CoreController.php
│   │           ├── InventoryController.php
│   │           └── ...
│   ├── Middleware/
│   ├── Requests/                    # Form request validation
│   └── Resources/                   # API resources (transformers)
│
├── Console/
│   └── Commands/                    # CLI commands for each domain
│
└── Support/                         # Shared utilities
    ├── Enums/
    ├── Helpers/
    └── Traits/
```

### File Naming Patterns

- **Models:** Singular noun - `InventoryItem.php`, `PurchaseOrder.php`
- **Controllers:** Resource name + `Controller.php` - `InventoryItemController.php`
- **Actions:** Verb + noun + `Action.php` - `CreatePurchaseOrderAction.php`
- **Services:** Purpose + `Service.php` - `SerialNumberService.php`
- **Repositories:** Model + `Repository.php` - `InventoryItemRepository.php`
- **Contracts:** Model + `Interface.php` - `InventoryItemInterface.php`
- **Events:** Past tense + `Event.php` - `StockAdjustedEvent.php`
- **Listeners:** Action description + `Listener.php` - `UpdateInventoryBalanceListener.php`
- **Policies:** Model + `Policy.php` - `InventoryItemPolicy.php`
- **Requests:** Action + Model + `Request.php` - `StoreInventoryItemRequest.php`
- **Resources:** Model + `Resource.php` - `InventoryItemResource.php`
- **Migrations:** `yyyy_mm_dd_hhmmss_action_table_name.php`

---

## Development Guidelines

### Contract-Driven Development

Always define contracts (interfaces) before implementation:

```php
namespace App\Domains\Inventory\Contracts;

use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

interface InventoryItemInterface
{
    public function findById(int $id): ?InventoryItem;
    
    public function findByCode(string $code): ?InventoryItem;
    
    public function getActiveItems(): Collection;
    
    public function create(array $data): InventoryItem;
    
    public function update(InventoryItem $item, array $data): bool;
    
    public function delete(InventoryItem $item): bool;
}
```

### Repository Pattern

Implement repositories for all data access:

```php
namespace App\Domains\Inventory\Repositories;

use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

class InventoryItemRepository implements InventoryItemInterface
{
    public function findById(int $id): ?InventoryItem
    {
        return InventoryItem::find($id);
    }
    
    public function getActiveItems(): Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }
    
    // Implement all interface methods
}
```

### Action Pattern

Use Actions for business operations:

```php
namespace App\Domains\Inventory\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Domains\Inventory\Models\InventoryItem;
use App\Domains\Inventory\Events\StockAdjustedEvent;

class AdjustStockAction
{
    use AsAction;
    
    public function __construct(
        private readonly AuditLogService $auditLog
    ) {}
    
    public function handle(InventoryItem $item, float $quantity, string $reason): bool
    {
        // Validate
        if ($quantity === 0.0) {
            throw new InvalidQuantityException('Quantity cannot be zero');
        }
        
        $newQuantity = $item->quantity + $quantity;
        
        if ($newQuantity < 0) {
            throw new InsufficientStockException('Insufficient stock for adjustment');
        }
        
        // Update
        $item->quantity = $newQuantity;
        $item->save();
        
        // Audit
        $this->auditLog->log('stock_adjusted', $item, [
            'old_quantity' => $item->quantity - $quantity,
            'new_quantity' => $newQuantity,
            'adjustment' => $quantity,
            'reason' => $reason,
        ]);
        
        // Event
        event(new StockAdjustedEvent($item, $quantity, $reason));
        
        return true;
    }
    
    // Make action available as job
    public function asJob(InventoryItem $item, float $quantity, string $reason): void
    {
        $this->handle($item, $quantity, $reason);
    }
    
    // Make action available via CLI
    public function asCommand(Command $command): void
    {
        $itemId = $command->argument('item_id');
        $quantity = (float) $command->argument('quantity');
        $reason = $command->argument('reason');
        
        $item = InventoryItem::findOrFail($itemId);
        
        $this->handle($item, $quantity, $reason);
        
        $command->info("Stock adjusted successfully for {$item->code}");
    }
}
```

### Event-Driven Communication

Use events for module communication:

```php
namespace App\Domains\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\Inventory\Models\InventoryItem;

class StockAdjustedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly InventoryItem $item,
        public readonly float $adjustment,
        public readonly string $reason
    ) {}
}
```

### Service Layer

Encapsulate complex business logic in services:

```php
namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Support\Collection;

class InventoryValuationService
{
    public function __construct(
        private readonly InventoryItemInterface $repository
    ) {}
    
    public function calculateTotalValue(): float
    {
        return $this->repository
            ->getActiveItems()
            ->sum(fn (InventoryItem $item) => $item->quantity * $item->unit_cost);
    }
    
    public function getItemsByValuation(float $minValue): Collection
    {
        return $this->repository
            ->getActiveItems()
            ->filter(fn (InventoryItem $item) => 
                ($item->quantity * $item->unit_cost) >= $minValue
            );
    }
}
```

---

## API Development

### API Standards

- **Version:** Always version APIs (`/api/v1/`)
- **Format:** JSON:API specification compliance
- **Authentication:** Laravel Sanctum (stateless tokens)
- **Rate Limiting:** Apply per endpoint
- **Status Codes:** Use proper HTTP status codes
- **Pagination:** Always paginate list endpoints
- **Filtering:** Support query parameter filtering
- **Sorting:** Support `sort` parameter
- **Field Selection:** Support `fields` parameter (sparse fieldsets)

### Controller Structure

```php
namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Actions\CreateInventoryItemAction;
use App\Domains\Inventory\Actions\UpdateInventoryItemAction;
use App\Domains\Inventory\Contracts\InventoryItemInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryItemController extends Controller
{
    public function __construct(
        private readonly InventoryItemInterface $repository
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(InventoryItem::class, 'item');
    }
    
    public function index(): AnonymousResourceCollection
    {
        $items = $this->repository
            ->query()
            ->filter(request()->only(['search', 'category', 'is_active']))
            ->sort(request('sort', 'code'))
            ->paginate(request('per_page', 15));
        
        return InventoryItemResource::collection($items);
    }
    
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = CreateInventoryItemAction::run($request->validated());
        
        return InventoryItemResource::make($item)
            ->response()
            ->setStatusCode(201);
    }
    
    public function show(int $id): InventoryItemResource
    {
        $item = $this->repository->findById($id);
        
        abort_if(!$item, 404, 'Inventory item not found');
        
        return InventoryItemResource::make($item);
    }
    
    public function update(
        UpdateInventoryItemRequest $request,
        int $id
    ): InventoryItemResource {
        $item = $this->repository->findById($id);
        
        abort_if(!$item, 404, 'Inventory item not found');
        
        $updated = UpdateInventoryItemAction::run($item, $request->validated());
        
        return InventoryItemResource::make($updated);
    }
    
    public function destroy(int $id): JsonResponse
    {
        $item = $this->repository->findById($id);
        
        abort_if(!$item, 404, 'Inventory item not found');
        
        $this->repository->delete($item);
        
        return response()->json(null, 204);
    }
}
```

### API Resource Structure

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'uom' => $this->whenLoaded('uom', fn () => UomResource::make($this->uom)),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'reorder_level' => $this->reorder_level,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'links' => [
                'self' => route('api.v1.inventory-items.show', $this->id),
            ],
        ];
    }
}
```

---

## Database Guidelines

### Migration Standards

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign keys (with explicit naming)
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->onDelete('set null');
            $table->foreignId('uom_id')
                ->constrained('units_of_measure')
                ->onDelete('restrict');
            
            // Unique constraints
            $table->string('code')->unique();
            
            // Required fields
            $table->string('name');
            $table->text('description')->nullable();
            
            // Numeric fields with precision
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('reorder_level', 15, 4)->default(0);
            
            // Status/flags
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index('code');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
```

### Model Standards

```php
namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'tenant_id',
        'category_id',
        'uom_id',
        'code',
        'name',
        'description',
        'quantity',
        'unit_cost',
        'unit_price',
        'reorder_level',
        'is_active',
    ];
    
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'reorder_level' => 'decimal:4',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }
    
    // Activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'quantity', 'unit_cost', 'unit_price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

---

## Testing Standards

### Test Structure

```
tests/
├── Feature/                         # Integration tests
│   └── Api/
│       └── V1/
│           ├── InventoryItemTest.php
│           └── PurchaseOrderTest.php
├── Unit/                            # Unit tests
│   └── Domains/
│       └── Inventory/
│           ├── Actions/
│           │   └── AdjustStockActionTest.php
│           └── Services/
│               └── InventoryValuationServiceTest.php
└── TestCase.php
```

### Feature Test Example

```php
namespace Tests\Feature\Api\V1;

use App\Domains\Inventory\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }
    
    public function test_can_list_inventory_items(): void
    {
        InventoryItem::factory()->count(3)->create();
        
        $response = $this->getJson('/api/v1/inventory-items');
        
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'quantity'],
                ],
                'links',
                'meta',
            ]);
    }
    
    public function test_can_create_inventory_item(): void
    {
        $data = [
            'code' => 'ITEM-001',
            'name' => 'Test Item',
            'quantity' => 100,
            'unit_cost' => 10.50,
            'unit_price' => 15.00,
        ];
        
        $response = $this->postJson('/api/v1/inventory-items', $data);
        
        $response
            ->assertCreated()
            ->assertJsonFragment(['code' => 'ITEM-001']);
        
        $this->assertDatabaseHas('inventory_items', ['code' => 'ITEM-001']);
    }
    
    public function test_cannot_create_duplicate_item_code(): void
    {
        InventoryItem::factory()->create(['code' => 'ITEM-001']);
        
        $data = ['code' => 'ITEM-001', 'name' => 'Duplicate'];
        
        $response = $this->postJson('/api/v1/inventory-items', $data);
        
        $response->assertUnprocessable();
    }
}
```

### Unit Test Example

```php
namespace Tests\Unit\Domains\Inventory\Actions;

use App\Domains\Inventory\Actions\AdjustStockAction;
use App\Domains\Inventory\Exceptions\InsufficientStockException;
use App\Domains\Inventory\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustStockActionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_increase_stock(): void
    {
        $item = InventoryItem::factory()->create(['quantity' => 100]);
        
        $result = AdjustStockAction::run($item, 50, 'Purchase receipt');
        
        $this->assertTrue($result);
        $this->assertEquals(150, $item->fresh()->quantity);
    }
    
    public function test_can_decrease_stock(): void
    {
        $item = InventoryItem::factory()->create(['quantity' => 100]);
        
        $result = AdjustStockAction::run($item, -30, 'Sales order');
        
        $this->assertTrue($result);
        $this->assertEquals(70, $item->fresh()->quantity);
    }
    
    public function test_throws_exception_on_insufficient_stock(): void
    {
        $item = InventoryItem::factory()->create(['quantity' => 10]);
        
        $this->expectException(InsufficientStockException::class);
        
        AdjustStockAction::run($item, -50, 'Sales order');
    }
}
```

---

## CLI Commands

### Command Structure

```php
namespace App\Console\Commands;

use App\Domains\Inventory\Actions\GenerateStockReportAction;
use Illuminate\Console\Command;

class GenerateStockReportCommand extends Command
{
    protected $signature = 'inventory:stock-report
                            {--format=json : Output format (json|csv|pdf)}
                            {--email= : Email address to send report}
                            {--category= : Filter by category ID}';
    
    protected $description = 'Generate inventory stock report';
    
    public function handle(): int
    {
        $format = $this->option('format');
        $email = $this->option('email');
        $categoryId = $this->option('category');
        
        $this->info('Generating stock report...');
        
        $report = GenerateStockReportAction::run($format, $categoryId);
        
        if ($email) {
            // Send email
            $this->info("Report sent to {$email}");
        } else {
            $this->line($report);
        }
        
        $this->info('Report generated successfully');
        
        return self::SUCCESS;
    }
}
```

---

## Security Guidelines

### Authentication & Authorization

- Use Laravel Sanctum for API authentication
- Implement policies for all models
- Use gates for complex authorization logic
- Always authorize controller actions
- Use middleware for route protection

### Input Validation

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InventoryItem::class);
    }
    
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'uom_id' => ['required', 'exists:units_of_measure,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
```

### Audit Logging

- Log all data modifications using `spatie/laravel-activitylog`
- Include user, timestamp, old/new values
- Log failed authorization attempts
- Log all authentication events
- Consider blockchain verification for critical operations

---

## Module Development Workflow

When creating a new module or domain:

1. **Define Contracts** - Create interfaces in `Contracts/` directory
2. **Create Models** - Define Eloquent models with relationships
3. **Write Migrations** - Create database schema
4. **Build Repositories** - Implement contract interfaces
5. **Create Actions** - Build discrete business operations
6. **Define Events** - Create events for module communication
7. **Build Services** - Encapsulate complex business logic
8. **Add Policies** - Implement authorization rules
9. **Create Controllers** - Build API endpoints
10. **Write Tests** - Feature and unit tests
11. **Add CLI Commands** - Create console commands
12. **Document APIs** - API documentation

---

## Best Practices Summary

### DO

- ✅ Use strict types in all PHP files
- ✅ Type-hint all parameters and return types
- ✅ Define contracts/interfaces before implementation
- ✅ Use repository pattern for data access
- ✅ Implement action pattern for business operations
- ✅ Use events for module communication
- ✅ Write comprehensive tests (Feature + Unit)
- ✅ Log all data modifications with audit trail
- ✅ Validate all inputs using Form Requests
- ✅ Use resource classes for API responses
- ✅ Apply authorization policies
- ✅ Document complex business logic
- ✅ Use database transactions for atomic operations
- ✅ Implement soft deletes where appropriate
- ✅ Use eager loading to prevent N+1 queries
- ✅ Follow PSR-12 coding standards

### DON'T

- ❌ Create UI components or views (headless system only)
- ❌ Use static Eloquent methods in business logic (use repositories)
- ❌ Put business logic in controllers (use actions/services)
- ❌ Skip input validation
- ❌ Bypass authorization checks
- ❌ Forget audit logging
- ❌ Create tight coupling between modules
- ❌ Use magic numbers (define constants)
- ❌ Ignore database indexing
- ❌ Skip writing tests
- ❌ Use raw SQL queries without parameterization
- ❌ Expose sensitive data in API responses
- ❌ Skip error handling
- ❌ Create god classes or methods

---

## Reference Documentation

### Required Reading

- [Laravel 12.x Documentation](https://laravel.com/docs/12.x)
- [PHP 8.2+ Features](https://www.php.net/releases/8.2/en.php)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)
- [JSON:API Specification](https://jsonapi.org/format/)
- [Domain-Driven Design Patterns](https://martinfowler.com/tags/domain%20driven%20design.html)

### Internal Documentation

- [Product Requirements Document](/docs/prd/PRD.md)
- [Phase 1 MVP Specifications](/docs/prd/PHASE-1-MVP.md)
- [Phase 2-4 Progressive Features](/docs/prd/PHASE-2-4-PROGRESSIVE.md)
- [Module Development Guide](/docs/prd/MODULE-DEVELOPMENT.md)
- [Implementation Checklist](/docs/prd/IMPLEMENTATION-CHECKLIST.md)

### Package Documentation

- [Laravel UOM Management](https://github.com/azaharizaman/laravel-uom-management)
- [Laravel Inventory Management](https://github.com/azaharizaman/laravel-inventory-management)
- [Laravel Backoffice](https://github.com/azaharizaman/laravel-backoffice)
- [Laravel Serial Numbering](https://github.com/azaharizaman/laravel-serial-numbering)
- [PHP Blockchain](https://github.com/azaharizaman/php-blockchain)
- [Laravel Actions](https://laravelactions.com/)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Laravel Model Status](https://github.com/spatie/laravel-model-status)
- [Spatie Laravel Activity Log](https://spatie.be/docs/laravel-activitylog)

---

## Copilot-Specific Instructions

### When Generating Code

1. **Always** check existing patterns in the codebase first
2. **Follow** the domain structure strictly
3. **Use** the appropriate design patterns (Repository, Action, Service)
4. **Include** proper type hints and return types
5. **Add** PHPDoc blocks for complex methods
6. **Implement** proper error handling
7. **Write** corresponding tests
8. **Consider** event-driven communication between modules
9. **Apply** authorization and validation
10. **Log** important operations

### When Refactoring

1. **Maintain** backward compatibility
2. **Preserve** existing contracts/interfaces
3. **Update** tests accordingly
4. **Document** breaking changes
5. **Use** Laravel's migration system for database changes

### When Debugging

1. **Check** Laravel logs (`storage/logs/laravel.log`)
2. **Review** query logs for N+1 issues
3. **Verify** authorization policies
4. **Validate** input data
5. **Check** event listeners

---

**Last Updated:** November 8, 2025  
**Maintained By:** Laravel ERP Development Team  
**Questions?** Refer to `/docs/prd/` folder for detailed specifications

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.27
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>
