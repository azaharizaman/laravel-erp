# Module Development Guide

**Version:** 1.0.0  
**Date:** November 8, 2025  
**Target:** Laravel 12+ / PHP 8.2+

---

## Module Development Principles

### Core Principles

1. **Contract-First Development:** Define interfaces before implementation
2. **Event-Driven Communication:** Modules communicate via events, not direct calls
3. **Dependency Injection:** No static calls, use container resolution
4. **Single Responsibility:** Each class has one clear purpose
5. **Open-Closed Principle:** Open for extension, closed for modification
6. **Testability:** Every component must be testable in isolation

---

## Module Structure Template

```
app/Modules/{ModuleName}/
├── Actions/                    # Discrete business operations
│   ├── Create{Entity}Action.php
│   ├── Update{Entity}Action.php
│   └── Delete{Entity}Action.php
│
├── Commands/                   # Artisan console commands
│   └── {Module}Command.php
│
├── Contracts/                  # Interfaces
│   ├── {Entity}RepositoryContract.php
│   ├── {Entity}ServiceContract.php
│   └── {Module}ManagerContract.php
│
├── Events/                     # Domain events
│   ├── {Entity}CreatedEvent.php
│   ├── {Entity}UpdatedEvent.php
│   └── {Entity}DeletedEvent.php
│
├── Exceptions/                 # Module-specific exceptions
│   └── {Module}Exception.php
│
├── Factories/                  # Model factories
│   └── {Entity}Factory.php
│
├── Listeners/                  # Event listeners
│   ├── Handle{Entity}Created.php
│   └── Send{Entity}Notification.php
│
├── Models/                     # Eloquent models
│   ├── {Entity}.php
│   └── Concerns/
│       └── Has{Behavior}.php
│
├── Observers/                  # Model observers
│   └── {Entity}Observer.php
│
├── Policies/                   # Authorization policies
│   └── {Entity}Policy.php
│
├── Repositories/               # Data access layer
│   ├── {Entity}Repository.php
│   └── Eloquent{Entity}Repository.php
│
├── Requests/                   # Form requests
│   ├── Store{Entity}Request.php
│   └── Update{Entity}Request.php
│
├── Resources/                  # API resources
│   ├── {Entity}Resource.php
│   └── {Entity}Collection.php
│
├── Services/                   # Business logic services
│   ├── {Module}Manager.php
│   └── {Entity}Service.php
│
├── {ModuleName}ServiceProvider.php
│
└── routes/
    ├── api.php
    └── console.php
```

---

## Step-by-Step Module Creation

### Step 1: Define Module Contracts

```php
<?php

namespace App\Modules\Sales\Contracts;

use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Database\Eloquent\Collection;

interface SalesOrderRepositoryContract
{
    public function find(string $id): ?SalesOrder;
    
    public function create(array $data): SalesOrder;
    
    public function update(SalesOrder $order, array $data): SalesOrder;
    
    public function delete(SalesOrder $order): bool;
    
    public function findByCustomer(string $customerId): Collection;
    
    public function findPendingOrders(): Collection;
}

interface SalesOrderServiceContract
{
    public function confirmOrder(SalesOrder $order): void;
    
    public function cancelOrder(SalesOrder $order, string $reason): void;
    
    public function reserveStock(SalesOrder $order): void;
    
    public function releaseStock(SalesOrder $order): void;
}
```

### Step 2: Create Models with Traits

```php
<?php

namespace App\Modules\Sales\Models;

use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStatus\HasStatuses;
use AzahariZaman\ControlledNumber\Traits\HasSerialNumbering;

class SalesOrder extends Model
{
    use HasUuids,
        BelongsToTenant,
        SoftDeletes,
        LogsActivity,
        HasStatuses,
        HasSerialNumbering;
    
    protected $table = 'sales_orders';
    
    protected $serialPattern = 'sales_order';
    protected $serialColumn = 'order_number';
    
    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'order_date',
        'delivery_date',
        'salesperson_id',
        'payment_terms_id',
        'shipping_method_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
    ];
    
    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
    
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    
    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }
    
    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'salesperson_id');
    }
    
    // Scopes
    public function scopePending($query)
    {
        return $query->whereHas('statuses', function ($q) {
            $q->where('name', 'confirmed')
              ->orWhere('name', 'in_progress');
        });
    }
}
```

### Step 3: Implement Repository

