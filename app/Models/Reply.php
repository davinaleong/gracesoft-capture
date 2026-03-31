<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Reply extends Model
{
    protected $fillable = [
        'enquiry_id',
        'account_id',
        'sender_type',
        'sender_id',
        'email',
        'content',
        'is_internal',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'bool',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reply $reply): void {
            if (! $reply->uuid) {
                $reply->uuid = (string) Str::uuid();
            }
        });
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
