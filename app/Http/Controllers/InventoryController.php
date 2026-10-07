<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $supplies = Inventory::query()->orderBy('ITEM_CODE')->get();
        $nextItemCode = Inventory::nextItemCode();

        return view('inventory.index', compact('supplies', 'nextItemCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'GENERIC_NAME'         => 'required|string|max:255',
            'BRAND_NAME'           => 'nullable|string|max:255',
            'ITEM_DOSAGE'          => 'nullable|string|max:100',
            'ITEM_UNIT'            => 'nullable|string|in:Pill,Syrup,Capsule,Drops,Cream,Injection',
            'ITEM_CATEGORY'        => 'required|string',
            'ITEM_QUANTITY'        => 'required|integer|min:0',
            'ITEM_EXPIRATION_DATE' => 'required|date',
        ]);

        $validated['ITEM_CODE'] = Inventory::nextItemCode();
        Inventory::create($validated);

        return redirect('/inventory')->with('success', 'Item '.$validated['ITEM_CODE'].' added to inventory.');
    }

    public function update(Request $request, $inventory)
    {
        $item = Inventory::where('ITEM_CODE', $inventory)->firstOrFail();

        $validated = $request->validate([
            'GENERIC_NAME'         => 'required|string|max:255',
            'BRAND_NAME'           => 'nullable|string|max:255',
            'ITEM_DOSAGE'          => 'nullable|string|max:100',
            'ITEM_UNIT'            => 'nullable|string|in:Pill,Syrup,Capsule,Drops,Cream,Injection',
            'ITEM_CATEGORY'        => 'required|string',
            'ITEM_QUANTITY'        => 'required|integer|min:0',
            'ITEM_EXPIRATION_DATE' => 'required|date',
        ]);

        $item->update($validated);

        return response()->json(['message' => 'Item updated successfully!']);
    }
}