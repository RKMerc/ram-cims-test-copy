<?php

namespace App\Support;

use App\Models\AppUser;
use App\Models\AppUserMedicalStaff;
use App\Models\SubUserType;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ClinicRoster
{
    public static function groups(): array
    {
        return [
            [
                'role' => 'Medical Doctor',
                'people' => [
                    self::person('Dr. Marciano Fidel L. Avendaño', 'Medical Doctor', [
                        ['days' => 'Thursday', 'weekdays' => [4], 'start' => '14:00', 'end' => '17:00'],
                    ]),
                ],
            ],
            [
                'role' => 'Dentists',
                'people' => [
                    self::person('Dr. Winnie Ann L. Sy', 'Dentist', [
                        ['days' => 'Tuesday', 'weekdays' => [2], 'start' => '14:00', 'end' => '17:00'],
                    ]),
                    self::person('Dr. Ma. Rosalie R. Samoza', 'Dentist', [
                        ['days' => 'Thursday', 'weekdays' => [4], 'start' => '14:00', 'end' => '17:00'],
                    ]),
                ],
            ],
            [
                'role' => 'Nurses',
                'people' => [
                    self::person('Ana Mae J. Torre, R.N.', 'Nurse', [
                        ['days' => 'Monday, Tuesday & Thursday', 'weekdays' => [1, 2, 4], 'start' => '09:00', 'end' => '19:00'],
                        ['days' => 'Wednesday', 'weekdays' => [3], 'start' => '08:00', 'end' => '18:00'],
                        ['days' => 'Friday', 'weekdays' => [5], 'start' => '09:00', 'end' => '18:00'],
                    ]),
                    self::person('Maria Lolita Beatriz P. Magpantay, R.N.', 'Nurse', [
                        ['days' => 'Monday, Tuesday, Thursday & Friday', 'weekdays' => [1, 2, 4, 5], 'start' => '07:00', 'end' => '17:00'],
                        ['days' => 'Saturday', 'weekdays' => [6], 'start' => '07:30', 'end' => '16:30'],
                    ]),
                ],
            ],
        ];
    }

    public static function accounts(): array
    {
        return [
            self::account('marciano.avendano@apc.edu.ph', 'EMP-MD-01', 'Marciano Fidel', 'L.', 'Avendaño', 'Dr. Marciano Fidel L. Avendaño', 'Physician'),
            self::account('winnie.sy@apc.edu.ph', 'EMP-DT-01', 'Winnie Ann', 'L.', 'Sy', 'Dr. Winnie Ann L. Sy', 'Dentist'),
            self::account('rosalie.samoza@apc.edu.ph', 'EMP-DT-02', 'Ma. Rosalie', 'R.', 'Samoza', 'Dr. Ma. Rosalie R. Samoza', 'Dentist'),
            self::account('anamae.torre@apc.edu.ph', 'EMP-RN-01', 'Ana Mae', 'J.', 'Torre', 'Ana Mae J. Torre, R.N.', 'Nurse'),
            self::account('lolita.magpantay@apc.edu.ph', 'EMP-RN-02', 'Maria Lolita Beatriz', 'P.', 'Magpantay', 'Maria Lolita Beatriz P. Magpantay, R.N.', 'Nurse'),
        ];
    }

    public static function names(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => collect($group['people'])->pluck('name'))
            ->values()
            ->all();
    }

    public static function people(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => $group['people'])
            ->values()
            ->all();
    }

    public static function displayNameForEmail(string $email): ?string
    {
        foreach (self::accounts() as $account) {
            if (strcasecmp($account['email'], $email) === 0) {
                return $account['display'];
            }
        }

        return null;
    }

    public static function rows(): array
    {
        $rows = [];

        foreach (self::groups() as $group) {
            foreach ($group['people'] as $person) {
                foreach ($person['shifts'] as $shift) {
                    $rows[] = [
                        'name' => $person['name'],
                        'role' => $person['role'],
                        'days' => $shift['days'],
                        'hours' => $shift['hours'],
                    ];
                }
            }
        }

        return $rows;
    }

    public static function summary(): string
    {
        return collect(self::rows())
            ->map(fn (array $row) => $row['name'].' ('.$row['role'].'): '.$row['days'].', '.$row['hours'])
            ->implode("\n");
    }

    public static function weekdayFlags(string $displayName): array
    {
        $days = [];

        foreach (self::people() as $person) {
            if ($person['name'] !== $displayName) {
                continue;
            }

            foreach ($person['shifts'] as $shift) {
                foreach ($shift['weekdays'] as $weekday) {
                    $days[(int) $weekday] = true;
                }
            }
        }

        return [
            'IsMonday' => isset($days[1]),
            'IsTuesday' => isset($days[2]),
            'IsWednesday' => isset($days[3]),
            'IsThursday' => isset($days[4]),
            'IsFriday' => isset($days[5]),
        ];
    }

    public static function isRostered(string $name): bool
    {
        return collect(self::people())->contains(fn (array $person) => $person['name'] === $name);
    }

    public static function covers(string $name, Carbon $start, Carbon $end): bool
    {
        $person = collect(self::people())->firstWhere('name', $name);

        if (! $person) {
            return false;
        }

        $weekday = $start->dayOfWeekIso;

        foreach ($person['shifts'] as $shift) {
            if (! in_array($weekday, $shift['weekdays'], true)) {
                continue;
            }

            $shiftStart = $start->copy()->setTimeFromTimeString($shift['start']);
            $shiftEnd = $start->copy()->setTimeFromTimeString($shift['end']);

            if ($start->greaterThanOrEqualTo($shiftStart) && $end->lessThanOrEqualTo($shiftEnd)) {
                return true;
            }
        }

        return false;
    }

    public static function ensureAccounts(): void
    {
        if (! Schema::hasTable('AppUser') || ! Schema::hasTable('AppUser_MedicalStaff') || ! Schema::hasTable('SubUserType')) {
            return;
        }

        $typeId = UserType::query()->where('Name', 'Medical Staff')->value('Id');

        if (! $typeId) {
            return;
        }

        foreach (self::accounts() as $account) {
            $user = AppUser::query()->updateOrCreate(
                ['EmailAddress' => $account['email']],
                [
                    'Student_Employee_No' => $account['number'],
                    'FirstName' => $account['first'],
                    'MiddleName' => $account['middle'],
                    'LastName' => $account['last'],
                    'UserTypeId' => $typeId,
                ]
            );

            $subId = SubUserType::query()->where('Name', $account['subtype'])->value('Id');

            if (! $subId) {
                continue;
            }

            $dutyDays = Schema::hasColumn('AppUser_MedicalStaff', 'IsMonday')
                ? self::weekdayFlags($account['display'])
                : [];

            AppUserMedicalStaff::query()->updateOrCreate(
                ['AppUserId' => $user->Id],
                array_merge([
                    'SubUserTypeId' => $subId,
                    'LicenseNo' => null,
                ], $dutyDays)
            );
        }
    }

    private static function account(
        string $email,
        string $number,
        string $first,
        string $middle,
        string $last,
        string $display,
        string $subtype
    ): array {
        return compact('email', 'number', 'first', 'middle', 'last', 'display', 'subtype');
    }

    private static function person(string $name, string $role, array $shifts): array
    {
        return [
            'name' => $name,
            'role' => $role,
            'shifts' => array_map(function (array $shift) {
                $shift['hours'] = Carbon::parse($shift['start'])->format('g:i A')
                    .' – '
                    .Carbon::parse($shift['end'])->format('g:i A');

                return $shift;
            }, $shifts),
        ];
    }
}
