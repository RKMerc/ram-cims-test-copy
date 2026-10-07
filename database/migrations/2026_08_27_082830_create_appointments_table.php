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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('APPOINTMENT_ID');
            $table->string('PATIENT_ID');
            $table->string('PATIENT_NAME');
            $table->string('APPOINTMENT_TYPE');
            $table->text('APPOINTMENT_REASON')->nullable();
            $table->string('ATTENDING_PHYSICIAN');
            $table->dateTime('SCHEDULED_AT');
            $table->string('STATUS')->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};