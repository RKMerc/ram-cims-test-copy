<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    public const LOW_STOCK_AT = 10;

    public const CATEGORY_THRESHOLDS = [
        'Medicine' => ['mode' => 'percent', 'percent' => 0.20],
        'Medical Supply' => ['mode' => 'percent', 'percent' => 0.25],
        'Equipment' => ['mode' => 'fixed', 'threshold' => 1],
        'First Aid Kit' => ['mode' => 'fixed', 'threshold' => 2],
    ];

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
        'minimum_stock',
        'target_stock',
        'kit_complete',
        'ITEM_EXPIRATION_DATE'
    ];

    public function normalizedCategory(): string
    {
        return $this->ITEM_CATEGORY === 'First Aid' ? 'First Aid Kit' : (string) $this->ITEM_CATEGORY;
    }

    public function availableQuantity(): int
    {
        $quantity = max(0, (int) $this->ITEM_QUANTITY);

        if ($this->normalizedCategory() === 'First Aid Kit' && $this->kit_complete !== null && ! $this->kit_complete) {
            return 0;
        }

        return $quantity;
    }

    public function stockAssessment(): array
    {
        $available = $this->availableQuantity();
        $threshold = $this->resolvedThreshold();

        if ($threshold === null) {
            return [
                'status' => 'CONFIGURATION REQUIRED',
                'threshold' => null,
                'available' => $available,
            ];
        }

        if ($available === 0) {
            $status = 'OUT OF STOCK';
        } elseif ($available <= $threshold) {
            $status = 'LOW STOCK';
        } else {
            $status = 'NORMAL';
        }

        return [
            'status' => $status,
            'threshold' => $threshold,
            'available' => $available,
        ];
    }

    public function stockThreshold(): ?int
    {
        return $this->stockAssessment()['threshold'];
    }

    public function needsStockAttention(): bool
    {
        return in_array($this->stockAssessment()['status'], ['LOW STOCK', 'OUT OF STOCK'], true);
    }

    public static function lowStockItems()
    {
        return static::query()
            ->orderBy('ITEM_QUANTITY')
            ->orderBy('GENERIC_NAME')
            ->get()
            ->filter(fn (self $item) => $item->needsStockAttention())
            ->values();
    }

    public static function configurationRequiredItems()
    {
        return static::query()
            ->orderBy('GENERIC_NAME')
            ->get()
            ->filter(fn (self $item) => $item->stockAssessment()['status'] === 'CONFIGURATION REQUIRED')
            ->values();
    }

    private function resolvedThreshold(): ?int
    {
        if ($this->minimum_stock !== null) {
            return (int) $this->minimum_stock;
        }

        $rule = self::CATEGORY_THRESHOLDS[$this->normalizedCategory()] ?? null;
        if ($rule === null) {
            return null;
        }

        if ($rule['mode'] === 'fixed') {
            return (int) $rule['threshold'];
        }

        if ($this->target_stock === null || (int) $this->target_stock < 1) {
            return null;
        }

        return (int) ceil(((int) $this->target_stock) * $rule['percent']);
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