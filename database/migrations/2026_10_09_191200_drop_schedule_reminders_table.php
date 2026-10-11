<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('schedule_reminders');
    }

    public function down(): void
    {
        Schema::create('schedule_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('patient_name')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }
};
