<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AppUser extends Model
{
    protected $table = 'AppUser';

    protected $primaryKey = 'Id';

    protected $fillable = [
        'Student_Employee_No',
        'FirstName',
        'LastName',
        'MiddleName',
        'EmailAddress',
        'ContactNo',
        'UserTypeId',
    ];

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'UserTypeId', 'Id');
    }

    public function medicalStaff(): HasOne
    {
        return $this->hasOne(AppUserMedicalStaff::class, 'AppUserId', 'Id');
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->FirstName,
            $this->MiddleName,
            $this->LastName,
        ])));
    }

    public function nameVariants(): array
    {
        return array_values(array_unique(array_filter([
            $this->fullName(),
            trim($this->FirstName.' '.$this->LastName),
        ])));
    }

    public function appointmentIds(): array
    {
        return array_values(array_unique(array_filter([
            (string) $this->Id,
            $this->Student_Employee_No ? (string) $this->Student_Employee_No : null,
        ])));
    }

    public function recordIds(): array
    {
        $ids = [];

        foreach ([$this->Id, $this->Student_Employee_No] as $value) {
            if ($value !== null && $value !== '' && ctype_digit((string) $value)) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    public function isClinicStaff(): bool
    {
        $type = strtolower(trim((string) $this->userType?->Name));
        $sub = strtolower(trim((string) $this->medicalStaff?->subUserType?->Name));

        if (in_array($type, ['student', 'patient'], true)) {
            return false;
        }

        $staffTypes = ['medical staff', 'doctor', 'nurse', 'admin', 'physician', 'dentist', 'staff'];
        $staffRoles = ['physician', 'doctor', 'nurse', 'dentist', 'admin'];

        return in_array($type, $staffTypes, true) || in_array($sub, $staffRoles, true);
    }

    public function roleLabel(): string
    {
        $sub = $this->medicalStaff?->subUserType?->Name;

        if ($this->isClinicStaff()) {
            return match ($sub) {
                'Physician' => 'Doctor',
                'Nurse', 'Dentist', 'Admin' => $sub,
                default => $this->userType?->Name ?? 'Clinic Staff',
            };
        }

        return $this->userType?->Name ?? 'Student';
    }

    public static function syncFromIdentity(?string $email, ?string $name): ?self
    {
        if (! $email) {
            return null;
        }

        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_values(array_filter($parts));
        $first = $parts[0] ?? 'APC';
        $last = count($parts) > 1 ? array_pop($parts) : $first;
        array_shift($parts);
        $middle = $parts ? implode(' ', $parts) : null;

        $account = static::firstOrNew(['EmailAddress' => $email]);
        $account->FirstName = $first;
        $account->LastName = $last;
        $account->MiddleName = $middle;

        if (! $account->exists) {
            $account->UserTypeId = UserType::query()->where('Name', 'Student')->value('Id') ?? 1;
        }

        $account->save();

        return $account;
    }
}
