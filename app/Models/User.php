<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'mobile',
        'location',
        'bio',
        'is_active',
        'approved_at',
        'approved_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Check if user has a supervisor role.
     */
    public function isSupervisor(): bool
    {
        return $this->roles()->where('supervisor', true)->exists();
    }

    /**
     * Check if user has a specific role by slug.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user can access system resources.
     */
    public function canAccessSystemResources(): bool
    {
        return $this->isSupervisor() || $this->hasRole('it');
    }

    /**
     * Check if user can approve other users.
     */
    public function canApproveUsers(): bool
    {
        return $this->isSupervisor() || $this->hasRole('power-user') || $this->hasRole('it');
    }

    /**
     * Check if user is approved.
     */
    public function isApproved(): bool
    {
        return !is_null($this->approved_at);
    }

    /**
     * Check if user is active and approved.
     */
    public function isActiveAndApproved(): bool
    {
        return $this->is_active && $this->isApproved();
    }

    /**
     * Check if user has verified their email.
     */
    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user meets all requirements to access the system.
     */
    public function canAccessSystem(): bool
    {
        return $this->isEmailVerified() && $this->is_active && $this->isApproved();
    }

    /**
     * Get the user who approved this user.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get users approved by this user.
     */
    public function approvedUsers()
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    /**
     * Can access to Panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
    }
}
