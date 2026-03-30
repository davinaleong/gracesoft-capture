<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityEventSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        'metric_key',
        'metric_value',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'metric_value' => 'int',
            'metadata' => 'array',
        ];
    }
}
