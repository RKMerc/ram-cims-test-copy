<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LowStockReport extends Model
{
    protected $fillable = [
        'notified_by',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