```php
<?php

namespace App\Modules\Sales\Repositories;

use App\Modules\Sales\Contracts\SalesOrderRepositoryContract;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Database\Eloquent\Collection;

class EloquentSalesOrderRepository implements SalesOrderRepositoryContract
{
    public function find(string $id): ?SalesOrder
    {
        return SalesOrder::with(['customer', 'lines', 'salesperson'])
            ->find($id);
    }
    
    public function create(array $data): SalesOrder
    {
        return SalesOrder::create($data);
    }
    
    public function update(SalesOrder $order, array $data): SalesOrder
    {
        $order->update($data);
        return $order->fresh();
    }
    
    public function delete(SalesOrder $order): bool
    {
        return $order->delete();
    }
    
    public function findByCustomer(string $customerId): Collection
    {
        return SalesOrder::where('customer_id', $customerId)
            ->with(['lines', 'salesperson'])
            ->orderBy('order_date', 'desc')
            ->get();
    }
    
    public function findPendingOrders(): Collection
    {
        return SalesOrder::pending()
            ->with(['customer', 'lines'])
            ->get();
    }
}
```

### Step 4: Create Service Layer

```php
<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Contracts\SalesOrderServiceContract;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Events\SalesOrderConfirmedEvent;
use App\Modules\Sales\Events\SalesOrderCancelledEvent;
use App\Modules\Inventory\Contracts\StockReservationContract;
use Illuminate\Support\Facades\DB;

class SalesOrderService implements SalesOrderServiceContract
{
    public function __construct(
        private StockReservationContract $stockReservation
    ) {}
    
    public function confirmOrder(SalesOrder $order): void
    {
        DB::transaction(function () use ($order) {
            // Change status
            $order->setStatus('confirmed', 'Order confirmed by user');
            
            // Reserve stock
            $this->reserveStock($order);
            
            // Fire event
            event(new SalesOrderConfirmedEvent($order));
        });
    }
    
    public function cancelOrder(SalesOrder $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            // Release reserved stock
            $this->releaseStock($order);
            
            // Change status
            $order->setStatus('cancelled', $reason);
            
            // Fire event
            event(new SalesOrderCancelledEvent($order, $reason));
        });
    }
    
    public function reserveStock(SalesOrder $order): void
    {
        foreach ($order->lines as $line) {
            $this->stockReservation->reserve(
                itemId: $line->item_id,
                warehouseId: $order->warehouse_id,
                quantity: $line->quantity,
                uom: $line->uom_id,
                reference: "SO-{$order->order_number}",
                referenceId: $order->id
            );
        }
    }
    
    public function releaseStock(SalesOrder $order): void
    {
        foreach ($order->lines as $line) {
            $this->stockReservation->release(
                referenceId: $order->id,
                lineId: $line->id
            );
        }
    }
}
```

### Step 5: Create Actions (Laravel Actions)

```php
<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Contracts\SalesOrderRepositoryContract;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Requests\CreateSalesOrderRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateSalesOrderAction
{
    use AsAction;
    
    public function __construct(
        private SalesOrderRepositoryContract $repository
    ) {}
    
    public function handle(array $data): SalesOrder
    {
        // Create header
        $order = $this->repository->create([
            'customer_id' => $data['customer_id'],
            'order_date' => $data['order_date'] ?? now(),
            'delivery_date' => $data['delivery_date'],
            'salesperson_id' => $data['salesperson_id'],
            'payment_terms_id' => $data['payment_terms_id'],
            'shipping_method_id' => $data['shipping_method_id'],
            'notes' => $data['notes'] ?? null,
        ]);
        
        // Create lines
        foreach ($data['lines'] as $lineData) {
            $order->lines()->create($lineData);
        }
        
        // Calculate totals
        $this->calculateTotals($order);
        
        return $order->fresh(['customer', 'lines']);
    }
    
    public function asController(CreateSalesOrderRequest $request): SalesOrder
    {
        return $this->handle($request->validated());
    }
    
    public function asCommand(Command $command): void
    {
        $data = [
            'customer_id' => $command->argument('customer_id'),
            // ... map command arguments
        ];
        
        $order = $this->handle($data);
        
        $command->info("Sales order created: {$order->order_number}");
    }
    
    private function calculateTotals(SalesOrder $order): void
    {
        $subtotal = $order->lines->sum('line_total');
        $taxAmount = $order->lines->sum('tax_amount');
        $discountAmount = $order->lines->sum('discount_amount');
        
        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $subtotal + $taxAmount - $discountAmount,
        ]);
    }
}
```

