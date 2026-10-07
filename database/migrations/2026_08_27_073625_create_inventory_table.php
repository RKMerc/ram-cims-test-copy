<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->integer('ITEM_CODE')->unique();
            $table->string('GENERIC_NAME');
            $table->string('BRAND_NAME')->nullable();
            $table->string('ITEM_CATEGORY');
            $table->integer('ITEM_QUANTITY')->default(0);
            $table->date('ITEM_EXPIRATION_DATE')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};