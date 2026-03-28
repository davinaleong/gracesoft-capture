<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[UseFactory(NoteFactory::class)]
class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'enquiry_id',
        'user_id',
        'content',
    ];

    protected static function booted(): void
    {
        static::creating(function (Note $note): void {
            if (! $note->uuid) {
                $note->uuid = (string) Str::uuid();
            }
        });
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
