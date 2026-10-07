<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use App\Models\SubUserType;
use App\Models\User;
use App\Models\UserType;
use App\Support\ClinicAccess;
use Illuminate\Support\Facades\Auth;

class PreviewController extends Controller
{
    public function enter(string $role, ClinicAccess $access)
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless(in_array($role, ['student', 'staff'], true), 404);

        $account = $this->account($role);
        $this->sampleRecords();

        $user = User::firstOrCreate(
            ['email' => $account->EmailAddress],
            ['name' => $account->fullName(), 'password' => 'preview']
        );
        $user->forceFill(['name' => $account->fullName()])->save();

        Auth::login($user);
        $access->remember($account);

        return redirect()->route('dashboard');
    }

    private function account(string $role): AppUser
    {
        $isStaff = $role === 'staff';
        $type = UserType::query()->where('Name', $isStaff ? 'Medical Staff' : 'Student')->firstOrFail();

        $account = AppUser::updateOrCreate(
            ['EmailAddress' => $isStaff ? 'preview.staff@apc.edu.ph' : 'preview.student@apc.edu.ph'],
            [
                'Student_Employee_No' => $isStaff ? 'EMP-900' : '2024100001',
                'FirstName' => $isStaff ? 'Mina' : 'Ana',
                'LastName' => 'Reyes',
                'UserTypeId' => $type->Id,
            ]
        );

        if ($isStaff) {
            $sub = SubUserType::query()->where('Name', 'Nurse')->firstOrFail();
            AppUserMedicalStaff::updateOrCreate(
                ['AppUserId' => $account->Id],
                ['SubUserTypeId' => $sub->Id]
            );
        }

        return $account->load(['userType', 'medicalStaff.subUserType']);
    }

    private function sampleRecords(): void
    {
        Appointment::updateOrCreate(
            ['PATIENT_ID' => '2024100001', 'APPOINTMENT_REASON' => 'Headache follow-up'],
            [
                'PATIENT_NAME' => 'Ana Reyes',
                'APPOINTMENT_TYPE' => 'Consultation',
                'ATTENDING_PHYSICIAN' => 'Dr. Cruz',
                'SCHEDULED_AT' => now()->addDay()->setTime(9, 30),
                'STATUS' => 'Scheduled',
            ]
        );

        Appointment::updateOrCreate(
            ['PATIENT_ID' => '2024100099', 'APPOINTMENT_REASON' => 'Fever'],
            [
                'PATIENT_NAME' => 'Luis Tan',
                'APPOINTMENT_TYPE' => 'Consultation',
                'ATTENDING_PHYSICIAN' => 'Nurse Mina',
                'SCHEDULED_AT' => now()->addHour(),
                'STATUS' => 'Scheduled',
            ]
        );

        MedicalRecord::updateOrCreate(
            ['PATIENT_ID' => 2024100001, 'MEDREC_DIAGNOSIS' => 'Tension headache'],
            [
                'MEDREC_CONSUL_DATE' => now()->subDays(3)->toDateString(),
                'MEDREC_NOTES' => 'Rest and fluids',
                'MEDREC_MEDICINE_DOSAGE' => 'Paracetamol 500mg',
                'APPT_ID' => 1,
            ]
        );

        Inventory::updateOrCreate(
            ['ITEM_CODE' => 99001],
            [
                'GENERIC_NAME' => 'Paracetamol',
                'BRAND_NAME' => 'Biogesic',
                'ITEM_CATEGORY' => 'Medicine',
                'ITEM_DOSAGE' => '500mg',
                'ITEM_QUANTITY' => 4,
                'ITEM_EXPIRATION_DATE' => now()->addMonths(6)->toDateString(),
            ]
        );
    }
}
