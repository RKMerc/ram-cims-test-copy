<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('UserType', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Name')->unique();
            $table->timestamps();
        });

        Schema::create('SubUserType', function (Blueprint $table) {
            $table->id('Id');
            $table->foreignId('UserTypeId')->constrained('UserType', 'Id');
            $table->string('Name');
            $table->timestamps();
        });

        Schema::create('AppUser', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Student_Employee_No', 20)->nullable()->unique();
            $table->string('FirstName');
            $table->string('LastName');
            $table->string('MiddleName')->nullable();
            $table->string('EmailAddress')->unique();
            $table->string('ContactNo', 30)->nullable();
            $table->foreignId('UserTypeId')->constrained('UserType', 'Id');
            $table->timestamps();
        });

        Schema::create('AppUser_MedicalStaff', function (Blueprint $table) {
            $table->id('Id');
            $table->foreignId('AppUserId')->constrained('AppUser', 'Id')->cascadeOnDelete();
            $table->foreignId('SubUserTypeId')->nullable()->constrained('SubUserType', 'Id');
            $table->string('LicenseNo')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('patient_name')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('UserType')->insert([
            ['Id' => 1, 'Name' => 'Student', 'created_at' => $now, 'updated_at' => $now],
            ['Id' => 2, 'Name' => 'Employee', 'created_at' => $now, 'updated_at' => $now],
            ['Id' => 3, 'Name' => 'Medical Staff', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('SubUserType')->insert([
            ['UserTypeId' => 3, 'Name' => 'Physician', 'created_at' => $now, 'updated_at' => $now],
            ['UserTypeId' => 3, 'Name' => 'Nurse', 'created_at' => $now, 'updated_at' => $now],
            ['UserTypeId' => 3, 'Name' => 'Dentist', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_reminders');
        Schema::dropIfExists('AppUser_MedicalStaff');
        Schema::dropIfExists('AppUser');
        Schema::dropIfExists('SubUserType');
        Schema::dropIfExists('UserType');
    }
};
