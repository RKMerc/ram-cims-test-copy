<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\LowStockReport;
use App\Support\InventoryLedger;
use App\Support\StockWatch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index()
    {
        $supplies = Inventory::query()->orderBy('ITEM_CODE')->get();
        $nextItemCode = Inventory::nextItemCode();
        $lowStockItems = Inventory::lowStockItems();
        $configurationItems = Inventory::configurationRequiredItems();
        $lowStockReport = LowStockReport::query()->latest()->first();

        return view('inventory.index', compact('supplies', 'nextItemCode', 'lowStockItems', 'configurationItems', 'lowStockReport'));
    }

    public function notifyLogistics()
    {
        $items = Inventory::lowStockItems();

        LowStockReport::query()->delete();

        LowStockReport::create([
            'notified_by' => auth()->user()?->name ?: auth()->user()?->email,
            'items' => $items->map(fn (Inventory $item) => [
                'code' => $item->ITEM_CODE,
                'generic_name' => $item->GENERIC_NAME,
                'brand_name' => $item->BRAND_NAME,
                'dosage' => $item->dosageLabel() === 'N/A' ? '' : $item->dosageLabel(),
                'category' => $item->ITEM_CATEGORY,
                'quantity' => $item->ITEM_QUANTITY,
                'expiration_date' => $item->ITEM_EXPIRATION_DATE,
            ])->values()->all(),
        ]);

        $low = $items->count();
        $message = $low > 0
            ? 'Logistics notified for '.$low.' low-stock item'.($low === 1 ? '' : 's').'.'
            : 'Logistics notified. All stock levels are within range.';

        return redirect('/inventory')->with('success', $message);
    }

    public function store(Request $request, InventoryLedger $ledger)
    {
        $validated = $this->validatedItem($request);
        $quantity = (int) $validated['ITEM_QUANTITY'];
        $validated['ITEM_QUANTITY'] = 0;
        $validated['ITEM_CODE'] = Inventory::nextItemCode();
        $item = Inventory::create($validated);

        if ($quantity > 0) {
            $ledger->receive($item, $quantity, $request->user(), 'Opening stock');
        } else {
            app(StockWatch::class)->sync($item);
        }

        return redirect('/inventory')->with('success', 'Item '.$item->ITEM_CODE.' added to inventory.');
    }

    public function update(Request $request, $inventory, InventoryLedger $ledger)
    {
        $item = Inventory::where('ITEM_CODE', $inventory)->firstOrFail();
        $validated = $this->validatedItem($request);
        $quantity = (int) $validated['ITEM_QUANTITY'];
        unset($validated['ITEM_QUANTITY']);
        $item->update($validated);
        $ledger->setQuantity($item->fresh(), $quantity, $request->user());

        return response()->json(['message' => 'Item updated successfully!']);
    }

    public function dispense(Request $request, $inventory, InventoryLedger $ledger)
    {
        $quantity = (int) $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ])['quantity'];

        $item = Inventory::where('ITEM_CODE', $inventory)->firstOrFail();
        $updated = $ledger->dispense($item, $quantity, $request->user());

        return redirect('/inventory')->with('success', 'Dispensed '.$quantity.' of '.$updated->GENERIC_NAME.'. '.$updated->ITEM_QUANTITY.' remaining.');
    }

    public function destroy($inventory)
    {
        $item = Inventory::where('ITEM_CODE', $inventory)->firstOrFail();
        $name = $item->GENERIC_NAME;
        $item->delete();

        return redirect('/inventory')->with('success', $name.' was deleted from inventory.');
    }

    private function validatedItem(Request $request): array
    {
        $category = (string) $request->input('ITEM_CATEGORY');
        $isMedicine = $category === 'Medicine';
        $tracksExpiration = in_array($category, ['Medicine', 'Medical Supply'], true);

        $validated = $request->validate([
            'GENERIC_NAME' => 'required|string|max:255',
            'BRAND_NAME' => 'nullable|string|max:255',
            'ITEM_DOSAGE' => [$isMedicine ? 'required' : 'nullable', 'string', 'max:100'],
            'ITEM_UNIT' => [$isMedicine ? 'required' : 'nullable', 'string', Rule::in([
                'Liter (L)', 'Milliliter (mL)', 'Gram (g)', 'Milligram (mg)', 'Pieces / Pcs',
            ])],
            'ITEM_FORM' => ['required', 'string', Rule::in(Inventory::FORMS[$category] ?? [])],
            'ITEM_CATEGORY' => ['required', 'string', Rule::in(array_keys(Inventory::FORMS))],
            'ITEM_QUANTITY' => 'required|integer|min:0',
            'target_stock' => 'nullable|integer|min:1',
            'minimum_stock' => 'nullable|integer|min:0',
            'kit_complete' => 'nullable|boolean',
            'ITEM_EXPIRATION_DATE' => [$category === 'Medicine' ? 'required' : 'nullable', 'date'],
        ]);

        if (! $isMedicine) {
            $validated['ITEM_DOSAGE'] = null;
            $validated['ITEM_UNIT'] = null;
        }

        if (! $tracksExpiration) {
            $validated['ITEM_EXPIRATION_DATE'] = null;
        }

        if (! in_array($category, ['Medicine', 'Medical Supply'], true)) {
            $validated['target_stock'] = null;
        }

        if ($category === 'First Aid Kit') {
            $validated['kit_complete'] = $request->boolean('kit_complete');
        } else {
            $validated['kit_complete'] = null;
        }

        return $validated;
    }
}