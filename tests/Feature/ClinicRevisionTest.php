<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\AuditLog;
use App\Models\DutySlotState;
use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\MedicalRecord;
use App\Models\SubUserType;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_search_is_limited_to_profile_managers_and_matches_partial_text(): void
    {
        $manager = $this->signIn('Student', 'jaalba@student.apc.edu.ph', 'Jericho', 'Alba', '2024100094');
        $this->signIn('Student', 'ana.search@student.apc.edu.ph', 'Ana', 'Searchable', '2024999001');
        $outsider = $this->signIn('Student', 'ben.hidden@student.apc.edu.ph', 'Ben', 'Hidden', '2024999002');

        $this->actingAs($outsider)->get('/users')->assertForbidden();

        $page = $this->actingAs($manager)->get('/users?q=searchable');
        $page->assertOk();
        $page->assertSee('Ana Searchable');
        $page->assertDontSee('Ben Hidden');
        $page->assertSee('Student/Employee Number');
        $page->assertSee('col-12 col-lg-5', false);
        $page->assertSee('user-number-label', false);

        $this->actingAs($manager)->get('/users?q=zzznomatch')
            ->assertOk()
            ->assertSee('No accounts match that search.');
    }

    public function test_duty_dates_follow_the_roster_and_nurses_can_reopen_a_manual_block(): void
    {
        $student = $this->signIn('Student', 'ana.book@student.apc.edu.ph', 'Ana', 'Booker', '2024101111');
        $nurse = $this->signIn('Medical Staff', 'mina.slots@apc.edu.ph', 'Mina', 'Reyes', 'EMP-777', 'Nurse');
        $physician = 'Dr. Marciano Fidel L. Avendaño';
        $day = now()->next(Carbon::THURSDAY)->toDateString();

        $dates = $this->actingAs($student)->getJson('/appointments/duty-dates?physician='.urlencode($physician));
        $dates->assertOk();
        $values = collect($dates->json('dates'))->pluck('value');
        $this->assertTrue($values->contains($day));
        $this->assertFalse($values->contains(now()->next(Carbon::SUNDAY)->toDateString()));

        $before = $this->actingAs($student)->getJson('/appointments/slots?physician='.urlencode($physician).'&date='.$day);
        $beforeTimes = collect($before->json('slots'))->pluck('value')->implode(' | ');
        $this->assertTrue(
            collect($before->json('slots'))->contains(fn ($slot) => str_contains((string) ($slot['value'] ?? ''), '14:00:00')),
            $beforeTimes
        );

        $this->actingAs($student)->post('/duty-slots/toggle', [
            'practitioner' => $physician,
            'duty_date' => $day,
            'start' => '14:00:00',
            'status' => 'unavailable',
        ])->assertForbidden();

        $this->actingAs($nurse)->post('/duty-slots/toggle', [
            'practitioner' => $physician,
            'duty_date' => $day,
            'start' => '14:00:00',
            'status' => 'unavailable',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'duty.slot.availability']);
        $closed = $this->actingAs($student)->getJson('/appointments/slots?physician='.urlencode($physician).'&date='.$day);
        $closedTimes = collect($closed->json('slots'))->pluck('value');
        $this->assertFalse($closedTimes->contains(fn ($value) => str_contains((string) $value, '14:00:00')));

        $reopen = $this->actingAs($nurse)->post('/duty-slots/toggle', [
            'practitioner' => $physician,
            'duty_date' => $day,
            'start' => '14:00:00',
            'status' => 'available',
        ]);
        $reopen->assertRedirect();
        $reopen->assertSessionHas('success');
        $this->assertSame(0, DutySlotState::query()->count());

        $open = $this->actingAs($student)->getJson('/appointments/slots?physician='.urlencode($physician).'&date='.$day);
        $openTimes = collect($open->json('slots'))->pluck('value');
        $this->assertTrue($openTimes->contains(fn ($value) => str_contains((string) $value, '14:00:00')));

        $booked = Appointment::create([
            'PATIENT_ID' => '2024101111',
            'PATIENT_NAME' => 'Ana Booker',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Keep this visit',
            'ATTENDING_PHYSICIAN' => $physician,
            'SCHEDULED_AT' => $day.' 15:00:00',
            'STATUS' => 'Scheduled',
        ]);

        $this->actingAs($nurse)->post('/duty-slots/toggle', [
            'practitioner' => $physician,
            'duty_date' => $day,
            'start' => '15:00:00',
            'status' => 'unavailable',
        ])->assertSessionHas('error');
        $this->assertSame('Scheduled', $booked->fresh()->STATUS);

        $this->actingAs($nurse)->post('/duty-slots/toggle', [
            'practitioner' => $physician,
            'duty_date' => '2020-01-09',
            'start' => '14:00:00',
            'status' => 'available',
        ])->assertSessionHas('error');

        $this->assertGreaterThan(0, AuditLog::query()->where('action', 'duty.slot.availability')->count());
    }

    public function test_appointment_list_sorts_by_id_and_hides_previous_weeks_until_history_is_opened(): void
    {
        $nurse = $this->signIn('Medical Staff', 'mina.list@apc.edu.ph', 'Mina', 'List', 'EMP-778', 'Nurse');
        $older = Appointment::create([
            'PATIENT_ID' => '1',
            'PATIENT_NAME' => 'Older Visit',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Previous week reason',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => now()->subWeeks(2),
            'STATUS' => 'Scheduled',
        ]);
        $second = Appointment::create([
            'PATIENT_ID' => '2',
            'PATIENT_NAME' => 'Later Visit',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Later week reason',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => now()->addWeek(),
            'STATUS' => 'Scheduled',
        ]);
        $first = Appointment::create([
            'PATIENT_ID' => '3',
            'PATIENT_NAME' => 'Earlier Id Visit',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Earlier id reason',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => now()->addWeeks(2),
            'STATUS' => 'Scheduled',
        ]);

        $page = $this->actingAs($nurse)->get('/appointments');
        $page->assertOk();
        $page->assertSee('Earlier id reason');
        $page->assertSee('Later week reason');
        $page->assertDontSee('Previous week reason');
        $html = $page->getContent();
        $lowerId = strpos($html, 'Later week reason');
        $higherId = strpos($html, 'Earlier id reason');
        $this->assertNotFalse($lowerId);
        $this->assertNotFalse($higherId);
        $this->assertLessThan($higherId, $lowerId);

        $history = $this->actingAs($nurse)->get('/appointments?history=1');
        $history->assertOk();
        $history->assertSee('Previous week reason');
        $history->assertDontSee('Later week reason');
        $this->assertNotNull(Appointment::find($older->APPOINTMENT_ID));
    }

    public function test_inventory_search_does_not_change_stock_and_batches_use_fefo(): void
    {
        $nurse = $this->signIn('Medical Staff', 'mina.stock@apc.edu.ph', 'Mina', 'Stock', 'EMP-779', 'Nurse');
        $gauze = Inventory::create([
            'ITEM_CODE' => 8101,
            'GENERIC_NAME' => 'Gauze pad',
            'BRAND_NAME' => 'SoftWrap',
            'ITEM_CATEGORY' => 'Medical Supply',
            'ITEM_FORM' => 'Pack',
            'ITEM_QUANTITY' => 12,
        ]);
        Inventory::create([
            'ITEM_CODE' => 8102,
            'GENERIC_NAME' => 'Thermometer',
            'BRAND_NAME' => 'TempCheck',
            'ITEM_CATEGORY' => 'Equipment',
            'ITEM_FORM' => 'Device',
            'ITEM_QUANTITY' => 4,
        ]);

        $search = $this->actingAs($nurse)->get('/inventory?q=soft&category=Medical+Supply');
        $search->assertOk();
        $search->assertSee('Gauze pad');
        $search->assertDontSee('Thermometer');
        $this->assertSame(12, (int) $gauze->fresh()->ITEM_QUANTITY);

        $this->actingAs($nurse)->get('/inventory?q=zzznone')
            ->assertOk()
            ->assertSee('No inventory items match that search.');

        $item = Inventory::create([
            'ITEM_CODE' => 8103,
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_DOSAGE' => '500',
            'ITEM_UNIT' => 'Milligram (mg)',
            'ITEM_QUANTITY' => 8,
            'ITEM_EXPIRATION_DATE' => now()->addMonths(2)->toDateString(),
        ]);
        $soon = InventoryBatch::create([
            'item_code' => 8103,
            'batch_code' => 'SOON-1',
            'quantity' => 3,
            'expiration_date' => now()->addDays(10)->toDateString(),
            'needs_review' => false,
        ]);
        $later = InventoryBatch::create([
            'item_code' => 8103,
            'batch_code' => 'LATER-1',
            'quantity' => 5,
            'expiration_date' => now()->addMonths(6)->toDateString(),
            'needs_review' => false,
        ]);

        $this->actingAs($nurse)->post('/inventory/8103/dispense', ['quantity' => 1])->assertRedirect();
        $this->assertSame(2, (int) $soon->fresh()->quantity);
        $this->assertSame(5, (int) $later->fresh()->quantity);
        $this->assertSame(7, (int) $item->fresh()->ITEM_QUANTITY);

        $expired = Inventory::create([
            'ITEM_CODE' => 8104,
            'GENERIC_NAME' => 'Expired tablets',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_DOSAGE' => '500',
            'ITEM_UNIT' => 'Milligram (mg)',
            'ITEM_QUANTITY' => 4,
            'ITEM_EXPIRATION_DATE' => now()->subDay()->toDateString(),
        ]);
        InventoryBatch::create([
            'item_code' => 8104,
            'batch_code' => null,
            'quantity' => 4,
            'expiration_date' => now()->subDay()->toDateString(),
            'needs_review' => true,
        ]);

        $this->actingAs($nurse)->post('/inventory/8104/dispense', ['quantity' => 1])->assertSessionHasErrors('quantity');
        $this->assertSame(4, (int) $expired->fresh()->ITEM_QUANTITY);
        $this->assertNull(InventoryBatch::query()->where('item_code', 8103)->where('batch_code', 'INVENTED')->value('batch_code'));
    }

    public function test_edit_saves_a_real_batch_number_beside_the_expiration_date(): void
    {
        $nurse = $this->signIn('Medical Staff', 'mina.batch@apc.edu.ph', 'Mina', 'Batch', 'EMP-779', 'Nurse');
        Inventory::create([
            'ITEM_CODE' => 8201,
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_DOSAGE' => '500',
            'ITEM_UNIT' => 'Milligram (mg)',
            'ITEM_QUANTITY' => 4,
            'ITEM_EXPIRATION_DATE' => '2027-04-11',
        ]);
        $batch = InventoryBatch::create([
            'item_code' => 8201,
            'batch_code' => null,
            'quantity' => 4,
            'expiration_date' => '2027-04-11',
            'needs_review' => true,
        ]);

        $this->actingAs($nurse)->get('/inventory')
            ->assertOk()
            ->assertSee('Batch number')
            ->assertSee('No batch number')
            ->assertDontSee('· no batch number');

        $this->actingAs($nurse)->putJson('/inventory/8201', [
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_DOSAGE' => '500',
            'ITEM_UNIT' => 'Milligram (mg)',
            'ITEM_FORM' => 'Capsule',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_QUANTITY' => 6,
            'ITEM_EXPIRATION_DATE' => '2027-04-11',
            'batch_code' => 'LOT-7781',
        ])->assertOk();

        $batch->refresh();
        $this->assertSame('LOT-7781', $batch->batch_code);
        $this->assertFalse($batch->needs_review);
        $this->assertSame(6, (int) $batch->quantity);
        $this->assertSame(1, InventoryBatch::query()->where('item_code', 8201)->count());
        $this->assertSame(6, (int) Inventory::query()->where('ITEM_CODE', 8201)->value('ITEM_QUANTITY'));
    }

    public function test_medical_record_search_stays_inside_the_staff_list(): void
    {
        $student = $this->signIn('Student', 'ana.record@student.apc.edu.ph', 'Ana', 'Record', '2024102222');
        $nurse = $this->signIn('Medical Staff', 'mina.record@apc.edu.ph', 'Mina', 'Record', 'EMP-780', 'Nurse');

        Appointment::create([
            'PATIENT_ID' => '551001',
            'PATIENT_NAME' => 'Liza Unique',
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => 'Visit',
            'ATTENDING_PHYSICIAN' => 'Dr. Marciano Fidel L. Avendaño',
            'SCHEDULED_AT' => now()->addDay(),
            'STATUS' => 'Scheduled',
        ]);
        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => now()->toDateString(),
            'MEDREC_DIAGNOSIS' => 'Liza diagnosis',
            'PATIENT_ID' => 551001,
            'APPT_ID' => 1,
        ]);
        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => now()->toDateString(),
            'MEDREC_DIAGNOSIS' => 'Other diagnosis',
            'PATIENT_ID' => 551002,
            'APPT_ID' => 1,
        ]);

        $this->actingAs($student)->get('/medical-records')->assertRedirect(route('dashboard'));

        $found = $this->actingAs($nurse)->get('/medical-records?q=Liza');
        $found->assertOk();
        $found->assertSee('Liza diagnosis');
        $found->assertDontSee('Other diagnosis');

        $this->actingAs($nurse)->get('/medical-records?q=zzznone')
            ->assertOk()
            ->assertSee('No medical records match that search.');
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
