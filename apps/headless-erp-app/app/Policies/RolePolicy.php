<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

/**
 * Role Policy
 *
 * Authorization policy for Role model operations.
 * Enforces tenant isolation and prevents deletion of critical roles.
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any roles
     *
     * @param  User  $user  The authenticated user
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-roles');
    }

    /**
     * Determine if the user can view a specific role
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being viewed
     */
    public function view(User $user, Role $role): bool
    {
        // Super admin can view any role
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // User can view roles in their own tenant or global roles (team_id is null)
        return $user->hasPermissionTo('view-roles')
            && ($role->team_id === null || $user->tenant_id === $role->team_id);
    }

    /**
     * Determine if the user can create roles
     *
     * @param  User  $user  The authenticated user
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-roles');
    }

    /**
     * Determine if the user can update a specific role
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being updated
     */
    public function update(User $user, Role $role): bool
    {
        // Super admin can update any role
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Cannot update super-admin role
        if ($role->name === 'super-admin') {
            return false;
        }

        // User can update roles in their own tenant or global roles
        return $user->hasPermissionTo('manage-roles')
            && ($role->team_id === null || $user->tenant_id === $role->team_id);
    }

    /**
     * Determine if the user can delete a specific role
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being deleted
     */
    public function delete(User $user, Role $role): bool
    {
        // Super admin can delete any role except super-admin itself
        if ($user->hasRole('super-admin')) {
            return $role->name !== 'super-admin';
        }

        // Cannot delete super-admin role
        if ($role->name === 'super-admin') {
            return false;
        }

        // User can delete roles in their own tenant
        return $user->hasPermissionTo('manage-roles')
            && $user->tenant_id === $role->team_id;
    }

    /**
     * Determine if the user can assign a role to users
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being assigned
     */
    public function assign(User $user, Role $role): bool
    {
        // Super admin can assign any role
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Cannot assign super-admin role
        if ($role->name === 'super-admin') {
            return false;
        }

        // User can assign roles in their own tenant or global roles
        return $user->hasPermissionTo('assign-roles')
            && ($role->team_id === null || $user->tenant_id === $role->team_id);
    }
}
