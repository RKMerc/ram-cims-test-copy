<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    public const LOW_STOCK_AT = 10;

    public const FORMS = [
        'Medicine' => ['Capsule', 'Syrup', 'Drops', 'Cream', 'Injection'],
        'Equipment' => ['Device', 'Instrument', 'Machine'],
        'Medical Supply' => ['Box', 'Pack', 'Roll', 'Bottle', 'Pair', 'Piece'],
        'First Aid Kit' => ['Kit', 'Box', 'Pouch'],
    ];

    protected $table = 'inventory';
    protected $primaryKey = 'ITEM_CODE';
    
    // Set to false if ITEM_CODE is a custom string (e.g., "MED-001") rather than an auto-incrementing integer
    public $incrementing = false; // Change from false to true
    protected $keyType = 'int';   // Change from 'string' to 'int'

    public $timestamps = false; 

    // Define columns safe for mass-assignment (excluding price, adding generic/brand)
    protected $fillable = [
        'ITEM_CODE',
        'GENERIC_NAME',
        'BRAND_NAME',
        'ITEM_DOSAGE',
        'ITEM_UNIT',
        'ITEM_FORM',
        'ITEM_CATEGORY',
        'ITEM_QUANTITY',
        'ITEM_EXPIRATION_DATE'
    ];

    public function scopeLowStock($query)
    {
        return $query->where('ITEM_QUANTITY', '<=', self::LOW_STOCK_AT);
    }

    public static function lowStockItems()
    {
        return static::query()->lowStock()->orderBy('ITEM_QUANTITY')->orderBy('GENERIC_NAME')->get();
    }

    public function dosageLabel(): string
    {
        $label = trim(($this->ITEM_DOSAGE ?: '').' '.($this->ITEM_UNIT ?: '').' '.($this->ITEM_FORM ?: ''));

        return $label !== '' ? $label : 'N/A';
    }

    public static function nextItemCode(): int
    {
        $max = static::query()->max('ITEM_CODE');

        return $max ? ((int) $max) + 1 : 1001;
    }
}