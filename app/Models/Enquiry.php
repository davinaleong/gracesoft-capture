<?php

namespace App\Models;

use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UseFactory(EnquiryFactory::class)]
class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'account_id',
        'application_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'contacted_at',
        'closed_at',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (Enquiry $enquiry): void {
            if (! $enquiry->uuid) {
                $enquiry->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
            'message' => 'encrypted',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
