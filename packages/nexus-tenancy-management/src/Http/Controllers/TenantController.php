<?php

declare(strict_types=1);

namespace Nexus\TenancyManagement\Http\Controllers;

use Nexus\TenancyManagement\Actions\ActivateTenantAction;
use Nexus\TenancyManagement\Actions\ArchiveTenantAction;
use Nexus\TenancyManagement\Actions\CreateTenantAction;
use Nexus\TenancyManagement\Actions\DeleteTenantAction;
use Nexus\TenancyManagement\Actions\EndImpersonationAction;
use Nexus\TenancyManagement\Actions\StartImpersonationAction;
use Nexus\TenancyManagement\Actions\SuspendTenantAction;
use Nexus\TenancyManagement\Actions\UpdateTenantAction;
use Nexus\TenancyManagement\Contracts\TenantRepositoryContract;
use Nexus\TenancyManagement\Enums\TenantStatus;
use Nexus\TenancyManagement\Http\Requests\StoreTenantRequest;
use Nexus\TenancyManagement\Http\Requests\UpdateTenantRequest;
use Nexus\TenancyManagement\Http\Resources\TenantResource;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * Tenant Controller
 *
 * RESTful API endpoints for tenant management.
 * Handles CRUD operations and tenant lifecycle management.
 */
class TenantController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        protected readonly TenantRepositoryContract $repository
    ) {
        // Apply authentication middleware to all routes
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of tenants
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // Check authorization
        if (! auth()->user()->can('view-tenants')) {
            abort(403, 'Unauthorized to view tenants');
        }

        // Validate input
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $filters = [
            'status' => $validated['status'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $tenants = $this->repository->paginate($perPage, $filters);

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created tenant
     */
    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $tenant = $action->handle($request->validated());

        return TenantResource::make($tenant)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified tenant
     */
    public function show(Tenant $tenant): TenantResource
    {
        $this->authorize('view-tenant', $tenant);

        return TenantResource::make($tenant);
    }

    /**
     * Update the specified tenant
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenantAction $action): TenantResource
    {
        $tenant = $action->handle($tenant, $request->validated());

        return TenantResource::make($tenant);
    }

    /**
     * Remove the specified tenant
     */
    public function destroy(Tenant $tenant, DeleteTenantAction $action): JsonResponse
    {
        $this->authorize('delete-tenant', $tenant);

        $action->handle($tenant);

        return response()->json(null, 204);
    }

    /**
     * Suspend the specified tenant
     */
    public function suspend(Request $request, Tenant $tenant, SuspendTenantAction $action): TenantResource
    {
        $this->authorize('suspend-tenant', $tenant);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason'));

        return TenantResource::make($tenant);
    }

    /**
     * Activate the specified tenant
     */
    public function activate(Request $request, Tenant $tenant, ActivateTenantAction $action): TenantResource
    {
        $this->authorize('activate-tenant', $tenant);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason', 'Manual activation'));

        return TenantResource::make($tenant);
    }

    /**
     * Archive the specified tenant
     */
    public function archive(Request $request, Tenant $tenant, ArchiveTenantAction $action): TenantResource
    {
        $this->authorize('archive-tenant', $tenant);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $tenant = $action->handle($tenant, $request->input('reason'));

        return TenantResource::make($tenant);
    }

    /**
     * Start impersonating the specified tenant
     */
    public function impersonate(Request $request, Tenant $tenant, StartImpersonationAction $action): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $action->handle($request->user(), $tenant, $request->input('reason'));

        return response()->json([
            'message' => 'Impersonation started successfully.',
            'tenant' => TenantResource::make($tenant),
        ]);
    }

    /**
     * End the current impersonation session
     */
    public function endImpersonation(Request $request, EndImpersonationAction $action): JsonResponse
    {
        $action->handle($request->user());

        return response()->json([
            'message' => 'Impersonation ended successfully.',
        ]);
    }
}
