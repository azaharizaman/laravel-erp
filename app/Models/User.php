<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domains\Core\Enums\UserStatus;
use App\Domains\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, HasUuids, LogsActivity, Notifiable, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'status',
        'email_verified_at',
        'last_login_at',
        'mfa_enabled',
        'mfa_secret',
        'failed_login_attempts',
        'locked_until',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted',
            'failed_login_attempts' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if the user account is locked.
     *
     * Checks both permanent lock status and temporary lockout timestamp.
     */
    public function isLocked(): bool
    {
        if ($this->status === UserStatus::LOCKED) {
            return true;
        }

        // Check temporary lockout
        if ($this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Check if MFA is enabled for the user.
     */
    public function hasMfaEnabled(): bool
    {
        return $this->mfa_enabled === true && ! empty($this->mfa_secret);
    }

    /**
     * Increment failed login attempts.
     *
     * Automatically locks the account for 30 minutes after 5 failed attempts.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->failed_login_attempts++;

        // Lock account after 5 failed attempts
        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(30);
        }

        $this->save();
    }

    /**
     * Reset failed login attempts.
     *
     * Clears the failed login counter and removes any temporary lockout.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'status',
                'tenant_id',
                'is_admin',
                'mfa_enabled',
                'last_login_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'users';
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'tenant_id' => $this->tenant_id,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at?->timestamp,
        ];
    }
}
