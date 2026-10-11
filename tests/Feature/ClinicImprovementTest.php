<?php

namespace Tests\Feature;

use App\Mail\LowStockAlertMail;
use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\ClinicNotification;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use App\Models\SubUserType;
use App\Models\User;
use App\Models\UserType;
use App\Models\InventoryTransaction;
use App\Models\StockAlert;
use App\Support\ClinicAccess;
use App\Support\InventoryLedger;
use App\Support\StockBaseline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClinicImprovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_ramsey_matches_supported_messages_and_rejects_unknown_ones(): void
    {
        $student = $this->student();

        $cases = [
            'Hi' => 'Hello! How can I help you today?',
            'Hello' => 'Hello! How can I help you today?',
            'kumusta?' => 'Mabuti naman! Ikaw, kumusta? Paano kita matutulungan ngayon?',
            'kamusta?' => 'Mabuti naman! Ikaw, kumusta? Paano kita matutulungan ngayon?',
            'kumusta ka?' => 'Mabuti naman! Ikaw, kumusta? Paano kita matutulungan ngayon?',
            'Magandang umaga' => 'Magandang umaga! Paano kita matutulungan ngayon?',
            'Pwede pa help?' => 'Sure! Ano po ang kailangan ninyong tulong?',
            'Can you help me?' => 'Sure! What do you need help with?',
            'Paano magpa-appointment?' => 'Maaari kang mag-schedule ng appointment sa Appointments section ng RAM-CIMS.',
            'Where can I view my records?' => 'You can view your available medical records through the Medical Records section.',
            'Salamat!' => 'Walang anuman! Nandito lang ako kung kailangan mo ng tulong.',
            'Masakit ulo ko.' => 'Pasensya na at masakit ang ulo mo. Para sa tamang assessment, maaari kang magpakonsulta sa clinic personnel.',
            'adajda' => 'Sorry, hindi ko naintindihan ang message mo. Maaari mo bang linawin?',
            'asdfghjkl' => 'Sorry, hindi ko naintindihan ang message mo. Maaari mo bang linawin?',
            'Appointment' => 'Are you asking how to schedule an appointment, or where to find the Appointments section?',
            'Can you change my grades?' => 'I cannot help with that. I can help with clinic services, appointments, and your own visit history.',
        ];

        foreach ($cases as $input => $expected) {
            $this->actingAs($student)->postJson('/ramsey/ask', ['message' => $input])
                ->assertOk()
                ->assertJsonPath('reply', $expected);
        }
    }

    public function test_ramsey_follow_up_uses_appointment_context_and_then_expires(): void
    {
        $student = $this->student();

        $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Paano magpa-appointment?'])->assertOk();
        $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Saan ko makikita yun?'])
            ->assertJsonPath('reply', 'Makikita mo ang Appointments section sa navigation menu.');

        $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'adajda'])->assertOk();
        $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Saan ko makikita yun?'])
            ->assertJsonPath('reply', 'Alin po ang hinahanap ninyo? Appointments, o ang sarili mong visit history?');

        $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Paano magpa-appointment?'])->assertOk();
        $this->travel(31)->minutes();
        $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Saan ko makikita yun?'])
            ->assertJsonPath('reply', 'Alin po ang hinahanap ninyo? Appointments, o ang sarili mong visit history?');
    }

    public function test_category_thresholds_mark_low_stock_out_of_stock_and_missing_configuration(): void
    {
        $this->assertSame('NORMAL', $this->item('Medicine', 21, 100)->stockAssessment()['status']);
        $this->assertSame('LOW STOCK', $this->item('Medicine', 20, 100)->stockAssessment()['status']);
        $this->assertSame(20, $this->item('Medicine', 20, 100)->stockThreshold());
        $this->assertSame('OUT OF STOCK', $this->item('Medicine', 0, 100)->stockAssessment()['status']);

        $this->assertSame('NORMAL', $this->item('Medical Supply', 51, 200)->stockAssessment()['status']);
        $this->assertSame('LOW STOCK', $this->item('Medical Supply', 50, 200)->stockAssessment()['status']);
        $this->assertSame(50, $this->item('Medical Supply', 50, 200)->stockThreshold());

        $this->assertSame('NORMAL', $this->item('Equipment', 2)->stockAssessment()['status']);
        $this->assertSame('LOW STOCK', $this->item('Equipment', 1)->stockAssessment()['status']);

        $this->assertSame('NORMAL', $this->item('First Aid Kit', 3)->stockAssessment()['status']);
        $this->assertSame('LOW STOCK', $this->item('First Aid Kit', 2)->stockAssessment()['status']);

        $missing = $this->item('Medicine', 5, null);
        $this->assertSame('CONFIGURATION REQUIRED', $missing->stockAssessment()['status']);
        $this->assertNull($missing->stockThreshold());
        $this->assertSame('OUT OF STOCK', $this->item('Medicine', 0, null)->stockAssessment()['status']);

        $custom = Inventory::create([
            'ITEM_CODE' => 7101,
            'GENERIC_NAME' => 'Custom gauze',
            'ITEM_CATEGORY' => 'Medical Supply',
            'ITEM_FORM' => 'Pack',
            'ITEM_QUANTITY' => 6,
            'target_stock' => 200,
            'minimum_stock' => 5,
        ]);
        $this->assertSame('NORMAL', $custom->stockAssessment()['status']);
        $custom->ITEM_QUANTITY = 5;
        $this->assertSame('LOW STOCK', $custom->stockAssessment()['status']);

        $incomplete = Inventory::create([
            'ITEM_CODE' => 7102,
            'GENERIC_NAME' => 'Open kit',
            'ITEM_CATEGORY' => 'First Aid Kit',
            'ITEM_FORM' => 'Kit',
            'ITEM_QUANTITY' => 5,
            'kit_complete' => 0,
        ]);
        $this->assertSame(0, $incomplete->availableQuantity());
        $this->assertSame('OUT OF STOCK', $incomplete->stockAssessment()['status']);

        $nurse = User::factory()->create(['email' => 'anamaet@apc.edu.ph', 'name' => 'Ana Mae Torre']);
        $this->actingAs($nurse)->post('/inventory', [
            'GENERIC_NAME' => 'Bad count',
            'ITEM_CATEGORY' => 'Equipment',
            'ITEM_FORM' => 'Device',
            'ITEM_QUANTITY' => -1,
        ])->assertSessionHasErrors('ITEM_QUANTITY');
    }

    public function test_reminders_wait_24_hours_and_a_second_run_does_not_overlap(): void
    {
        Mail::fake();
        config(['clinic.alert_email' => 'clinic@apc.edu.ph']);
        $nurse = User::factory()->create(['email' => 'anamaet@apc.edu.ph', 'name' => 'Ana Mae Torre']);
        Inventory::create([
            'ITEM_CODE' => 7201,
            'GENERIC_NAME' => 'Gloves',
            'ITEM_CATEGORY' => 'Medical Supply',
            'ITEM_FORM' => 'Box',
            'ITEM_QUANTITY' => 51,
            'target_stock' => 200,
        ]);

        $this->actingAs($nurse)->post('/inventory/7201/dispense', ['quantity' => 1])->assertRedirect();
        $this->assertSame(1, ClinicNotification::count());

        $this->travel(23)->hours();
        $this->artisan('clinic:stock-reminders')->assertSuccessful();
        $this->assertSame(1, ClinicNotification::count());

        $this->travel(2)->hours();
        $lock = Cache::lock('clinic-stock-reminders', 120);
        $this->assertTrue($lock->get());
        $this->artisan('clinic:stock-reminders')->expectsOutput('A stock reminder run is already in progress.');
        $this->assertSame(1, ClinicNotification::count());
        $lock->release();

        $this->artisan('clinic:stock-reminders')->assertSuccessful();
        $this->assertSame(2, ClinicNotification::count());

        Inventory::where('ITEM_CODE', 7201)->update(['ITEM_QUANTITY' => 80]);
        app(\App\Support\StockWatch::class)->sync(Inventory::where('ITEM_CODE', 7201)->first());
        $this->assertSame(0, StockAlert::where('item_code', 7201)->whereNull('resolved_at')->count());

        Inventory::where('ITEM_CODE', 7201)->update(['ITEM_QUANTITY' => 40]);
        app(\App\Support\StockWatch::class)->sync(Inventory::where('ITEM_CODE', 7201)->first());
        $this->assertSame(1, StockAlert::where('item_code', 7201)->whereNull('resolved_at')->count());
        $this->assertSame(3, ClinicNotification::count());
        Mail::assertQueued(LowStockAlertMail::class, function (LowStockAlertMail $mail) {
            return $mail->hasTo('clinic@apc.edu.ph')
                && $mail->envelope()->subject === 'RAM-CIMS Inventory Alert: LOW STOCK';
        });
    }

    public function test_baselines_are_initialized_once_and_do_not_move_when_stock_is_dispensed(): void
    {
        Mail::fake();
        config(['clinic.alert_email' => 'anamaet@apc.edu.ph']);
        $nurse = User::factory()->create(['email' => 'anamaet@apc.edu.ph', 'name' => 'Ana Mae Torre']);

        $medicine = Inventory::create([
            'ITEM_CODE' => 7301,
            'GENERIC_NAME' => 'Ibuprofen <script>',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_UNIT' => 'Pieces / Pcs',
            'ITEM_QUANTITY' => 100,
        ]);
        $this->assertSame('current', app(StockBaseline::class)->initialize($medicine));
        $medicine->refresh();
        $this->assertSame(100, (int) $medicine->ITEM_QUANTITY);
        $this->assertSame(100, (int) $medicine->target_stock);
        $this->assertSame('provisional', $medicine->baseline_status);
        $this->assertSame(20, $medicine->stockThreshold());

        $opening = Inventory::create([
            'ITEM_CODE' => 7302,
            'GENERIC_NAME' => 'Cetirizine',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_QUANTITY' => 0,
        ]);
        app(InventoryLedger::class)->receive($opening, 80, $nurse, 'Opening stock');
        $opening->ITEM_QUANTITY = 40;
        $opening->save();
        $this->assertSame('opening', app(StockBaseline::class)->initialize($opening->fresh()));
        $opening->refresh();
        $this->assertSame(40, (int) $opening->ITEM_QUANTITY);
        $this->assertSame(80, (int) $opening->target_stock);
        $this->assertSame(16, $opening->stockThreshold());

        $dispensed = Inventory::create([
            'ITEM_CODE' => 7305,
            'GENERIC_NAME' => 'Recorded ibuprofen',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_QUANTITY' => 50,
        ]);
        InventoryTransaction::create([
            'item_code' => 7305,
            'type' => 'dispense',
            'quantity_changed' => -50,
            'previous_quantity' => 100,
            'updated_quantity' => 50,
            'note' => 'Dispensed',
        ]);
        $this->assertSame('opening', app(StockBaseline::class)->initialize($dispensed));
        $dispensed->refresh();
        $this->assertSame(50, (int) $dispensed->ITEM_QUANTITY);
        $this->assertSame(100, (int) $dispensed->target_stock);
        $this->assertSame(20, $dispensed->stockThreshold());

        $empty = Inventory::create([
            'ITEM_CODE' => 7303,
            'GENERIC_NAME' => 'Empty bottle',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_QUANTITY' => 0,
        ]);
        $this->assertSame('review', app(StockBaseline::class)->initialize($empty));
        $empty->refresh();
        $this->assertNull($empty->target_stock);
        $this->assertSame(0, (int) $empty->ITEM_QUANTITY);
        $this->assertTrue((bool) $empty->needs_baseline_review);
        $this->assertSame('OUT OF STOCK', $empty->stockAssessment()['status']);

        $custom = Inventory::where('ITEM_CODE', 7101)->first();
        if (! $custom) {
            $custom = Inventory::create([
                'ITEM_CODE' => 7101,
                'GENERIC_NAME' => 'Custom gauze',
                'ITEM_CATEGORY' => 'Medical Supply',
                'ITEM_FORM' => 'Pack',
                'ITEM_QUANTITY' => 6,
                'target_stock' => 200,
                'minimum_stock' => 5,
            ]);
        }
        app(StockBaseline::class)->initialize($custom);
        $custom->refresh();
        $this->assertSame(200, (int) $custom->target_stock);
        $this->assertSame(5, (int) $custom->minimum_stock);
        $this->assertSame('approved', $custom->baseline_status);

        $this->artisan('clinic:initialize-baselines')->assertSuccessful();
        $this->artisan('clinic:initialize-baselines')->expectsOutput('Initialized 0 provisional baselines.')->assertSuccessful();
        $this->assertSame(100, (int) Inventory::where('ITEM_CODE', 7301)->value('ITEM_QUANTITY'));
        $this->assertSame(100, (int) Inventory::where('ITEM_CODE', 7301)->value('target_stock'));

        $this->actingAs($nurse)->post('/inventory/7301/dispense', ['quantity' => 40])->assertRedirect();
        $medicine->refresh();
        $this->assertSame(60, (int) $medicine->ITEM_QUANTITY);
        $this->assertSame(100, (int) $medicine->target_stock);
        $this->assertSame(20, $medicine->stockThreshold());
        $this->assertSame('NORMAL', $medicine->stockAssessment()['status']);

        $this->actingAs($nurse)->post('/inventory/7301/dispense', ['quantity' => 40])->assertRedirect();
        $medicine->refresh();
        $this->assertSame('LOW STOCK', $medicine->stockAssessment()['status']);
        Mail::assertQueued(LowStockAlertMail::class, function (LowStockAlertMail $mail) {
            return $mail->hasTo('anamaet@apc.edu.ph')
                && $mail->envelope()->subject === 'RAM-CIMS Inventory Alert: LOW STOCK';
        });

        $html = view('mail.low-stock-alert-html', [
            'item' => $medicine->fresh(),
            'status' => 'LOW STOCK',
            'detectedAt' => 'October 12, 2026 1:00 AM UTC',
        ])->render();
        $this->assertStringContainsString('Dear Ms. Torre', $html);
        $this->assertStringContainsString('Ibuprofen &lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('20 Pieces / Pcs', $html);

        config(['clinic.alert_email' => '']);
        Mail::fake();
        $this->actingAs($nurse)->post('/inventory/7301/dispense', ['quantity' => 20])->assertRedirect();
        $medicine->refresh();
        $this->assertSame(0, (int) $medicine->ITEM_QUANTITY);
        $this->assertSame(100, (int) $medicine->target_stock);
        $this->assertSame('OUT OF STOCK', $medicine->stockAssessment()['status']);
        Mail::assertNothingQueued();

        $this->actingAs($nurse)->post('/inventory', [
            'GENERIC_NAME' => 'Loratadine',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_DOSAGE' => '10',
            'ITEM_UNIT' => 'Milligram (mg)',
            'ITEM_FORM' => 'Capsule',
            'ITEM_QUANTITY' => 50,
            'ITEM_EXPIRATION_DATE' => now()->addYear()->toDateString(),
        ])->assertRedirect('/inventory');
        $created = Inventory::where('GENERIC_NAME', 'Loratadine')->first();
        $this->assertSame(50, (int) $created->ITEM_QUANTITY);
        $this->assertSame(50, (int) $created->target_stock);
        $this->assertSame('provisional', $created->baseline_status);
        $this->assertSame(10, $created->stockThreshold());
    }

    public function test_account_modal_hides_clinical_fields_until_the_type_is_medical_staff(): void
    {
        $admin = $this->developer();

        $page = $this->actingAs($admin)->get('/users');
        $page->assertOk();
        $page->assertSee('id="medicalStaffFields" hidden', false);
        $page->assertSee('syncMedicalStaffFields', false);
        $page->assertSee('Employee No.', false);
        $page->assertSee('min(720px, calc(100vw - 1.5rem))', false);
        $page->assertDontSee('>None<', false);
    }

    public function test_forged_clinical_fields_are_rejected_and_a_real_role_change_keeps_records(): void
    {
        $admin = $this->developer();
        $studentLogin = User::factory()->create([
            'name' => 'Ana Cruz',
            'email' => 'student.qa@student.apc.edu.ph',
        ]);
        $student = $this->account('Student', 'student.qa@student.apc.edu.ph', 'Ana', 'Cruz');
        $employee = $this->account('Employee', 'employee.qa@apc.edu.ph', 'Juan', 'Cruz');
        $nurseUser = User::factory()->create(['email' => 'nurse.qa@apc.edu.ph', 'name' => 'Mina Reyes']);
        $nurse = $this->account('Medical Staff', 'nurse.qa@apc.edu.ph', 'Mina', 'Reyes', 'Nurse', 'RN-1001');

        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => '2026-10-01',
            'MEDREC_DIAGNOSIS' => 'Kept record',
            'PATIENT_ID' => $nurse->Id,
            'APPT_ID' => 9,
        ]);

        $nurseRole = SubUserType::where('Name', 'Nurse')->firstOrFail()->Id;

        $this->actingAs($studentLogin)
            ->put('/users/'.$student->Id, $this->payload($student, [
                'SubUserTypeId' => $nurseRole,
                'LicenseNo' => 'RN-9999',
            ]))->assertForbidden();

        $this->app->forgetInstance(ClinicAccess::class);
        $this->actingAs($admin)->put('/users/'.$student->Id, $this->payload($student, [
            'SubUserTypeId' => $nurseRole,
            'LicenseNo' => 'RN-9999',
        ]))->assertSessionHasErrors('SubUserTypeId');
        $this->assertNull($student->fresh()->medicalStaff);

        $this->actingAs($admin)->put('/users/'.$employee->Id, $this->payload($employee, [
            'LicenseNo' => 'RN-8888',
        ]))->assertSessionHasErrors('SubUserTypeId');
        $this->assertNull($employee->fresh()->medicalStaff);

        $studentType = UserType::where('Name', 'Student')->firstOrFail()->Id;
        $this->actingAs($admin)->put('/users/'.$nurse->Id, $this->payload($nurse, [
            'UserTypeId' => $studentType,
        ]))->assertRedirect(route('users.index'));

        $this->assertSame($studentType, $nurse->fresh()->UserTypeId);
        $this->assertNull($nurse->fresh()->medicalStaff);
        $this->assertDatabaseHas('medical_record', [
            'PATIENT_ID' => $nurse->Id,
            'MEDREC_DIAGNOSIS' => 'Kept record',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.clinical-access-revoked']);

        $this->app->forgetInstance(ClinicAccess::class);
        $this->actingAs($nurseUser)->get('/inventory')->assertRedirect(route('dashboard'));

        $medicalType = UserType::where('Name', 'Medical Staff')->firstOrFail()->Id;
        $this->app->forgetInstance(ClinicAccess::class);
        $this->actingAs($admin)->put('/users/'.$employee->Id, $this->payload($employee, [
            'UserTypeId' => $medicalType,
            'SubUserTypeId' => $nurseRole,
            'LicenseNo' => 'RN-4242',
        ]))->assertRedirect(route('users.index'));
        $this->assertSame('Nurse', $employee->fresh()->medicalStaff->subUserType->Name);
        $this->assertSame('RN-4242', $employee->fresh()->medicalStaff->LicenseNo);

        $this->app['auth']->logout();
        $this->post('/users', [
            'FirstName' => 'Fake',
            'LastName' => 'Doctor',
            'EmailAddress' => 'fake.doctor@student.apc.edu.ph',
            'UserTypeId' => $medicalType,
            'SubUserTypeId' => $nurseRole,
            'LicenseNo' => 'RN-0001',
        ])->assertForbidden();
    }

    private function item(string $category, int $quantity, ?int $target = null): Inventory
    {
        return new Inventory([
            'ITEM_CATEGORY' => $category,
            'ITEM_QUANTITY' => $quantity,
            'target_stock' => $target,
        ]);
    }

    private function student(): User
    {
        $user = User::factory()->create([
            'name' => 'Ana Cruz',
            'email' => 'ana.chat@student.apc.edu.ph',
        ]);
        $this->account('Student', 'ana.chat@student.apc.edu.ph', 'Ana', 'Cruz');

        return $user;
    }

    private function developer(): User
    {
        $user = User::factory()->create([
            'name' => 'Jericho Alba',
            'email' => 'jaalba@student.apc.edu.ph',
        ]);
        $this->account('Student', 'jaalba@student.apc.edu.ph', 'Jericho', 'Alba');

        return $user;
    }

    private function account(string $typeName, string $email, string $first, string $last, ?string $role = null, ?string $license = null): AppUser
    {
        $type = UserType::where('Name', $typeName)->firstOrFail();
        $account = AppUser::create([
            'FirstName' => $first,
            'LastName' => $last,
            'EmailAddress' => $email,
            'UserTypeId' => $type->Id,
        ]);

        if ($role) {
            $sub = SubUserType::where('Name', $role)->firstOrFail();
            AppUserMedicalStaff::create([
                'AppUserId' => $account->Id,
                'SubUserTypeId' => $sub->Id,
                'LicenseNo' => $license,
            ]);
        }

        return $account;
    }

    private function payload(AppUser $account, array $overrides = []): array
    {
        return array_merge([
            'FirstName' => $account->FirstName,
            'MiddleName' => $account->MiddleName,
            'LastName' => $account->LastName,
            'EmailAddress' => $account->EmailAddress,
            'Student_Employee_No' => $account->Student_Employee_No,
            'ContactNo' => $account->ContactNo,
            'UserTypeId' => $account->UserTypeId,
        ], $overrides);
    }
}
