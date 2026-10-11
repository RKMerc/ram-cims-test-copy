<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    protected $fillable = [
        'item_code',
        'status',
        'opened_at',
        'last_notified_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
