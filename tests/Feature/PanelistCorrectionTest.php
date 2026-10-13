<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\DutySlotState;
use App\Models\MedicalRecord;
use App\Models\SubUserType;
use App\Models\User;
use App\Models\UserType;
use App\Support\ClinicCalendar;
use App\Support\DutyBoard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelistCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_cancel_an_eligible_appointment_and_release_the_slot(): void
    {
        $student = $this->signIn('Student', 'ana.cancel@student.apc.edu.ph', 'Ana', 'Cancel', '2024103333');
        $physician = 'Dr. Marciano Fidel L. Avendaño';
        $day = now()->next(Carbon::THURSDAY)->toDateString();
        $scheduled = $day.' 14:00:00';

        $appointment = Appointment::create([
            'PATIENT_ID' => '2024103333',
            'PATIENT_NAME' => 'Ana Cancel',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Check-up',
            'ATTENDING_PHYSICIAN' => $physician,
            'SCHEDULED_AT' => $scheduled,
            'STATUS' => 'Scheduled',
        ]);

        $page = $this->actingAs($student)->get('/appointments');
        $page->assertOk();
        $page->assertSee(ClinicCalendar::parse($scheduled)->format('Y-m-d h:i A'));
        $page->assertDontSee(Carbon::parse($scheduled)->timezone(ClinicCalendar::timezone())->format('Y-m-d h:i A'));
        $page->assertSee('data-bs-target="#cancelAppointmentModal"', false);
        $page->assertSee('Are you sure you want to cancel this appointment? Your reserved time slot may become available for another patient.');
        $page->assertSee('Keep Appointment');
        $page->assertSee('Confirm Cancellation');
        $this->assertSame('occupied', $this->dutyCell($physician, $day, '14:00:00')['kind']);
        $this->assertFalse($this->slotIsOpen($student, $physician, $day, '14:00:00'));

        $this->actingAs($student)
            ->post(route('appointments.cancel', $appointment))
            ->assertRedirect('/appointments')
            ->assertSessionHas('success', 'Your appointment has been cancelled successfully.');

        $appointment->refresh();
        $this->assertSame('Cancelled', $appointment->STATUS);
        $this->assertNotNull(Appointment::query()->find($appointment->APPOINTMENT_ID));
        $this->assertSame(0, DutySlotState::query()->count());
        $this->assertSame('available', $this->dutyCell($physician, $day, '14:00:00')['kind']);
        $this->assertTrue($this->slotIsOpen($student, $physician, $day, '14:00:00'));

        $this->actingAs($student)->post('/appointments', [
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Rebooked visit',
            'ATTENDING_PHYSICIAN' => $physician,
            'SCHEDULED_AT' => $scheduled,
        ])->assertRedirect('/appointments');

        $this->assertSame(1, Appointment::query()->where('STATUS', 'Cancelled')->count());
        $this->assertSame(1, Appointment::query()->where('STATUS', 'Scheduled')->where('SCHEDULED_AT', $scheduled)->count());
        $this->assertFalse($this->slotIsOpen($student, $physician, $day, '14:00:00'));
    }

    public function test_students_cannot_cancel_ineligible_or_someone_elses_appointment(): void
    {
        $student = $this->signIn('Student', 'ana.block@student.apc.edu.ph', 'Ana', 'Block', '2024104444');
        $other = $this->signIn('Student', 'juan.block@student.apc.edu.ph', 'Juan', 'Block', '2024105555');
        $nurse = $this->signIn('Medical Staff', 'mina.block@apc.edu.ph', 'Mina', 'Block', 'EMP-880', 'Nurse');
        $physician = 'Dr. Marciano Fidel L. Avendaño';
        $day = now()->next(Carbon::THURSDAY)->toDateString();

        $owned = $this->visit('2024104444', 'Ana Block', $physician, $day.' 15:00:00', 'In Consultation');
        $completed = $this->visit('2024104444', 'Ana Block', $physician, $day.' 16:00:00', 'Completed');
        $cancelled = $this->visit('2024104444', 'Ana Block', $physician, $day.' 14:00:00', 'Cancelled');
        $past = $this->visit('2024104444', 'Ana Block', $physician, now()->subDay()->format('Y-m-d H:i:s'), 'Pending');
        $foreign = $this->visit('2024105555', 'Juan Block', $physician, $day.' 14:00:00', 'Scheduled');

        $page = $this->actingAs($student)->get('/appointments');
        $page->assertOk();
        $page->assertDontSee('data-bs-target="#cancelAppointmentModal"', false);

        foreach ([$owned, $completed, $cancelled, $past] as $appointment) {
            $this->actingAs($student)
                ->post(route('appointments.cancel', $appointment))
                ->assertRedirect('/appointments')
                ->assertSessionHas('error', 'This appointment can no longer be cancelled.');
            $this->assertSame($appointment->STATUS, $appointment->fresh()->STATUS);
        }

        $this->actingAs($student)->post(route('appointments.cancel', $foreign))->assertForbidden();
        $this->assertSame('Scheduled', $foreign->fresh()->STATUS);

        $this->actingAs($nurse)->post(route('appointments.cancel', $owned))->assertForbidden();
        $this->post('/logout');
        $this->post(route('appointments.cancel', $owned))->assertRedirect(route('login'));
    }

    public function test_a_manual_block_or_another_active_visit_keeps_the_slot_closed(): void
    {
        $student = $this->signIn('Student', 'ana.hold@student.apc.edu.ph', 'Ana', 'Hold', '2024106666');
        $physician = 'Dr. Marciano Fidel L. Avendaño';
        $day = now()->next(Carbon::THURSDAY)->toDateString();

        $blocked = $this->visit('2024106666', 'Ana Hold', $physician, $day.' 14:00:00', 'Scheduled');
        DutySlotState::create([
            'PRACTITIONER_NAME' => $physician,
            'SLOT_DATE' => $day,
            'START_TIME' => '14:00:00',
            'STATUS' => 'unavailable',
        ]);

        $this->actingAs($student)->post(route('appointments.cancel', $blocked))->assertRedirect('/appointments');
        $this->assertSame('Cancelled', $blocked->fresh()->STATUS);
        $this->assertSame('unavailable', $this->dutyCell($physician, $day, '14:00:00')['kind']);
        $this->assertFalse($this->slotIsOpen($student, $physician, $day, '14:00:00'));
        $this->assertSame(1, DutySlotState::query()->count());

        $first = $this->visit('2024106666', 'Ana Hold', $physician, $day.' 15:00:00', 'Pending');
        $second = $this->visit('2024107777', 'Other Hold', $physician, $day.' 15:00:00', 'Scheduled');

        $this->actingAs($student)->post(route('appointments.cancel', $first))->assertRedirect('/appointments');
        $this->assertSame('Cancelled', $first->fresh()->STATUS);
        $this->assertSame('Scheduled', $second->fresh()->STATUS);
        $this->assertSame('occupied', $this->dutyCell($physician, $day, '15:00:00')['kind']);
        $this->assertFalse($this->slotIsOpen($student, $physician, $day, '15:00:00'));
    }

    public function test_medical_record_form_keeps_patient_appointment_and_consultation_date_separate(): void
    {
        $student = $this->signIn('Student', 'ana.record2@student.apc.edu.ph', 'Ana', 'Record', '2024108888');
        $nurse = $this->signIn('Medical Staff', 'mina.record2@apc.edu.ph', 'Mina', 'Record', 'EMP-881', 'Nurse');
        $scheduled = '2026-10-15 16:00:00';
        $appointment = Appointment::create([
            'PATIENT_ID' => '551001',
            'PATIENT_NAME' => 'Liza Unique',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Visit',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => $scheduled,
            'STATUS' => 'Scheduled',
        ]);
        $other = Appointment::create([
            'PATIENT_ID' => '551002',
            'PATIENT_NAME' => 'Other Person',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Other',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => '2026-10-16 16:00:00',
            'STATUS' => 'Scheduled',
        ]);

        $this->actingAs($student)->get('/medical-records')->assertRedirect(route('dashboard'));
        $this->actingAs($student)->post('/medical-records', [
            'PATIENT_ID' => '551001',
            'APPT_ID' => $appointment->APPOINTMENT_ID,
            'MEDREC_CONSUL_DATE' => '2026-10-13',
            'MEDREC_DIAGNOSIS' => 'Should stay hidden',
        ])->assertRedirect(route('dashboard'));

        $page = $this->actingAs($nurse)->get('/medical-records');
        $page->assertOk();
        $page->assertSee('Patient ID / Patient name');
        $page->assertSee('Liza Unique — 551001', false);
        $page->assertSee('Scheduled Appointment');
        $page->assertSee('Consultation Date');
        $page->assertSee('"label":"#'.$appointment->APPOINTMENT_ID.'"', false);
        $page->assertSee('October 15, 2026, 4:00 PM', false);
        $page->assertDontSee('#'.$appointment->APPOINTMENT_ID.' — ', false);

        $today = ClinicCalendar::now()->toDateString();
        $this->actingAs($nurse)->post('/medical-records', [
            'PATIENT_ID' => '551001',
            'APPT_ID' => $appointment->APPOINTMENT_ID,
            'MEDREC_CONSUL_DATE' => $today,
            'MEDREC_DIAGNOSIS' => 'Observed today',
            'MEDREC_MEDICINE_DOSAGE' => 'None',
            'MEDREC_NOTES' => 'Rest',
        ])->assertRedirect();

        $record = MedicalRecord::query()->where('MEDREC_DIAGNOSIS', 'Observed today')->first();
        $this->assertNotNull($record);
        $this->assertSame($today, Carbon::parse($record->MEDREC_CONSUL_DATE)->toDateString());
        $this->assertSame(551001, (int) $record->PATIENT_ID);
        $this->assertSame($appointment->APPOINTMENT_ID, (int) $record->APPT_ID);
        $this->assertNotSame($scheduled, (string) $record->MEDREC_CONSUL_DATE);

        $this->actingAs($nurse)->put('/medical-records/'.$record->MEDREC_ID, [
            'PATIENT_ID' => '551001',
            'APPT_ID' => $appointment->APPOINTMENT_ID,
            'MEDREC_CONSUL_DATE' => '2026-10-01',
            'MEDREC_DIAGNOSIS' => 'Observed today',
            'MEDREC_NOTES' => 'Corrected date',
        ])->assertRedirect();
        $this->assertSame('2026-10-01', Carbon::parse($record->fresh()->MEDREC_CONSUL_DATE)->toDateString());

        $this->actingAs($nurse)->post('/medical-records', [
            'PATIENT_ID' => '551001',
            'APPT_ID' => $appointment->APPOINTMENT_ID,
            'MEDREC_CONSUL_DATE' => ClinicCalendar::now()->addDays(10)->toDateString(),
            'MEDREC_DIAGNOSIS' => 'Future visit',
        ])->assertSessionHasErrors('MEDREC_CONSUL_DATE');

        $this->actingAs($nurse)->post('/medical-records', [
            'PATIENT_ID' => '551001',
            'APPT_ID' => $other->APPOINTMENT_ID,
            'MEDREC_CONSUL_DATE' => $today,
            'MEDREC_DIAGNOSIS' => 'Wrong patient',
        ])->assertSessionHasErrors('APPT_ID');
        $this->assertNull(MedicalRecord::query()->where('MEDREC_DIAGNOSIS', 'Wrong patient')->first());
        $this->assertNull(MedicalRecord::query()->where('MEDREC_DIAGNOSIS', 'Future visit')->first());
    }

    private function visit(string $patientId, string $name, string $physician, string $scheduled, string $status): Appointment
    {
        return Appointment::create([
            'PATIENT_ID' => $patientId,
            'PATIENT_NAME' => $name,
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Visit',
            'ATTENDING_PHYSICIAN' => $physician,
            'SCHEDULED_AT' => $scheduled,
            'STATUS' => $status,
        ]);
    }

    private function slotIsOpen(User $student, string $physician, string $day, string $time): bool
    {
        $response = $this->actingAs($student)->getJson('/appointments/slots?physician='.urlencode($physician).'&date='.$day);
        $response->assertOk();

        return collect($response->json('slots'))->contains(
            fn ($slot) => str_contains((string) ($slot['value'] ?? ''), $time)
        );
    }

    private function dutyCell(string $physician, string $day, string $start): array
    {
        $week = app(DutyBoard::class)->week($physician, Carbon::parse($day)->startOfWeek(Carbon::MONDAY));

        foreach ($week['rows'] as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['date'] === $day && $cell['start'] === $start) {
                    return $cell;
                }
            }
        }

        $this->fail('Duty cell '.$day.' '.$start.' was not on the board.');
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
