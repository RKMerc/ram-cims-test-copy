<?php

namespace App\Support;

use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\SubUserType;
use App\Models\UserType;

class PortalGrant
{
    public const DUAL_VIEW = [
        'jaalba@student.apc.edu.ph',
        'jerichoalba8@gmail.com',
        'csdeguzman@student.apc.edu.ph',
        'madsoncha@gmail.com',
        'lccruz@student.apc.edu.ph',
        'latrellallencruz2314@gmail.com',
    ];

    public const STAFF = [
        'anamaet@apc.edu.ph' => [
            'number' => 'EMP-RN-01',
            'first' => 'Ana Mae',
            'middle' => 'J.',
            'last' => 'Torre',
            'display' => 'Ana Mae J. Torre, R.N.',
        ],
        'mariam@apc.edu.ph' => [
            'number' => 'EMP-RN-02',
            'first' => 'Maria Lolita Beatriz',
            'middle' => 'P.',
            'last' => 'Magpantay',
            'display' => 'Maria Lolita Beatriz P. Magpantay, R.N.',
        ],
    ];

    public static function normalize(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    public static function isDualView(?string $email): bool
    {
        return in_array(self::normalize($email), self::DUAL_VIEW, true);
    }

    public static function canManageProfiles(?string $email, ?AppUser $account = null): bool
    {
        if (self::isDualView($email)) {
            return true;
        }

        $type = strtolower(trim((string) $account?->userType?->Name));
        $role = strtolower(trim((string) $account?->medicalStaff?->subUserType?->Name));

        return in_array($type, ['admin', 'itro'], true) || in_array($role, ['admin', 'itro'], true);
    }

    public static function isStaffOnly(?string $email): bool
    {
        return array_key_exists(self::normalize($email), self::STAFF);
    }

    public static function allowsEmail(?string $email): bool
    {
        $email = self::normalize($email);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (self::isDualView($email) || self::isStaffOnly($email)) {
            return true;
        }

        return str_ends_with($email, '@student.apc.edu.ph')
            || str_ends_with($email, '@apc.edu.ph')
            || str_ends_with($email, '@gmail.com');
    }

    public static function rememberView(?string $email, string $view): bool
    {
        if (! self::isDualView($email) || ! in_array($view, ['student', 'staff'], true)) {
            return false;
        }

        session([
            'clinic.portal_view' => $view,
            'clinic.ramsey_persona' => $view,
        ]);

        return true;
    }

    public static function provision(string $email, ?string $name): ?AppUser
    {
        $email = self::normalize($email);
        $account = AppUser::syncFromIdentity($email, $name);

        if (! $account || ! self::isStaffOnly($email)) {
            return $account?->load(['userType', 'medicalStaff.subUserType']);
        }

        $profile = self::STAFF[$email];
        $typeId = UserType::query()->where('Name', 'Medical Staff')->value('Id');

        if (! $typeId) {
            return $account->load(['userType', 'medicalStaff.subUserType']);
        }

        $account->forceFill([
            'Student_Employee_No' => $account->Student_Employee_No ?: $profile['number'],
            'FirstName' => $profile['first'],
            'MiddleName' => $profile['middle'],
            'LastName' => $profile['last'],
            'UserTypeId' => $typeId,
        ])->save();

        $subId = SubUserType::query()->where('Name', 'Nurse')->value('Id');

        if ($subId) {
            AppUserMedicalStaff::query()->updateOrCreate(
                ['AppUserId' => $account->Id],
                ['SubUserTypeId' => $subId]
            );
        }

        return $account->load(['userType', 'medicalStaff.subUserType']);
    }
}
