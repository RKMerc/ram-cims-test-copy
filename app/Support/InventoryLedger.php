<?php

namespace App\Support;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryLedger
{
    public function __construct(private StockWatch $stock)
    {
    }

    public function receive(Inventory $item, int $quantity, ?User $user, string $note = 'Opening stock'): Inventory
    {
        return $this->move($item, $quantity, 'receive', $user, $note, false);
    }

    public function dispense(Inventory $item, int $quantity, ?User $user, ?string $note = null): Inventory
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Enter a quantity of at least 1.',
            ]);
        }

        return $this->move($item, -$quantity, 'dispense', $user, $note ?: 'Dispensed', true);
    }

    public function setQuantity(Inventory $item, int $quantity, ?User $user): Inventory
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'ITEM_QUANTITY' => 'Quantity cannot be negative.',
            ]);
        }

        $current = (int) Inventory::query()->where('ITEM_CODE', $item->ITEM_CODE)->value('ITEM_QUANTITY');
        $delta = $quantity - $current;
        if ($delta === 0) {
            return $item->fresh();
        }

        return $this->move($item, $delta, 'adjustment', $user, 'Quantity updated', false);
    }

    private function move(Inventory $item, int $delta, string $type, ?User $user, string $note, bool $blockExpired): Inventory
    {
        return DB::transaction(function () use ($item, $delta, $type, $user, $note, $blockExpired) {
            $locked = Inventory::query()->where('ITEM_CODE', $item->ITEM_CODE)->lockForUpdate()->firstOrFail();
            $previous = (int) $locked->ITEM_QUANTITY;
            $updated = $previous + $delta;

            if ($updated < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'That quantity is more than the '.$previous.' on hand.',
                ]);
            }

            if ($blockExpired && $locked->ITEM_EXPIRATION_DATE && Carbon::parse($locked->ITEM_EXPIRATION_DATE)->endOfDay()->isPast()) {
                throw ValidationException::withMessages([
                    'quantity' => 'This item is expired and cannot be dispensed.',
                ]);
            }

            $locked->ITEM_QUANTITY = $updated;
            $locked->save();

            InventoryTransaction::create([
                'item_code' => $locked->ITEM_CODE,
                'type' => $type,
                'quantity_changed' => $delta,
                'previous_quantity' => $previous,
                'updated_quantity' => $updated,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            $this->stock->sync($locked);

            return $locked->fresh();
        });
    }
}
