<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $supplies = Inventory::all();
        return view('inventory.index', compact('supplies'));
    }

    // Handle the submission of a new item form
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ITEM_CODE'            => 'required|integer|unique:inventory,ITEM_CODE',
            'GENERIC_NAME'         => 'required|string|max:255',
            'BRAND_NAME'           => 'nullable|string|max:255',
            'ITEM_CATEGORY'        => 'required|string',
            'ITEM_QUANTITY'        => 'required|integer|min:0',
            'ITEM_EXPIRATION_DATE' => 'required|date',
        ]);

        Inventory::create($validated);

        return redirect('/inventory')->with('success', 'Item created successfully!');
    }

    public function update(Request $request, $inventory)
    {
        $item = Inventory::where('ITEM_CODE', $inventory)->firstOrFail();

        $validated = $request->validate([
            'GENERIC_NAME'         => 'required|string|max:255',
            'BRAND_NAME'           => 'nullable|string|max:255',
            'ITEM_CATEGORY'        => 'required|string',
            'ITEM_QUANTITY'        => 'required|integer|min:0',
            'ITEM_EXPIRATION_DATE' => 'required|date',
        ]);

        $item->update($validated);

        return response()->json(['message' => 'Item updated successfully!']);
    }
}