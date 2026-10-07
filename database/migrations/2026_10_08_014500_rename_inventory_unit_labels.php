<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory')->where('ITEM_UNIT', 'Milligrams')->update(['ITEM_UNIT' => 'Milligram (mg)']);
        DB::table('inventory')->where('ITEM_UNIT', 'ml')->update(['ITEM_UNIT' => 'Milliliter (mL)']);
    }

    public function down(): void
    {
        DB::table('inventory')->where('ITEM_UNIT', 'Milligram (mg)')->update(['ITEM_UNIT' => 'Milligrams']);
        DB::table('inventory')->where('ITEM_UNIT', 'Milliliter (mL)')->update(['ITEM_UNIT' => 'ml']);
    }
};
