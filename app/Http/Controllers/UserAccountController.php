<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\AuditLog;
use App\Models\SubUserType;
use App\Models\UserType;
use App\Support\ClinicAccess;
use App\Support\PortalGrant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserAccountController extends Controller
{
    public function index()
    {
        $this->authorizeProfiles();

        $types = UserType::query()->orderBy('Name')->get();

        return view('users.index', [
            'users' => AppUser::query()->with(['userType', 'medicalStaff.subUserType'])->orderBy('LastName')->orderBy('FirstName')->get(),
            'types' => $types,
            'typeNames' => $types->mapWithKeys(fn (UserType $type) => [$type->Id => $type->Name]),
            'subtypes' => SubUserType::query()->orderBy('Name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeProfiles();

        $account = AppUser::query()->findOrFail($id);

        $validated = $request->validate([
            'Student_Employee_No' => ['nullable', 'string', 'max:20', Rule::unique('AppUser', 'Student_Employee_No')->ignore($account->Id, 'Id')],
            'FirstName' => 'required|string|max:255',
            'LastName' => 'required|string|max:255',
            'MiddleName' => 'nullable|string|max:255',
            'EmailAddress' => ['required', 'email', 'max:255', Rule::unique('AppUser', 'EmailAddress')->ignore($account->Id, 'Id')],
            'ContactNo' => 'nullable|string|max:30',
            'UserTypeId' => 'required|exists:UserType,Id',
            'SubUserTypeId' => 'nullable|exists:SubUserType,Id',
            'LicenseNo' => 'nullable|string|max:100',
        ]);

        $clinical = $this->clinicalPlan($account, $validated);

        $account->update([
            'Student_Employee_No' => $validated['Student_Employee_No'] ?? null,
            'FirstName' => $validated['FirstName'],
            'LastName' => $validated['LastName'],
            'MiddleName' => $validated['MiddleName'] ?? null,
            'EmailAddress' => $validated['EmailAddress'],
            'ContactNo' => $validated['ContactNo'] ?? null,
            'UserTypeId' => $validated['UserTypeId'],
        ]);

        $this->applyClinicalPlan($account->fresh(), $clinical);

        return redirect()->route('users.index')->with('success', 'User account updated.');
    }

    public function store(Request $request)
    {
        $type = UserType::query()->find($request->input('UserTypeId'));
        $typeName = strtolower((string) $type?->Name);
        $clinicalRequest = in_array($typeName, ['medical staff', 'admin'], true)
            || $request->filled('SubUserTypeId')
            || $request->filled('LicenseNo');

        if ($clinicalRequest) {
            $this->authorizeProfiles();
        }

        $validated = $request->validate([
            'Student_Employee_No' => 'nullable|string|max:20',
            'FirstName' => 'required|string|max:255',
            'LastName' => 'required|string|max:255',
            'MiddleName' => 'nullable|string|max:255',
            'EmailAddress' => 'required|email|max:255',
            'ContactNo' => 'nullable|string|max:30',
            'UserTypeId' => 'required|exists:UserType,Id',
            'SubUserTypeId' => 'nullable|exists:SubUserType,Id',
            'LicenseNo' => 'nullable|string|max:100',
        ]);

        $clinical = $this->clinicalPlan(new AppUser, $validated);

        $account = AppUser::updateOrCreate(
            ['EmailAddress' => $validated['EmailAddress']],
            [
                'Student_Employee_No' => $validated['Student_Employee_No'] ?? null,
                'FirstName' => $validated['FirstName'],
                'LastName' => $validated['LastName'],
                'MiddleName' => $validated['MiddleName'] ?? null,
                'ContactNo' => $validated['ContactNo'] ?? null,
                'UserTypeId' => $validated['UserTypeId'],
            ]
        );

        $this->applyClinicalPlan($account->fresh(), $clinical);

        $account->load('userType', 'medicalStaff.subUserType');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User account saved in AppUser.',
                'user' => $account,
            ], 201);
        }

        return redirect()->back()->with('success', 'User account saved in AppUser.');
    }

    private function clinicalPlan(AppUser $account, array $validated): array
    {
        $account->loadMissing('medicalStaff');
        $typeName = strtolower((string) UserType::query()->find($validated['UserTypeId'])?->Name);
        $keepsExistingClinicalRole = $typeName === 'admin' && $account->medicalStaff;
        $isMedicalStaff = $typeName === 'medical staff';

        if (! $isMedicalStaff && ! $keepsExistingClinicalRole) {
            if (! empty($validated['SubUserTypeId']) || filled($validated['LicenseNo'] ?? null)) {
                throw ValidationException::withMessages([
                    'SubUserTypeId' => 'Medical staff role and license are only for Medical Staff accounts.',
                ]);
            }

            return ['action' => $account->medicalStaff ? 'revoke' : 'none'];
        }

        $role = SubUserType::query()->find($validated['SubUserTypeId'] ?? null);
        if (! $role || ! in_array($role->Name, ['Physician', 'Nurse', 'Dentist'], true)) {
            throw ValidationException::withMessages([
                'SubUserTypeId' => 'Select a Physician, Nurse, or Dentist role.',
            ]);
        }

        $license = trim((string) ($validated['LicenseNo'] ?? ''));
        if (! preg_match('/^[A-Za-z0-9-]{4,30}$/', $license)) {
            throw ValidationException::withMessages([
                'LicenseNo' => 'Enter a license number of 4 to 30 letters or numbers.',
            ]);
        }

        return [
            'action' => 'assign',
            'role_id' => $role->Id,
            'role_name' => $role->Name,
            'license' => $license,
        ];
    }

    private function applyClinicalPlan(AppUser $account, array $plan): void
    {
        $account->load('medicalStaff');

        if ($plan['action'] === 'revoke' && $account->medicalStaff) {
            AuditLog::record('account.clinical-access-revoked', $account, [
                'previous_role_id' => $account->medicalStaff->SubUserTypeId,
            ]);
            $account->medicalStaff->delete();

            return;
        }

        if ($plan['action'] !== 'assign') {
            return;
        }

        AppUserMedicalStaff::query()->updateOrCreate(
            ['AppUserId' => $account->Id],
            [
                'SubUserTypeId' => $plan['role_id'],
                'LicenseNo' => $plan['license'],
            ]
        );

        AuditLog::record('account.clinical-role-assigned', $account, [
            'role' => $plan['role_name'],
        ]);
    }

    private function authorizeProfiles(): void
    {
        abort_unless(
            PortalGrant::canManageProfiles(auth()->user()?->email, app(ClinicAccess::class)->account()),
            403
        );
    }
}
