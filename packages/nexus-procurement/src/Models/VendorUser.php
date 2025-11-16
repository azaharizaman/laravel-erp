<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Vendor Portal User
 *
 * External vendor users who can access the vendor portal.
 */
class VendorUser extends Authenticatable
{
    use BelongsToTenant, Notifiable;

    protected $fillable = [
        'vendor_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'is_primary_contact',
        'is_active',
        'last_login_at',
        'email_verified_at',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    protected $hidden = [
        'password',
        'password_reset_token',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
    ];

    /**
     * Get the vendor this user belongs to.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Check if user can access vendor portal.
     */
    public function canAccessPortal(): bool
    {
        return $this->is_active && $this->vendor->status === 'active';
    }

    /**
     * Send password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        // Implementation will use notification system
    }
}