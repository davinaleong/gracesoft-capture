<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Administrator extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'display_name',
        'email',
        'password',
        'status',
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
}
