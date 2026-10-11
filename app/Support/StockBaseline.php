<?php

namespace App\Support;

use App\Models\Inventory;
use App\Models\InventoryTransaction;

class StockBaseline
{
    public function initialize(Inventory $item, ?int $knownOpening = null): string
    {
        if (! $item->usesPercentageThreshold()) {
            if ($item->exists && $item->needs_baseline_review) {
                $item->needs_baseline_review = false;
                $item->save();
            }

            return 'skipped';
        }

        if ($item->target_stock !== null && (int) $item->target_stock >= 1) {
            if ($item->baseline_status === null || $item->needs_baseline_review) {
                $item->baseline_status = $item->baseline_status ?: 'approved';
                $item->needs_baseline_review = false;
                $item->save();

                return 'preserved';
            }

            return 'unchanged';
        }

        $baseline = $this->openingStock($item);
        $source = 'opening';

        if ($baseline < 1 && $knownOpening !== null && $knownOpening > 0) {
            $baseline = $knownOpening;
            $source = 'current';
        }

        if ($baseline < 1) {
            $baseline = (int) $item->ITEM_QUANTITY;
            $source = 'current';
        }

        if ($baseline < 1) {
            if ($item->exists && ! $item->needs_baseline_review) {
                $item->needs_baseline_review = true;
                $item->save();
            }

            return 'review';
        }

        $item->target_stock = $baseline;
        $item->baseline_status = 'provisional';
        $item->needs_baseline_review = false;
        $item->save();

        return $source;
    }

    private function openingStock(Inventory $item): int
    {
        if (! $item->exists) {
            return 0;
        }

        $received = InventoryTransaction::query()
            ->where('item_code', $item->ITEM_CODE)
            ->where('type', 'receive')
            ->where('previous_quantity', 0)
            ->orderBy('id')
            ->first();

        if ($received && (int) $received->updated_quantity > 0) {
            return (int) $received->updated_quantity;
        }

        $earliest = InventoryTransaction::query()
            ->where('item_code', $item->ITEM_CODE)
            ->orderBy('id')
            ->first();

        if ($earliest && (int) $earliest->previous_quantity > 0) {
            return (int) $earliest->previous_quantity;
        }

        return 0;
    }
}
