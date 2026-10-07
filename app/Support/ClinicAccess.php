<?php

namespace App\Support;

use App\Models\AppUser;

class ClinicAccess
{
    private AppUser|false|null $resolved = false;

    public function account(): ?AppUser
    {
        if ($this->resolved !== false) {
            return $this->resolved;
        }

        if (! auth()->check() && ! session()->has('clinic.app_user_id')) {
            return $this->resolved = null;
        }

        $account = null;
        $authUser = auth()->user();

        if ($authUser?->email) {
            $account = AppUser::query()
                ->with(['userType', 'medicalStaff.subUserType'])
                ->where('EmailAddress', $authUser->email)
                ->first();

            if (! $account) {
                $account = AppUser::syncFromIdentity($authUser->email, $authUser->name);
                $account?->load(['userType', 'medicalStaff.subUserType']);
            }
        }

        if (! $account && session()->has('clinic.app_user_id')) {
            $sessionAccount = AppUser::query()
                ->with(['userType', 'medicalStaff.subUserType'])
                ->find(session('clinic.app_user_id'));

            $samePerson = ! $authUser
                || ($sessionAccount && strcasecmp((string) $sessionAccount->EmailAddress, (string) $authUser->email) === 0);

            if ($sessionAccount && $samePerson) {
                $account = $sessionAccount;
            }
        }

        if ($account) {
            $this->remember($account);
        }

        return $this->resolved = $account;
    }

    public function remember(AppUser $account): void
    {
        $account->loadMissing(['userType', 'medicalStaff.subUserType']);

        session([
            'clinic.app_user_id' => $account->Id,
            'clinic.user_type_id' => $account->UserTypeId,
        ]);

        $this->resolved = $account;
    }

    public function isStaff(): bool
    {
        return (bool) $this->account()?->isClinicStaff();
    }
}
