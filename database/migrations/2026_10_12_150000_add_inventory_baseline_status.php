<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->string('baseline_status', 20)->nullable()->after('target_stock');
            $table->boolean('needs_baseline_review')->default(false)->after('baseline_status');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn(['baseline_status', 'needs_baseline_review']);
        });
    }
};