### Step 6: Define Events

```php
<?php

namespace App\Modules\Sales\Events;

use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderConfirmedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public SalesOrder $order
    ) {}
}

class SalesOrderCancelledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public SalesOrder $order,
        public string $reason
    ) {}
}
```

### Step 7: Create Listeners

```php
<?php

namespace App\Modules\Sales\Listeners;

use App\Modules\Sales\Events\SalesOrderConfirmedEvent;
use App\Modules\Inventory\Contracts\StockReservationContract;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReserveStockForOrder implements ShouldQueue
{
    public function __construct(
        private StockReservationContract $stockReservation
    ) {}
    
    public function handle(SalesOrderConfirmedEvent $event): void
    {
        foreach ($event->order->lines as $line) {
            $this->stockReservation->reserve(
                itemId: $line->item_id,
                warehouseId: $event->order->warehouse_id,
                quantity: $line->quantity,
                uom: $line->uom_id,
                reference: "SO-{$event->order->order_number}",
                referenceId: $event->order->id
            );
        }
    }
}
```

### Step 8: Register Module Service Provider

```php
<?php

namespace App\Modules\Sales;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Sales\Contracts\SalesOrderRepositoryContract;
use App\Modules\Sales\Repositories\EloquentSalesOrderRepository;
use App\Modules\Sales\Contracts\SalesOrderServiceContract;
use App\Modules\Sales\Services\SalesOrderService;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Policies\SalesOrderPolicy;
use App\Modules\Sales\Observers\SalesOrderObserver;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register contracts
        $this->app->bind(
            SalesOrderRepositoryContract::class,
            EloquentSalesOrderRepository::class
        );
        
        $this->app->bind(
            SalesOrderServiceContract::class,
            SalesOrderService::class
        );
        
        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sales.php',
            'sales'
        );
    }
    
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/sales');
        
        // Register policies
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        
        // Register observers
        SalesOrder::observe(SalesOrderObserver::class);
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register commands here
            ]);
        }
        
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/sales.php' => config_path('sales.php'),
        ], 'sales-config');
    }
}
```

---

## API Resource Development

### Resource Class

```php
<?php

namespace App\Modules\Sales\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'order_date' => $this->order_date->toDateString(),
            'delivery_date' => $this->delivery_date->toDateString(),
            'status' => $this->status,
            'lines' => SalesOrderLineResource::collection($this->whenLoaded('lines')),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### API Controller

```php
<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CreateSalesOrderAction;
use App\Modules\Sales\Actions\UpdateSalesOrderAction;
use App\Modules\Sales\Contracts\SalesOrderRepositoryContract;
use App\Modules\Sales\Requests\CreateSalesOrderRequest;
use App\Modules\Sales\Requests\UpdateSalesOrderRequest;
use App\Modules\Sales\Resources\SalesOrderResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderController extends Controller
{
    public function __construct(
        private SalesOrderRepositoryContract $repository
    ) {}
    
    public function index(): JsonResource
    {
        $orders = $this->repository->paginate(
            perPage: request('per_page', 50)
        );
        
        return SalesOrderResource::collection($orders);
    }
    
    public function store(
        CreateSalesOrderRequest $request,
        CreateSalesOrderAction $action
    ): JsonResource {
        $order = $action->handle($request->validated());
        
        return new SalesOrderResource($order);
    }
    
    public function show(string $id): JsonResource
    {
        $order = $this->repository->find($id);
        
        abort_if(!$order, 404, 'Sales order not found');
        
        return new SalesOrderResource($order);
    }
    
    public function update(
        UpdateSalesOrderRequest $request,
        string $id,
        UpdateSalesOrderAction $action
    ): JsonResource {
        $order = $this->repository->find($id);
        
        abort_if(!$order, 404, 'Sales order not found');
        
        $order = $action->handle($order, $request->validated());
        
        return new SalesOrderResource($order);
    }
    
    public function destroy(string $id): JsonResponse
    {
        $order = $this->repository->find($id);
        
        abort_if(!$order, 404, 'Sales order not found');
        
        $this->repository->delete($order);
        
        return response()->json(null, 204);
    }
}
```

---

## CLI Command Development

```php
<?php

namespace App\Modules\Sales\Commands;

use App\Modules\Sales\Actions\CreateSalesOrderAction;
use Illuminate\Console\Command;

