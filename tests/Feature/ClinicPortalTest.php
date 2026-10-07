<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use App\Models\SubUserType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/inventory')->assertRedirect(route('login'));
        $this->get('/medical-records')->assertRedirect(route('login'));
        $this->get('/records')->assertRedirect(route('login'));
    }

    public function test_student_sees_a_personal_portal_and_cannot_open_staff_areas(): void
    {
        $user = $this->signIn('Student', 'ana@apc.edu.ph', 'Ana', 'Cruz', '2024100001');

        Appointment::create([
            'PATIENT_ID' => '2024100001',
            'PATIENT_NAME' => 'Ana Cruz',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Headache',
            'ATTENDING_PHYSICIAN' => 'Dr. Reyes',
            'SCHEDULED_AT' => now()->addDay(),
            'STATUS' => 'Scheduled',
        ]);
        Appointment::create([
            'PATIENT_ID' => '2024999999',
            'PATIENT_NAME' => 'Other Patient',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Private',
            'ATTENDING_PHYSICIAN' => 'Dr. Hidden',
            'SCHEDULED_AT' => now()->addDay(),
            'STATUS' => 'Scheduled',
        ]);
        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => now()->subDay()->toDateString(),
            'MEDREC_DIAGNOSIS' => 'Tension headache',
            'MEDREC_NOTES' => 'Rest and fluids',
            'MEDREC_MEDICINE_DOSAGE' => 'Paracetamol 500mg',
            'PATIENT_ID' => 2024100001,
            'APPT_ID' => 1,
        ]);

        $dashboard = $this->actingAs($user)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Welcome, Ana');
        $dashboard->assertSee('My Upcoming Appointments');
        $dashboard->assertSee('Total Clinic Visits');
        $dashboard->assertSee('My Scheduled Appointments');
        $dashboard->assertSee('Dr. Reyes');
        $dashboard->assertSee('Tension headache');
        $dashboard->assertSee('Rest and fluids');
        $dashboard->assertSee('Paracetamol 500mg');
        $dashboard->assertSee('Visit History');
        $dashboard->assertSee('Profile');
        $dashboard->assertSee('+ Schedule Check-Up');
        $dashboard->assertDontSee('APC Clinic Operations Dashboard');
        $dashboard->assertDontSee('Other Patient');
        $dashboard->assertDontSee('>Inventory<');
        $dashboard->assertDontSee('Medical Records');
        $dashboard->assertDontSee('Analytics');

        $this->actingAs($user)->get('/inventory')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/medical-records')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/records')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/analytics')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->post('/dashboard/next-patient')->assertRedirect(route('dashboard'));

        $appointments = $this->actingAs($user)->get('/appointments');
        $appointments->assertOk();
        $appointments->assertSee('Dr. Reyes');
        $appointments->assertDontSee('Other Patient');
        $appointments->assertDontSee('Publish Doctor Availability');

        $this->actingAs($user)->post('/appointments', [
            'PATIENT_ID' => '9999999999',
            'PATIENT_NAME' => 'Someone Else',
            'APPOINTMENT_TYPE' => 'Routine Checkup',
            'APPOINTMENT_REASON' => 'Annual check-up',
            'ATTENDING_PHYSICIAN' => 'Dr. Cruz',
            'SCHEDULED_AT' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect('/appointments');

        $this->assertDatabaseHas('appointments', [
            'PATIENT_ID' => '2024100001',
            'PATIENT_NAME' => 'Ana Cruz',
            'APPOINTMENT_REASON' => 'Annual check-up',
        ]);
        $this->assertDatabaseMissing('appointments', [
            'PATIENT_NAME' => 'Someone Else',
        ]);
    }

    public function test_clinic_staff_sees_the_operations_dashboard(): void
    {
        $user = $this->signIn('Medical Staff', 'mina@apc.edu.ph', 'Mina', 'Reyes', 'EMP-100', 'Nurse');

        Appointment::create([
            'PATIENT_ID' => '2024100001',
            'PATIENT_NAME' => 'Ana Cruz',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Fever',
            'ATTENDING_PHYSICIAN' => 'Dr. Reyes',
            'SCHEDULED_AT' => now()->addHour(),
            'STATUS' => 'Scheduled',
        ]);
        Appointment::create([
            'PATIENT_ID' => '2024100002',
            'PATIENT_NAME' => 'Luis Tan',
            'APPOINTMENT_TYPE' => 'Emergency Care',
            'APPOINTMENT_REASON' => 'Asthma',
            'ATTENDING_PHYSICIAN' => 'Dr. Reyes',
            'SCHEDULED_AT' => now()->addHours(3),
            'STATUS' => 'Scheduled',
        ]);
        Inventory::create([
            'ITEM_CODE' => 501,
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 3,
            'ITEM_EXPIRATION_DATE' => now()->addMonth()->toDateString(),
        ]);

        $dashboard = $this->actingAs($user)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('APC Clinic Operations Dashboard');
        $dashboard->assertSee('Appointments Today');
        $dashboard->assertSee('Total Patients Queued');
        $dashboard->assertSee('Upcoming Appointments');
        $dashboard->assertSee('Ana Cruz');
        $dashboard->assertSee('Paracetamol');
        $dashboard->assertSee('Next Patient');
        $dashboard->assertSee('Toggle Treatment-First Mode');
        $dashboard->assertSee('Notify Logistics');
        $dashboard->assertSee('Inventory');
        $dashboard->assertSee('Medical Records');
        $dashboard->assertSee('Analytics');
        $dashboard->assertDontSee('Visit History');
        $dashboard->assertDontSee('Welcome, Mina');

        $this->actingAs($user)->get('/inventory')->assertOk();
        $this->actingAs($user)->get('/medical-records')->assertOk();
        $this->actingAs($user)->get('/records')->assertOk();
        $this->actingAs($user)->get('/analytics')->assertOk();

        $this->actingAs($user)
            ->withSession(['clinic.treatment_first' => true])
            ->post(route('dashboard.next-patient'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('In Consultation', Appointment::where('PATIENT_NAME', 'Luis Tan')->first()->STATUS);
        $this->assertSame('Scheduled', Appointment::where('PATIENT_NAME', 'Ana Cruz')->first()->STATUS);
    }

    public function test_school_employees_stay_on_the_personal_portal(): void
    {
        $user = $this->signIn('Employee', 'juan@apc.edu.ph', 'Juan', 'Dela Cruz', 'EMP-200');

        $this->actingAs($user)->get('/dashboard')->assertSee('Welcome, Juan')->assertDontSee('APC Clinic Operations Dashboard');
        $this->actingAs($user)->get('/inventory')->assertRedirect(route('dashboard'));
    }

    public function test_an_admin_user_type_is_treated_as_staff(): void
    {
        UserType::create(['Name' => 'Admin']);
        $user = $this->signIn('Admin', 'admin@apc.edu.ph', 'Ava', 'Santos', 'ADM-1');

        $dashboard = $this->actingAs($user)->get('/dashboard');
        $dashboard->assertSee('APC Clinic Operations Dashboard');
        $dashboard->assertSee('All inventory stock levels are normal.');
        $this->actingAs($user)->get('/inventory')->assertOk();
    }

    public function test_ramsey_does_not_send_students_to_inventory(): void
    {
        $user = $this->signIn('Student', 'ana2@apc.edu.ph', 'Ana', 'Cruz', '2024100002');

        $response = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Where is the inventory?',
        ]);

        $response->assertOk();
        $this->assertStringNotContainsString('/inventory', $response->getContent());
        $this->assertStringNotContainsString('/medical-records', $response->getContent());
    }

    private function signIn(string $typeName, string $email, string $first, string $last, ?string $number = null, ?string $subName = null): User
    {
        $type = UserType::where('Name', $typeName)->firstOrFail();
        $account = AppUser::create([
            'Student_Employee_No' => $number,
            'FirstName' => $first,
            'LastName' => $last,
            'EmailAddress' => $email,
            'UserTypeId' => $type->Id,
        ]);

        if ($subName) {
            $sub = SubUserType::where('Name', $subName)->firstOrFail();
            AppUserMedicalStaff::create([
                'AppUserId' => $account->Id,
                'SubUserTypeId' => $sub->Id,
            ]);
        }

        return User::factory()->create([
            'name' => $first.' '.$last,
            'email' => $email,
        ]);
    }
}
