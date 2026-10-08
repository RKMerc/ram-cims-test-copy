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
        $dashboard->assertSee('RAMsey Student Health Assistant');
        $dashboard->assertSee('Your automated assistant for clinic appointment reminders, checking personal visit history, and navigating student health services. RAMsey cannot provide medical diagnoses or prescribe medication.');
        $dashboard->assertSee('Schedule Check-Up');
        $dashboard->assertSee('View My Latest Visit Summary');
        $dashboard->assertSee('View My Active Prescriptions');
        $dashboard->assertDontSee('RAMsey Clinic Operations Assistant');
        $dashboard->assertDontSee('Dev preview');
        $dashboard->assertDontSee('APC Clinic Operations Dashboard');
        $dashboard->assertDontSee('Other Patient');
        $dashboard->assertDontSee('>Inventory<');
        $dashboard->assertDontSee('Medical Records');
        $dashboard->assertDontSee('Analytics');

        $this->actingAs($user)->get('/inventory')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/medical-records')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/records')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/analytics')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/analytics/report')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/analytics/report/download')->assertRedirect(route('dashboard'));
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
        $dashboard->assertSee('RAMsey Clinic Operations Assistant');
        $dashboard->assertSee('Your operational copilot for clinic workflow shortcuts, low-stock inventory alerts, queue summary updates, and report generation assistance.');
        $dashboard->assertSee('Show items below minimum threshold');
        $dashboard->assertSee('Generate Clinic Visit Report');
        $dashboard->assertDontSee('RAMsey Student Health Assistant');
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

        $this->actingAs($user)->get('/dashboard')->assertSee('Welcome, Juan')->assertSee('RAMsey Student Health Assistant')->assertDontSee('APC Clinic Operations Dashboard');
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
        $response->assertJsonPath('reply', 'Sorry, this action is restricted to APC Clinic Staff.');
        $this->assertStringNotContainsString('/inventory', $response->getContent());
        $this->assertStringNotContainsString('/medical-records', $response->getContent());
    }

    public function test_ramsey_student_can_read_personal_records_and_not_clinic_operations(): void
    {
        $user = $this->signIn('Student', 'ana.ramsey@apc.edu.ph', 'Ana', 'Cruz', '2024100091');
        AppUser::where('EmailAddress', 'ana.ramsey@apc.edu.ph')->update(['ContactNo' => '09171234567']);

        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => '2026-10-01',
            'MEDREC_DIAGNOSIS' => 'Tension headache',
            'MEDREC_NOTES' => 'Rest and fluids',
            'MEDREC_MEDICINE_DOSAGE' => 'Paracetamol 500mg',
            'PATIENT_ID' => 2024100091,
            'APPT_ID' => 1,
        ]);
        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => '2026-10-02',
            'MEDREC_DIAGNOSIS' => 'Private sprain',
            'MEDREC_NOTES' => 'Other chart',
            'MEDREC_MEDICINE_DOSAGE' => 'Hidden medicine',
            'PATIENT_ID' => 2024999999,
            'APPT_ID' => 2,
        ]);
        Appointment::create([
            'PATIENT_ID' => '2024100091',
            'PATIENT_NAME' => 'Ana Cruz',
            'APPOINTMENT_TYPE' => 'Check-up',
            'APPOINTMENT_REASON' => 'Follow up',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => now()->addDay(),
            'STATUS' => 'Scheduled',
        ]);

        $visit = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'View My Latest Visit Summary',
        ]);
        $visit->assertOk();
        $visit->assertSee('Tension headache');
        $visit->assertSee('Paracetamol 500mg');
        $visit->assertDontSee('Private sprain');
        $visit->assertDontSee('Hidden medicine');
        $this->assertStringContainsString('/visit-history', $visit->getContent());

        $rx = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'View My Active Prescriptions',
        ]);
        $rx->assertOk();
        $rx->assertSee('Paracetamol 500mg');
        $rx->assertDontSee('Hidden medicine');

        $contact = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'View My Vitals / Contact Info',
        ]);
        $contact->assertOk();
        $contact->assertSee('09171234567');
        $contact->assertSee('No vital signs are stored');

        $book = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Schedule Check-Up',
        ]);
        $book->assertOk();
        $this->assertStringContainsString('Dr. Marciano Fidel L. Avendaño', $book->json('reply'));
        $this->assertStringContainsString('/appointments', $book->getContent());
        $this->assertStringNotContainsString('/inventory', $book->getContent());

        foreach (['Show the analytics report', 'List other patients', 'Please prescribe medicine'] as $prompt) {
            $blocked = $this->actingAs($user)->postJson('/ramsey/ask', ['message' => $prompt]);
            $blocked->assertOk();
            $blocked->assertJsonPath('reply', 'Sorry, this action is restricted to APC Clinic Staff.');
            $this->assertStringNotContainsString('/analytics', $blocked->getContent());
            $this->assertStringNotContainsString('/inventory', $blocked->getContent());
        }
    }

    public function test_ramsey_staff_runs_operations_and_refuses_clinical_decisions(): void
    {
        $user = $this->signIn('Medical Staff', 'mina.ramsey@apc.edu.ph', 'Mina', 'Reyes', 'EMP-910', 'Nurse');

        Appointment::create([
            'PATIENT_ID' => '2024100108',
            'PATIENT_NAME' => 'Carlo Santos',
            'APPOINTMENT_TYPE' => 'Check-up',
            'APPOINTMENT_REASON' => 'Fever',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => now(),
            'STATUS' => 'Scheduled',
        ]);
        Appointment::create([
            'PATIENT_ID' => '2024100109',
            'PATIENT_NAME' => 'Duty Patient',
            'APPOINTMENT_TYPE' => 'Check-up',
            'APPOINTMENT_REASON' => 'Slot',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => '2026-10-08 14:00:00',
            'STATUS' => 'Scheduled',
        ]);
        Inventory::create([
            'ITEM_CODE' => 88001,
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 3,
            'ITEM_EXPIRATION_DATE' => now()->addMonth()->toDateString(),
        ]);
        Inventory::create([
            'ITEM_CODE' => 88002,
            'GENERIC_NAME' => 'Vitamin C',
            'BRAND_NAME' => 'Poten Cee',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 40,
            'ITEM_EXPIRATION_DATE' => now()->addMonth()->toDateString(),
        ]);

        $stock = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Show items below minimum threshold',
        ]);
        $stock->assertOk();
        $stock->assertSee('Paracetamol');
        $stock->assertSee('3 left');
        $stock->assertDontSee('Vitamin C');
        $this->assertStringContainsString('/inventory', $stock->getContent());

        $queue = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Daily Queue Summary',
        ]);
        $queue->assertOk();
        $queue->assertSee('Carlo Santos');
        $queue->assertSee('waiting in today');

        $duty = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Doctor Duty Schedule Quick-Check',
            'physician' => 'Dr. Marciano Fidel L. Avendaño',
            'date' => '2026-10-08',
        ]);
        $duty->assertOk();
        $duty->assertSee('AVAILABLE');
        $duty->assertSee('OCCUPIED - Duty Patient');

        $mode = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Treatment-First Mode (TFM)',
        ]);
        $mode->assertOk();
        $mode->assertSee('Treatment-first mode is now on');
        $this->assertTrue((bool) session('clinic.treatment_first'));

        $report = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Generate Clinic Visit Report',
        ]);
        $report->assertOk();
        $report->assertSee('Clinic visit report');
        $day = today()->toDateString();
        $this->assertSame('/analytics/report?from='.$day.'&to='.$day, $report->json('links.0.href'));
        $this->assertSame('/analytics/report/download?from='.$day.'&to='.$day, $report->json('links.1.href'));

        $clinical = $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Please diagnose this patient and prescribe medication',
        ]);
        $clinical->assertOk();
        $clinical->assertJsonPath('reply', 'RAMsey is an operational assistant and cannot make clinical decisions, diagnose patients, or issue prescriptions. Please perform clinical entries manually.');
    }

    public function test_student_slot_alert_emails_when_a_duty_hour_is_open(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $user = $this->signIn('Student', 'ana.alert@apc.edu.ph', 'Ana', 'Cruz', '2024100092');

        $closed = $this->actingAs($user)->postJson('/ramsey/remind', [
            'email' => 'ana.alert@apc.edu.ph',
            'physician' => 'Dr. Marciano Fidel L. Avendaño',
            'date' => '2026-10-05',
        ]);
        $closed->assertOk();
        $closed->assertSee('Your alert is saved');
        \Illuminate\Support\Facades\Mail::assertNothingSent();
        $this->assertDatabaseHas('schedule_reminders', [
            'email' => 'ana.alert@apc.edu.ph',
            'note' => 'slot-watch|Dr. Marciano Fidel L. Avendaño|2026-10-05',
            'notified_at' => null,
        ]);

        $open = $this->actingAs($user)->postJson('/ramsey/remind', [
            'email' => 'ana.alert@apc.edu.ph',
            'physician' => 'Dr. Marciano Fidel L. Avendaño',
            'date' => '2026-10-08',
        ]);
        $open->assertOk();
        $open->assertSee('I emailed ana.alert@apc.edu.ph');
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SlotOpenAlert::class, function (\App\Mail\SlotOpenAlert $mail) {
            return $mail->hasTo('ana.alert@apc.edu.ph')
                && $mail->physician === 'Dr. Marciano Fidel L. Avendaño';
        });
    }

    public function test_cancelling_a_full_duty_day_emails_the_waiting_slot_alert(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $student = $this->signIn('Student', 'ana.wait@apc.edu.ph', 'Ana', 'Cruz', '2024100093');
        $staff = $this->signIn('Medical Staff', 'mina.wait@apc.edu.ph', 'Mina', 'Reyes', 'EMP-911', 'Nurse');
        $physician = 'Dr. Marciano Fidel L. Avendaño';

        foreach (['14:00:00', '15:00:00', '16:00:00'] as $start) {
            Appointment::create([
                'PATIENT_ID' => '2024100108',
                'PATIENT_NAME' => 'Booked Patient '.$start,
                'APPOINTMENT_TYPE' => 'Check-up',
                'APPOINTMENT_REASON' => 'Filled hour',
                'ATTENDING_PHYSICIAN' => $physician,
                'SCHEDULED_AT' => '2026-10-08 '.$start,
                'STATUS' => 'Scheduled',
            ]);
        }

        $this->actingAs($student)->postJson('/ramsey/remind', [
            'email' => 'ana.wait@apc.edu.ph',
            'physician' => $physician,
            'date' => '2026-10-08',
        ])->assertOk()->assertSee('Your alert is saved');
        \Illuminate\Support\Facades\Mail::assertNothingSent();

        $appointment = Appointment::query()->where('SCHEDULED_AT', '2026-10-08 14:00:00')->firstOrFail();
        $this->app->forgetInstance(\App\Support\ClinicAccess::class);
        $this->actingAs($staff)->post('/duty-slots/cancel', [
            'practitioner' => $physician,
            'duty_date' => '2026-10-08',
            'start' => '14:00:00',
            'appointment_id' => $appointment->APPOINTMENT_ID,
            'return_to' => '/appointments',
        ])->assertRedirect('/appointments');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SlotOpenAlert::class, function (\App\Mail\SlotOpenAlert $mail) {
            return $mail->hasTo('ana.wait@apc.edu.ph');
        });
    }

    public function test_developer_preview_can_switch_ramsey_views_only_when_local(): void
    {
        $user = $this->signIn('Student', 'ana.preview@apc.edu.ph', 'Ana', 'Cruz', '2024100094');

        $this->actingAs($user)->postJson('/ramsey/view', ['view' => 'staff'])->assertForbidden();

        $this->app['env'] = 'local';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $staffView = $this->actingAs($user)->postJson('/ramsey/view', ['view' => 'staff']);
        $staffView->assertOk();
        $staffView->assertJsonPath('staff', true);
        $staffView->assertJsonPath('title', 'RAMsey Clinic Operations Assistant');

        $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Where is the inventory?',
        ])->assertOk()->assertSee('Open Inventory Manager');

        $studentView = $this->actingAs($user)->postJson('/ramsey/view', ['view' => 'student']);
        $studentView->assertOk();
        $studentView->assertJsonPath('staff', false);
        $studentView->assertJsonPath('title', 'RAMsey Student Health Assistant');

        $this->actingAs($user)->postJson('/ramsey/ask', [
            'message' => 'Where is the inventory?',
        ])->assertJsonPath('reply', 'Sorry, this action is restricted to APC Clinic Staff.');
    }

    public function test_staff_can_view_and_download_the_analytics_pdf(): void
    {
        $user = $this->signIn('Medical Staff', 'mina.report@apc.edu.ph', 'Mina', 'Reyes', 'EMP-901', 'Nurse');

        Appointment::create([
            'PATIENT_ID' => '2024100101',
            'PATIENT_NAME' => 'Report Patient',
            'APPOINTMENT_TYPE' => 'Check-up',
            'APPOINTMENT_REASON' => 'Fever',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => '2026-10-08 14:00:00',
            'STATUS' => 'Scheduled',
        ]);

        $view = $this->actingAs($user)->get('/analytics/report?from=2026-10-08&to=2026-10-08&physician='.urlencode('Dr. Marciano Fidel L. Avendaño'));
        $view->assertOk();
        $view->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $view->getContent());

        $download = $this->actingAs($user)->get('/analytics/report/download?from=2026-10-08&to=2026-10-08');
        $download->assertOk();
        $download->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));
        $this->assertStringContainsString('APC-Clinic-Analytics-Report-2026-10-08.pdf', (string) $download->headers->get('content-disposition'));
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
