<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BreakGlassApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'scope',
        'requested_by_administrator_uuid',
        'approved_by_administrator_uuid',
        'reason',
        'requested_at',
        'approved_at',
        'expires_at',
        'revoked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNotNull('approved_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
