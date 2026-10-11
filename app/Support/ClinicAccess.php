<?php

namespace App\Support;

use App\Models\AppUser;

class ClinicAccess
{
    private AppUser|false|null $resolved = false;

    private ?string $resolvedEmail = null;

    public function account(): ?AppUser
    {
        $email = strtolower((string) auth()->user()?->email);
        if ($this->resolved !== false && $this->resolvedEmail !== $email) {
            $this->resolved = false;
        }

        if ($this->resolved !== false) {
            return $this->resolved;
        }

        $this->resolvedEmail = $email;

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

        $this->resolvedEmail = strtolower((string) $account->EmailAddress);
        $this->resolved = $account;
    }

    public function isStaff(): bool
    {
        $email = auth()->user()?->email;

        if (PortalGrant::isStaffOnly($email)) {
            return true;
        }

        if (PortalGrant::isDualView($email)) {
            return session('clinic.portal_view') === 'staff';
        }

        return (bool) $this->account()?->isClinicStaff();
    }
}
