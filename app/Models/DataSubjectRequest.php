<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSubjectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'subject_user_id',
        'subject_email',
        'request_type',
        'status',
        'reason',
        'requested_at',
        'resolved_at',
        'resolved_by_administrator_uuid',
        'resolution_metadata',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
            'resolution_metadata' => 'array',
        ];
    }
}
