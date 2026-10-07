<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->string('ITEM_FORM', 30)->nullable()->after('ITEM_UNIT');
        });

        DB::table('inventory')
            ->whereIn('ITEM_UNIT', ['Pill', 'Syrup', 'Capsule', 'Drops', 'Cream', 'Injection'])
            ->orderBy('ITEM_CODE')
            ->get()
            ->each(function ($item) {
                DB::table('inventory')->where('ITEM_CODE', $item->ITEM_CODE)->update([
                    'ITEM_FORM' => $item->ITEM_UNIT === 'Pill' ? 'Capsule' : $item->ITEM_UNIT,
                    'ITEM_UNIT' => null,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn('ITEM_FORM');
        });
    }
};
