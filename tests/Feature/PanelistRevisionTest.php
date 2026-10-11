<?php

namespace Tests\Feature;

use App\Mail\ConfirmEmailMail;
use App\Mail\LowStockAlertMail;
use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\AuditLog;
use App\Models\ClinicNotification;
use App\Models\Inventory;
use App\Models\LinkedIdentity;
use App\Models\MedicalRecord;
use App\Models\StockAlert;
use App\Models\SubUserType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class PanelistRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_a_weak_password_and_sends_a_confirmation_link(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Ana Cruz',
            'email' => 'ana.cruz@student.apc.edu.ph',
            'password' => 'clinic-pass',
            'password_confirmation' => 'clinic-pass',
        ])->assertSessionHasErrors('password');

        $this->post('/register', [
            'name' => 'Ana Cruz',
            'email' => 'Ana.Cruz@student.apc.edu.ph',
            'password' => 'clinic-pass1',
            'password_confirmation' => 'clinic-pass1',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'ana.cruz@student.apc.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('clinic-pass1', $user->password));
        $this->assertNull($user->email_verified_at);
        Mail::assertSent(ConfirmEmailMail::class);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_forgot_password_page_does_not_reveal_whether_an_account_exists(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Forgot Password');

        $this->post('/forgot-password', [
            'email' => 'missing@student.apc.edu.ph',
        ])->assertSessionHas('status');
    }

    public function test_dispense_uses_the_quantity_given_and_rejects_invalid_stock(): void
    {
        $nurse = $this->nurse();
        Inventory::create($this->medicine(5001, 100, now()->addYear()->toDateString()));

        $this->actingAs($nurse)->post('/inventory/5001/dispense', ['quantity' => 8])
            ->assertRedirect('/inventory');

        $this->assertSame(92, (int) Inventory::where('ITEM_CODE', 5001)->value('ITEM_QUANTITY'));
        $this->assertDatabaseHas('inventory_transactions', [
            'item_code' => 5001,
            'type' => 'dispense',
            'quantity_changed' => -8,
            'previous_quantity' => 100,
            'updated_quantity' => 92,
        ]);

        $this->actingAs($nurse)->post('/inventory/5001/dispense', ['quantity' => 0])
            ->assertSessionHasErrors('quantity');
        $this->actingAs($nurse)->post('/inventory/5001/dispense', ['quantity' => 93])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(92, (int) Inventory::where('ITEM_CODE', 5001)->value('ITEM_QUANTITY'));

        Inventory::where('ITEM_CODE', 5001)->update([
            'ITEM_EXPIRATION_DATE' => now()->subDay()->toDateString(),
        ]);
        $this->actingAs($nurse)->post('/inventory/5001/dispense', ['quantity' => 1])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(92, (int) Inventory::where('ITEM_CODE', 5001)->value('ITEM_QUANTITY'));
    }

    public function test_low_stock_alerts_are_queued_once_and_remind_after_a_day(): void
    {
        Mail::fake();
        config(['clinic.alert_email' => 'clinic@apc.edu.ph']);
        $nurse = $this->nurse();
        Inventory::create($this->medicine(5002, 12, now()->addYear()->toDateString()));

        $this->actingAs($nurse)->post('/inventory/5002/dispense', ['quantity' => 2])->assertRedirect();
        $this->assertSame(1, ClinicNotification::count());
        $this->assertSame(1, StockAlert::whereNull('resolved_at')->count());
        Mail::assertQueued(LowStockAlertMail::class, 1);

        $this->actingAs($nurse)->post('/inventory/5002/dispense', ['quantity' => 1])->assertRedirect();
        $this->assertSame(1, ClinicNotification::count());

        $this->travel(1)->days();
        $this->artisan('clinic:stock-reminders')->assertSuccessful();
        $this->assertSame(2, ClinicNotification::count());

        $this->actingAs($nurse)->post('/inventory/5002/dispense', ['quantity' => 9])->assertRedirect();
        $this->assertSame(0, (int) Inventory::where('ITEM_CODE', 5002)->value('ITEM_QUANTITY'));
        $this->assertSame(3, ClinicNotification::count());
        $this->assertDatabaseHas('stock_alerts', ['item_code' => 5002, 'status' => 'out']);

        $note = ClinicNotification::query()->first();
        $this->actingAs($nurse)->post(route('notifications.read', $note))->assertRedirect();
        $this->assertNull(StockAlert::where('item_code', 5002)->where('status', 'out')->value('resolved_at'));

        Inventory::where('ITEM_CODE', 5002)->update(['ITEM_QUANTITY' => 20]);
        app(\App\Support\StockWatch::class)->sync(Inventory::where('ITEM_CODE', 5002)->first());
        $this->assertSame(0, StockAlert::where('item_code', 5002)->whereNull('resolved_at')->count());

        $this->app->forgetInstance(\App\Support\ClinicAccess::class);
        $this->actingAs($this->student())->get('/notifications')->assertRedirect(route('dashboard'));
        $this->app->forgetInstance(\App\Support\ClinicAccess::class);
        $this->actingAs($nurse)->get('/notifications')->assertOk()->assertSee('Inventory alert');
    }

    public function test_mail_failure_does_not_undo_a_dispense(): void
    {
        config(['clinic.alert_email' => 'clinic@apc.edu.ph']);
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('queue')->andThrow(new \RuntimeException('smtp down'));

        $nurse = $this->nurse();
        Inventory::create($this->medicine(5003, 10, now()->addYear()->toDateString()));

        $this->actingAs($nurse)->post('/inventory/5003/dispense', ['quantity' => 1])->assertRedirect();
        $this->assertSame(9, (int) Inventory::where('ITEM_CODE', 5003)->value('ITEM_QUANTITY'));
        $this->assertSame(1, ClinicNotification::count());
    }

    public function test_microsoft_linking_requires_the_existing_password_and_keeps_the_record(): void
    {
        $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@student.apc.edu.ph',
            'password' => 'clinic-pass1',
            'password_confirmation' => 'clinic-pass1',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'juan@student.apc.edu.ph')->firstOrFail();
        $account = AppUser::query()->where('EmailAddress', 'juan@student.apc.edu.ph')->firstOrFail();
        $patientId = $account->Id;

        MedicalRecord::create([
            'MEDREC_CONSUL_DATE' => '2026-10-01',
            'MEDREC_DIAGNOSIS' => 'Checkup',
            'PATIENT_ID' => $patientId,
            'APPT_ID' => 1,
        ]);

        $this->post('/logout');
        $this->mockMicrosoft('ms-subject-1', 'juan@student.apc.edu.ph', 'Juan Dela Cruz');
        $this->get('/auth/microsoft/callback')->assertRedirect(route('microsoft.link'));
        $this->assertGuest();
        $this->assertSame(0, LinkedIdentity::count());

        $this->post('/auth/microsoft/link', ['password' => 'wrong-pass1'])->assertSessionHasErrors('password');
        $this->post('/auth/microsoft/link', ['password' => 'clinic-pass1'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'juan@student.apc.edu.ph')->count());
        $this->assertDatabaseHas('linked_identities', [
            'user_id' => $user->id,
            'provider' => 'microsoft',
            'provider_subject_id' => 'ms-subject-1',
        ]);
        $this->assertDatabaseHas('medical_record', [
            'PATIENT_ID' => $patientId,
            'MEDREC_DIAGNOSIS' => 'Checkup',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'microsoft.linked']);

        $this->mockMicrosoft('ms-subject-1', 'juan@student.apc.edu.ph', 'Juan Dela Cruz');
        $this->post('/logout');
        $this->get('/auth/microsoft/callback')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, LinkedIdentity::count());
    }

    public function test_microsoft_does_not_merge_two_different_accounts(): void
    {
        $first = User::factory()->create([
            'name' => 'Ana Cruz',
            'email' => 'ana.link@student.apc.edu.ph',
        ]);
        User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.link@student.apc.edu.ph',
        ]);
        $this->signInAccount('ana.link@student.apc.edu.ph', 'Ana', 'Cruz');
        $this->signInAccount('juan.link@student.apc.edu.ph', 'Juan', 'Dela Cruz');

        $this->actingAs($first);
        $this->mockMicrosoft('ms-other', 'juan.link@student.apc.edu.ph', 'Juan Dela Cruz');
        $this->get('/auth/microsoft/callback')->assertRedirect(route('profile'));
        $this->assertSame(0, LinkedIdentity::count());
        $this->assertSame(2, User::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'microsoft.link.conflict']);
        $this->assertSame(0, AuditLog::query()->where('action', 'microsoft.linked')->count());
    }

    public function test_ramsey_answers_typed_english_and_filipino_without_diagnosing(): void
    {
        $student = $this->student();

        $greeting = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Hi']);
        $greeting->assertOk();
        $this->assertSame('Hello! How can I help you today?', $greeting->json('reply'));

        $filipino = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Paano magpa-appointment?']);
        $this->assertStringContainsString('Maaari kang mag-schedule ng appointment', $filipino->json('reply'));

        $follow = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Saan ko makikita yun?']);
        $this->assertStringContainsString('Makikita mo ang Appointments section', $follow->json('reply'));

        $thanks = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Thank you!']);
        $this->assertStringContainsString("You're welcome", $thanks->json('reply'));

        $symptom = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'Masakit ulo ko.']);
        $this->assertStringContainsString('magpakonsulta sa clinic personnel', $symptom->json('reply'));
        $this->assertStringNotContainsString('Paracetamol', $symptom->json('reply'));

        $misspelled = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'How do I make an apointment?']);
        $this->assertStringContainsString('Appointments section', $misspelled->json('reply'));

        $unknown = $this->actingAs($student)->postJson('/ramsey/ask', ['message' => 'adajda']);
        $this->assertSame('Sorry, hindi ko naintindihan ang message mo. Maaari mo bang linawin?', $unknown->json('reply'));
    }

    private function medicine(int $code, int $quantity, string $expiration): array
    {
        return [
            'ITEM_CODE' => $code,
            'GENERIC_NAME' => 'Paracetamol',
            'BRAND_NAME' => 'Biogesic',
            'ITEM_CATEGORY' => 'Medicine',
            'ITEM_FORM' => 'Capsule',
            'ITEM_DOSAGE' => '500',
            'ITEM_UNIT' => 'Milligram (mg)',
            'ITEM_QUANTITY' => $quantity,
            'target_stock' => 50,
            'ITEM_EXPIRATION_DATE' => $expiration,
        ];
    }

    private function nurse(): User
    {
        $user = User::factory()->create([
            'name' => 'Ana Mae Torre',
            'email' => 'anamaet@apc.edu.ph',
        ]);
        $this->signInAccount('anamaet@apc.edu.ph', 'Ana Mae', 'Torre', 'Medical Staff', 'Nurse');

        return $user;
    }

    private function student(): User
    {
        $user = User::factory()->create([
            'name' => 'Ana Cruz',
            'email' => 'ana.student@student.apc.edu.ph',
        ]);
        $this->signInAccount('ana.student@student.apc.edu.ph', 'Ana', 'Cruz');

        return $user;
    }

    private function signInAccount(string $email, string $first, string $last, string $typeName = 'Student', ?string $subName = null): void
    {
        $type = UserType::where('Name', $typeName)->firstOrFail();
        $account = AppUser::create([
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
    }

    private function mockMicrosoft(string $id, string $email, string $name): void
    {
        $microsoftUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $microsoftUser->shouldReceive('getId')->andReturn($id);
        $microsoftUser->shouldReceive('getEmail')->andReturn($email);
        $microsoftUser->shouldReceive('getName')->andReturn($name);

        $driver = \Mockery::mock();
        $driver->shouldReceive('user')->andReturn($microsoftUser);

        Socialite::shouldReceive('driver')->once()->with('microsoft')->andReturn($driver);
    }
}
