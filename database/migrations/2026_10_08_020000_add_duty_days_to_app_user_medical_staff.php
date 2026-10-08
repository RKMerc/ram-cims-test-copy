<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('AppUser_MedicalStaff', function (Blueprint $table) {
            $table->boolean('IsMonday')->default(false);
            $table->boolean('IsTuesday')->default(false);
            $table->boolean('IsWednesday')->default(false);
            $table->boolean('IsThursday')->default(false);
            $table->boolean('IsFriday')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('AppUser_MedicalStaff', function (Blueprint $table) {
            $table->dropColumn(['IsMonday', 'IsTuesday', 'IsWednesday', 'IsThursday', 'IsFriday']);
        });
    }
};
