<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_code')->index();
            $table->string('batch_code')->nullable();
            $table->integer('quantity')->default(0);
            $table->date('expiration_date')->nullable();
            $table->date('received_on')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->timestamps();
        });

        if (! Schema::hasTable('inventory')) {
            return;
        }

        $now = now();

        foreach (DB::table('inventory')->orderBy('ITEM_CODE')->get() as $item) {
            $quantity = (int) $item->ITEM_QUANTITY;
            $expiration = $item->ITEM_EXPIRATION_DATE ?? null;

            if ($quantity === 0 && $expiration === null) {
                continue;
            }

            DB::table('inventory_batches')->insert([
                'item_code' => $item->ITEM_CODE,
                'batch_code' => null,
                'quantity' => $quantity,
                'expiration_date' => $expiration,
                'received_on' => null,
                'receipt_reference' => null,
                'needs_review' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
