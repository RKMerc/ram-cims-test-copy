<?php

namespace App\Support;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryLedger
{
    public function __construct(private StockWatch $stock) {}

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

    public function attachOpeningBatch(Inventory $item, int $quantity, ?string $batchCode, ?string $receipt): void
    {
        if ($quantity < 1 || InventoryBatch::query()->where('item_code', $item->ITEM_CODE)->exists()) {
            return;
        }

        $code = trim((string) $batchCode);

        InventoryBatch::query()->create([
            'item_code' => $item->ITEM_CODE,
            'batch_code' => $code !== '' ? $code : null,
            'quantity' => $quantity,
            'expiration_date' => $item->ITEM_EXPIRATION_DATE,
            'receipt_reference' => trim((string) $receipt) !== '' ? trim((string) $receipt) : null,
            'needs_review' => $code === '',
        ]);
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

            $batches = InventoryBatch::query()->where('item_code', $locked->ITEM_CODE)->lockForUpdate()->get();

            if ($batches->isEmpty()) {
                if ($blockExpired && $locked->ITEM_EXPIRATION_DATE && Carbon::parse($locked->ITEM_EXPIRATION_DATE)->endOfDay()->isPast()) {
                    throw ValidationException::withMessages([
                        'quantity' => 'This item is expired and cannot be dispensed.',
                    ]);
                }

                $locked->ITEM_QUANTITY = $updated;
            } else {
                if ($delta < 0) {
                    $note = $this->deductBatches($locked, $batches, abs($delta), $blockExpired, $note);
                } elseif ($delta > 0 && $type === 'adjustment' && $batches->count() === 1) {
                    $batch = $batches->first();
                    $batch->quantity = (int) $batch->quantity + $delta;
                    $batch->expiration_date = $locked->ITEM_EXPIRATION_DATE;
                    $batch->save();
                } elseif ($delta > 0) {
                    InventoryBatch::query()->create([
                        'item_code' => $locked->ITEM_CODE,
                        'batch_code' => null,
                        'quantity' => $delta,
                        'expiration_date' => $locked->ITEM_EXPIRATION_DATE,
                        'needs_review' => true,
                    ]);
                }

                $locked->ITEM_QUANTITY = (int) InventoryBatch::query()->where('item_code', $locked->ITEM_CODE)->sum('quantity');
            }

            $locked->save();

            InventoryTransaction::create([
                'item_code' => $locked->ITEM_CODE,
                'type' => $type,
                'quantity_changed' => $delta,
                'previous_quantity' => $previous,
                'updated_quantity' => (int) $locked->ITEM_QUANTITY,
                'user_id' => $user?->id,
                'note' => $note,
            ]);

            $this->stock->sync($locked);

            return $locked->fresh();
        });
    }

    private function deductBatches(Inventory $item, Collection $batches, int $quantity, bool $blockExpired, string $note): string
    {
        $category = $item->normalizedCategory();
        $ordered = $batches->sortBy(fn (InventoryBatch $batch) => $batch->expiration_date?->format('Y-m-d') ?? '9999-12-31')->values();
        $remaining = $quantity;
        $used = [];

        foreach ($ordered as $batch) {
            $available = $blockExpired ? $batch->dispensableQuantity($category) : (int) $batch->quantity;
            if ($available < 1) {
                continue;
            }

            $take = min($remaining, $available);
            $batch->quantity = (int) $batch->quantity - $take;
            $batch->save();
            $used[] = ($batch->batch_code ?: 'unlabeled').' '.$take;
            $remaining -= $take;

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            $expired = $batches->contains(fn (InventoryBatch $batch) => $batch->isExpired());

            throw ValidationException::withMessages([
                'quantity' => $expired
                    ? 'This item is expired and cannot be dispensed.'
                    : 'That quantity is more than the valid stock on hand.',
            ]);
        }

        return $note.' FEFO: '.implode(', ', $used);
    }
}
