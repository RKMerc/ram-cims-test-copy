<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\SubUserType;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserAccountController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => AppUser::query()->with(['userType', 'medicalStaff.subUserType'])->orderBy('LastName')->orderBy('FirstName')->get(),
            'types' => UserType::query()->orderBy('Name')->get(),
            'subtypes' => SubUserType::query()->orderBy('Name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
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

        $account->update([
            'Student_Employee_No' => $validated['Student_Employee_No'] ?? null,
            'FirstName' => $validated['FirstName'],
            'LastName' => $validated['LastName'],
            'MiddleName' => $validated['MiddleName'] ?? null,
            'EmailAddress' => $validated['EmailAddress'],
            'ContactNo' => $validated['ContactNo'] ?? null,
            'UserTypeId' => $validated['UserTypeId'],
        ]);

        if (! empty($validated['SubUserTypeId'])) {
            AppUserMedicalStaff::query()->updateOrCreate(
                ['AppUserId' => $account->Id],
                [
                    'SubUserTypeId' => $validated['SubUserTypeId'],
                    'LicenseNo' => $validated['LicenseNo'] ?? null,
                ]
            );
        } elseif ($account->medicalStaff) {
            $account->medicalStaff->delete();
        }

        return redirect()->route('users.index')->with('success', 'User account updated.');
    }

    public function store(Request $request)
    {
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

        if (! empty($validated['SubUserTypeId'])) {
            AppUserMedicalStaff::updateOrCreate(
                ['AppUserId' => $account->Id],
                [
                    'SubUserTypeId' => $validated['SubUserTypeId'],
                    'LicenseNo' => $validated['LicenseNo'] ?? null,
                ]
            );
        }

        $account->load('userType', 'medicalStaff.subUserType');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User account saved in AppUser.',
                'user' => $account,
            ], 201);
        }

        return redirect()->back()->with('success', 'User account saved in AppUser.');
    }
}
