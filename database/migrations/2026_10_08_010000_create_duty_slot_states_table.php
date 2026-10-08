<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_slot_states', function (Blueprint $table) {
            $table->id();
            $table->string('PRACTITIONER_NAME');
            $table->date('SLOT_DATE');
            $table->string('START_TIME', 8);
            $table->string('STATUS', 20);
            $table->timestamps();

            $table->unique(['PRACTITIONER_NAME', 'SLOT_DATE', 'START_TIME'], 'duty_slot_states_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_slot_states');
    }
};
