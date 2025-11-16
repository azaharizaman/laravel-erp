<?php

declare(strict_types=1);

namespace Nexus\Procurement;

use Illuminate\Support\ServiceProvider;

/**
 * Procurement Service Provider
 *
 * Registers procurement services and bindings for the complete procure-to-pay
 * lifecycle management in the Nexus ERP ecosystem.
 */
class ProcurementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/procurement.php',
            'procurement'
        );

        // Register core procurement services
        $this->registerCoreServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/procurement.php' => config_path('procurement.php'),
        ], 'procurement-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/procurement-api.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/vendor-portal-api.php');

        // Register middleware
        $this->registerMiddleware();

        // Register commands if any
        if ($this->app->runningInConsole()) {
            // Commands will be registered here in future phases
        }
    }

    /**
     * Register core procurement services.
     */
    private function registerCoreServices(): void
    {
        // Register repositories
        $this->app->singleton(\Nexus\Procurement\Repositories\PurchaseRequisitionRepository::class);
        $this->app->singleton(\Nexus\Procurement\Repositories\VendorRepository::class);
        $this->app->singleton(\Nexus\Procurement\Repositories\PurchaseOrderRepository::class);
        $this->app->singleton(\Nexus\Procurement\Repositories\GoodsReceiptRepository::class);

        // Register services
        $this->app->singleton(\Nexus\Procurement\Services\RequisitionApprovalService::class);
        $this->app->singleton(\Nexus\Procurement\Services\PurchaseOrderService::class);
        $this->app->singleton(\Nexus\Procurement\Services\ThreeWayMatchService::class);
        $this->app->singleton(\Nexus\Procurement\Services\GoodsReceiptService::class);
        $this->app->singleton(\Nexus\Procurement\Services\RFQManagementService::class);
        $this->app->singleton(\Nexus\Procurement\Services\BlanketPurchaseOrderService::class);
        $this->app->singleton(\Nexus\Procurement\Services\ContractManagementService::class);
        $this->app->singleton(\Nexus\Procurement\Services\VendorPerformanceService::class);
        $this->app->singleton(\Nexus\Procurement\Services\ProcurementAnalyticsService::class);
        $this->app->singleton(\Nexus\Procurement\Services\VendorPortalService::class);
    }

    /**
     * Register middleware.
     */
    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('vendor-portal', \Nexus\Procurement\Http\Middleware\VendorPortalAuth::class);
    }
}