class CreateSalesOrderCommand extends Command
{
    protected $signature = 'erp:sales:order:create
                            {customer-id : Customer UUID}
                            {--delivery-date= : Delivery date (Y-m-d)}
                            {--salesperson-id= : Salesperson UUID}';
    
    protected $description = 'Create a new sales order';
    
    public function handle(CreateSalesOrderAction $action): int
    {
        $data = [
            'customer_id' => $this->argument('customer-id'),
            'delivery_date' => $this->option('delivery-date') ?? now()->addDays(7),
            'salesperson_id' => $this->option('salesperson-id'),
            'lines' => $this->collectLines(),
        ];
        
        try {
            $order = $action->handle($data);
            
            $this->info("✓ Sales order created: {$order->order_number}");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $order->id],
                    ['Order Number', $order->order_number],
                    ['Customer', $order->customer->name],
                    ['Total Amount', number_format($order->total_amount, 2)],
                ]
            );
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("✗ Failed to create sales order: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
    
    private function collectLines(): array
    {
        $lines = [];
        
        while ($this->confirm('Add line item?', true)) {
            $lines[] = [
                'item_id' => $this->ask('Item ID'),
                'quantity' => $this->ask('Quantity'),
                'unit_price' => $this->ask('Unit Price'),
            ];
        }
        
        return $lines;
    }
}
```

---

## Testing Guidelines

### Unit Test Example

```php
<?php

namespace Tests\Unit\Modules\Sales\Services;

use Tests\TestCase;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Services\SalesOrderService;
use App\Modules\Inventory\Contracts\StockReservationContract;
use Mockery;

class SalesOrderServiceTest extends TestCase
{
    public function test_confirm_order_reserves_stock(): void
    {
        // Arrange
        $stockReservation = Mockery::mock(StockReservationContract::class);
        $service = new SalesOrderService($stockReservation);
        
        $order = SalesOrder::factory()
            ->has(SalesOrderLine::factory()->count(2))
            ->create();
        
        $stockReservation->shouldReceive('reserve')
            ->times(2)
            ->andReturn(true);
        
        // Act
        $service->confirmOrder($order);
        
        // Assert
        $this->assertEquals('confirmed', $order->fresh()->status->name);
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature\Api\V1\Sales;

use Tests\TestCase;
use App\Modules\Sales\Models\SalesOrder;
use App\Models\User;

class SalesOrderControllerTest extends TestCase
{
    public function test_can_create_sales_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $data = [
            'customer_id' => Customer::factory()->create()->id,
            'delivery_date' => now()->addDays(7)->toDateString(),
            'lines' => [
                [
                    'item_id' => Item::factory()->create()->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                ],
            ],
        ];
        
        // Act
        $response = $this->postJson('/api/v1/sales/orders', $data);
        
        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'customer',
                    'lines',
                    'total_amount',
                ],
            ]);
        
        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $data['customer_id'],
        ]);
    }
}
```

---

## Module Configuration

### Module Config File

```php
<?php

// config/sales.php

return [
    /*
    |--------------------------------------------------------------------------
    | Sales Module Configuration
    |--------------------------------------------------------------------------
    */
    
    'enabled' => env('MODULE_SALES_ENABLED', true),
    
    'dependencies' => [
        'core',
        'backoffice',
        'inventory',
    ],
    
    'order' => [
        'default_payment_terms' => 'NET30',
        'require_approval' => true,
        'approval_threshold' => 10000.00,
        'auto_reserve_stock' => true,
    ],
    
    'quotation' => [
        'default_validity_days' => 30,
        'auto_convert' => false,
    ],
    
    'pricing' => [
        'decimal_places' => 2,
        'rounding_method' => 'half-up',
    ],
];
```

---

## Best Practices Checklist

- [ ] All contracts defined before implementation
- [ ] Repository pattern for data access
- [ ] Service layer for business logic
- [ ] Actions for discrete operations
- [ ] Events for cross-module communication
- [ ] Policies for authorization
- [ ] Observers for model lifecycle hooks
- [ ] Form requests for validation
- [ ] API resources for response formatting
- [ ] Factory classes for testing
- [ ] Comprehensive test coverage (unit, integration, feature)
- [ ] API documentation (OpenAPI spec)
- [ ] CLI commands for operations
- [ ] Database migrations versioned
- [ ] Configuration externalized
- [ ] Logging for critical operations
- [ ] Error handling with custom exceptions

---

**Document Status:** Active Guide  
**Target Audience:** Development Team  
**Maintenance:** Update with new patterns and practices
