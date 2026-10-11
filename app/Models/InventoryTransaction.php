<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'item_code',
        'type',
        'quantity_changed',
        'previous_quantity',
        'updated_quantity',
        'user_id',
        'note',
    ];
}
