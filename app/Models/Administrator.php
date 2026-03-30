<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Administrator extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'display_name',
        'email',
        'password',
        'status',
        'role',
        'mfa_enabled',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Administrator $administrator): void {
            if (! $administrator->uuid) {
                $administrator->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mfa_enabled' => 'bool',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roleName(): string
    {
        $defaultRole = (string) config('capture.admin_authorization.default_role', 'compliance_admin');

        return is_string($this->role) && $this->role !== '' ? $this->role : $defaultRole;
    }

    public function hasCapability(string $capability): bool
    {
        $roleCapabilities = (array) config('capture.admin_authorization.role_capabilities', []);
        $capabilities = $roleCapabilities[$this->roleName()] ?? [];

        if (! is_array($capabilities)) {
            return false;
        }

        return in_array($capability, $capabilities, true);
    }
}
