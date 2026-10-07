<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use Illuminate\Http\Request;

class UserAccountController extends Controller
{
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
