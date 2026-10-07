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

    public function test_new_records_increment_and_existing_ids_stay_put(): void
    {
        $user = $this->signIn('Medical Staff', 'mina.codes@apc.edu.ph', 'Mina', 'Reyes', 'EMP-300', 'Nurse');

        Inventory::create([
            'ITEM_CODE' => 1001,
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_DOSAGE' => '500mg',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 4,
            'ITEM_EXPIRATION_DATE' => now()->addYear()->toDateString(),
        ]);

        $this->actingAs($user)->post('/inventory', [
            'GENERIC_NAME' => 'Ibuprofen',
            'BRAND_NAME' => 'Advil',
            'ITEM_DOSAGE' => '200mg',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 20,
            'ITEM_EXPIRATION_DATE' => now()->addYear()->toDateString(),
        ])->assertRedirect('/inventory');

        $this->assertDatabaseHas('inventory', [
            'ITEM_CODE' => 1002,
            'GENERIC_NAME' => 'Ibuprofen',
            'ITEM_DOSAGE' => '200mg',
        ]);

        $this->actingAs($user)->putJson('/inventory/1002', [
            'ITEM_CODE' => 9999,
            'GENERIC_NAME' => 'Ibuprofen',
            'BRAND_NAME' => 'Advil',
            'ITEM_DOSAGE' => '400mg',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 40,
            'ITEM_EXPIRATION_DATE' => now()->addYear()->toDateString(),
        ])->assertOk();

        $this->assertDatabaseHas('inventory', [
            'ITEM_CODE' => 1002,
            'ITEM_QUANTITY' => 40,
            'ITEM_DOSAGE' => '400mg',
        ]);
        $this->assertDatabaseMissing('inventory', ['ITEM_CODE' => 9999]);

        $appointment = Appointment::create([
            'PATIENT_ID' => '2024100001',
            'PATIENT_NAME' => 'Ana Cruz',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Headache',
            'ATTENDING_PHYSICIAN' => 'Dr. Cruz',
            'SCHEDULED_AT' => now()->addDay(),
            'STATUS' => 'Scheduled',
        ]);

        $this->actingAs($user)->putJson('/appointments/'.$appointment->APPOINTMENT_ID, [
            'APPOINTMENT_ID' => 99999,
            'PATIENT_ID' => '2024100888',
            'PATIENT_NAME' => 'Ana Cruz',
            'APPOINTMENT_TYPE' => 'Routine Checkup',
            'APPOINTMENT_REASON' => 'Updated reason',
            'ATTENDING_PHYSICIAN' => 'Mina Reyes',
            'SCHEDULED_AT' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'STATUS' => 'In Consultation',
        ])->assertOk();

        $updated = Appointment::find($appointment->APPOINTMENT_ID);
        $this->assertNotNull($updated);
        $this->assertSame('2024100888', (string) $updated->PATIENT_ID);
        $this->assertSame('Routine Checkup', $updated->APPOINTMENT_TYPE);
        $this->assertSame('Updated reason', $updated->APPOINTMENT_REASON);
        $this->assertSame('Mina Reyes', $updated->ATTENDING_PHYSICIAN);
        $this->assertSame('In Consultation', $updated->STATUS);
        $this->assertNull(Appointment::find(99999));

        $page = $this->actingAs($user)->get('/appointments');
        $page->assertOk();
        $page->assertSee('Select attending physician');
        $page->assertSee('Mina Reyes');
        $page->assertSee('Select Doctor / Attending Physician');
        $page->assertSee('Dr. Marciano Fidel L. Avendaño - Doctor');
        $page->assertSee('value="'.Appointment::nextPatientId().'"', false);
    }

    public function test_weekly_duty_grid_follows_appointments_and_duty_days(): void
    {
        $user = $this->signIn('Medical Staff', 'mina.duty@apc.edu.ph', 'Mina', 'Reyes', 'EMP-301', 'Nurse');
        $name = 'Dr. Marciano Fidel L. Avendaño';
        $return = '/appointments?practitioner='.urlencode($name).'&week=2026-10-08';

        $page = $this->actingAs($user)->get($return);
        $page->assertOk();
        $page->assertSee('Doctor Duty Schedule & Availability');
        $page->assertSee('Select Doctor / Attending Physician');
        $page->assertSee('TIMESLOT');
        $page->assertSee('Monday');
        $page->assertSee('Friday');
        $page->assertSee('8:00 AM - 9:00 AM');
        $page->assertSee('4:00 PM - 5:00 PM');
        $page->assertSee('AVAILABLE');
        $page->assertSee('NOT AVAILABLE');
        $page->assertDontSee('DEV MODE');
        $page->assertDontSee('Developer Mode');
        $page->assertDontSee('Toggle Status');

        $this->actingAs($user)->post('/duty-slots/book', [
            'practitioner' => $name,
            'duty_date' => '2026-10-08',
            'start' => '14:00:00',
            'patient_name' => 'Test Patient',
            'patient_id' => '2024101234',
            'reason' => 'Slot booking',
            'return_to' => $return,
        ])->assertRedirect($return);

        $this->assertDatabaseHas('appointments', [
            'PATIENT_NAME' => 'Test Patient',
            'ATTENDING_PHYSICIAN' => $name,
            'PATIENT_ID' => '2024101234',
        ]);

        $booked = $this->actingAs($user)->get($return);
        $booked->assertSee('OCCUPIED - Test Patient');

        Appointment::query()->where('PATIENT_NAME', 'Test Patient')->update(['STATUS' => 'Cancelled']);

        $this->actingAs($user)->get($return)->assertDontSee('OCCUPIED - Test Patient');
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
