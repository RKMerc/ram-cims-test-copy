<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('medical_record', function (Blueprint $table) {
            $table->id('MEDREC_ID');
            $table->date('MEDREC_CONSUL_DATE');
            $table->string('MEDREC_DIAGNOSIS', 250);
            $table->string('MEDREC_NOTES', 45)->nullable();
            $table->string('MEDREC_MEDICINE_DOSAGE', 100)->nullable();
            $table->unsignedBigInteger('PATIENT_ID');
            $table->unsignedBigInteger('APPT_ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
