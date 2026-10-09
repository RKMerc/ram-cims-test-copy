<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\LowStockReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index()
    {
        $supplies = Inventory::query()->orderBy('ITEM_CODE')->get();
        $nextItemCode = Inventory::nextItemCode();
        $lowStockItems = Inventory::lowStockItems();
        $lowStockReport = LowStockReport::query()->latest()->first();

        return view('inventory.index', compact('supplies', 'nextItemCode', 'lowStockItems', 'lowStockReport'));
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

    public function store(Request $request)
    {
        $validated = $this->validatedItem($request);
        $validated['ITEM_CODE'] = Inventory::nextItemCode();
        Inventory::create($validated);

        return redirect('/inventory')->with('success', 'Item '.$validated['ITEM_CODE'].' added to inventory.');
    }

    public function update(Request $request, $inventory)
    {
        $item = Inventory::where('ITEM_CODE', $inventory)->firstOrFail();
        $item->update($this->validatedItem($request));

        return response()->json(['message' => 'Item updated successfully!']);
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
            'ITEM_EXPIRATION_DATE' => [$category === 'Medicine' ? 'required' : 'nullable', 'date'],
        ]);

        if (! $isMedicine) {
            $validated['ITEM_DOSAGE'] = null;
            $validated['ITEM_UNIT'] = null;
        }

        if (! $tracksExpiration) {
            $validated['ITEM_EXPIRATION_DATE'] = null;
        }

        return $validated;
    }
}