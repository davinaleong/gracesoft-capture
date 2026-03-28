<?php

namespace App\Models;

use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UseFactory(FormFactory::class)]
class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'application_id',
        'name',
        'public_token',
        'is_active',
        'settings',
    ];

    protected static function booted(): void
    {
        static::creating(function (Form $form): void {
            if (! $form->uuid) {
                $form->uuid = (string) Str::uuid();
            }

            if (! $form->public_token) {
                $form->public_token = 'frm_' . Str::lower(Str::random(24));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'bool',
        ];
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }
}
