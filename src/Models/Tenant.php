<?php

declare(strict_types=1);

namespace Nexus\Erp\Models;

use Nexus\Tenancy\Models\Tenant as BaseTenant;
use Nexus\Workflow\Adapters\Laravel\Traits\HasWorkflow;

/**
 * Tenant Model - Nexus ERP Orchestration Layer
 * 
 * This model extends the atomic nexus-tenancy package and orchestrates
 * workflow capabilities from nexus-workflow package.
 * 
 * Architecture:
 * - nexus-tenancy: Provides base Tenant model (atomic, zero dependencies)
 * - nexus-workflow: Provides HasWorkflow trait (atomic, zero dependencies)
 * - Nexus\Erp: Orchestrates both packages together
 * 
 * This demonstrates Maximum Atomicity principle: atomic packages remain
 * independent, orchestration happens at Nexus\Erp level.
 */
class Tenant extends BaseTenant
{
    use HasWorkflow;

    /**
     * Define the tenant lifecycle workflow
     * 
     * States:
     * - pending: New tenant awaiting approval
     * - active: Operational tenant
     * - suspended: Temporarily disabled (billing issue, policy violation)
     * - archived: Permanently disabled
     * 
     * Transitions:
     * - activate: pending → active (requires approval)
     * - suspend: active → suspended (can happen automatically or manually)
     * - reactivate: suspended → active (requires payment/resolution)
     * - archive: active|suspended → archived (permanent deletion)
     * - restore: archived → active (rare, requires admin approval)
     * 
     * @return array<string, mixed>
     */
    public function workflowDefinition(): array
    {
        return [
            'id' => 'tenant-lifecycle',
            'label' => 'Tenant Lifecycle Management',
            'version' => '1.0.0',
            'initialState' => 'pending',
            
            'states' => [
                'pending' => [
                    'label' => 'Pending Approval',
                    'description' => 'New tenant awaiting activation',
                ],
                
                'active' => [
                    'label' => 'Active',
                    'description' => 'Tenant is operational',
                ],
                
                'suspended' => [
                    'label' => 'Suspended',
                    'description' => 'Tenant access temporarily disabled',
                ],
                
                'archived' => [
                    'label' => 'Archived',
                    'description' => 'Tenant permanently disabled',
                ],
            ],
            
            'transitions' => [
                'activate' => [
                    'label' => 'Activate Tenant',
                    'from' => ['pending'],
                    'to' => 'active',
                    'guard' => function ($tenant, $context) {
                        // Only activate if has required configuration
                        return $tenant->domain !== null 
                            && $tenant->billing_email !== null
                            && ($context['approved_by'] ?? false);
                    },
                    'after' => function ($tenant, $context) {
                        // Update the status enum in the base model
                        $tenant->update(['status' => \Nexus\Tenancy\Enums\TenantStatus::ACTIVE]);
                        
                        // Log activation
                        activity()
                            ->performedOn($tenant)
                            ->causedBy($context['approved_by'] ?? null)
                            ->log('Tenant activated');
                    },
                ],
                
                'suspend' => [
                    'label' => 'Suspend Tenant',
                    'from' => ['active'],
                    'to' => 'suspended',
                    'before' => function ($tenant, $context) {
                        // Log reason for suspension
                        $reason = $context['reason'] ?? 'No reason provided';
                        activity()
                            ->performedOn($tenant)
                            ->causedBy($context['suspended_by'] ?? null)
                            ->withProperties(['reason' => $reason])
                            ->log('Tenant suspended');
                    },
                    'after' => function ($tenant, $context) {
                        // Update status enum
                        $tenant->update(['status' => \Nexus\Tenancy\Enums\TenantStatus::SUSPENDED]);
                        
                        // TODO: Disable tenant access, notify users
                    },
                ],
                
                'reactivate' => [
                    'label' => 'Reactivate Tenant',
                    'from' => ['suspended'],
                    'to' => 'active',
                    'guard' => function ($tenant, $context) {
                        // Only reactivate if issue is resolved
                        return $context['issue_resolved'] ?? false;
                    },
                    'after' => function ($tenant, $context) {
                        $tenant->update(['status' => \Nexus\Tenancy\Enums\TenantStatus::ACTIVE]);
                        
                        activity()
                            ->performedOn($tenant)
                            ->causedBy($context['reactivated_by'] ?? null)
                            ->log('Tenant reactivated');
                    },
                ],
                
                'archive' => [
                    'label' => 'Archive Tenant',
                    'from' => ['active', 'suspended'],
                    'to' => 'archived',
                    'guard' => function ($tenant, $context) {
                        // Require admin approval for archiving
                        return $context['admin_approved'] ?? false;
                    },
                    'before' => function ($tenant, $context) {
                        // Log archival reason
                        $reason = $context['reason'] ?? 'No reason provided';
                        activity()
                            ->performedOn($tenant)
                            ->causedBy($context['archived_by'] ?? null)
                            ->withProperties(['reason' => $reason])
                            ->log('Tenant archived');
                    },
                    'after' => function ($tenant, $context) {
                        $tenant->update(['status' => \Nexus\Tenancy\Enums\TenantStatus::ARCHIVED]);
                        
                        // TODO: Soft delete tenant data, schedule full deletion
                        $tenant->delete();
                    },
                ],
                
                'restore' => [
                    'label' => 'Restore Archived Tenant',
                    'from' => ['archived'],
                    'to' => 'active',
                    'guard' => function ($tenant, $context) {
                        // Require super admin approval
                        return ($context['super_admin_approved'] ?? false)
                            && ($context['data_intact'] ?? false);
                    },
                    'before' => function ($tenant, $context) {
                        // Restore soft deleted tenant
                        $tenant->restore();
                    },
                    'after' => function ($tenant, $context) {
                        $tenant->update(['status' => \Nexus\Tenancy\Enums\TenantStatus::ACTIVE]);
                        
                        activity()
                            ->performedOn($tenant)
                            ->causedBy($context['restored_by'] ?? null)
                            ->log('Tenant restored from archive');
                    },
                ],
            ],
        ];
    }

    /**
     * Override the workflow state column to use 'workflow_state'
     * This keeps the workflow state separate from the business status enum
     */
    public function getWorkflowStateColumn(): string
    {
        return 'workflow_state';
    }
}
