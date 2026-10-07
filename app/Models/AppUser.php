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
