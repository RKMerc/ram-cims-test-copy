<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatch extends Model
{
    protected $fillable = [
        'item_code',
        'batch_code',
        'quantity',
        'expiration_date',
        'received_on',
        'receipt_reference',
        'needs_review',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'received_on' => 'date',
            'needs_review' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'item_code', 'ITEM_CODE');
    }

    public function dispensableQuantity(string $category): int
    {
        if ((int) $this->quantity < 1) {
            return 0;
        }

        if ($this->isExpired()) {
            return 0;
        }

        if ($category === 'Medicine' && $this->expiration_date === null) {
            return 0;
        }

        return (int) $this->quantity;
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->copy()->endOfDay()->isPast();
    }

    public function stateLabel(): string
    {
        if ($this->expiration_date === null) {
            return 'Needs review';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        $days = max(1, (int) config('clinic.near_expiration_days', 30));
        if ($this->expiration_date->copy()->startOfDay()->lte(Carbon::now()->addDays($days))) {
            return 'Near expiration';
        }

        return 'Valid';
    }
}
