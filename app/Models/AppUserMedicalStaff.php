<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserMedicalStaff extends Model
{
    protected $table = 'AppUser_MedicalStaff';

    protected $primaryKey = 'Id';

    protected $fillable = [
        'AppUserId',
        'SubUserTypeId',
        'LicenseNo',
        'IsMonday',
        'IsTuesday',
        'IsWednesday',
        'IsThursday',
        'IsFriday',
    ];

    protected $casts = [
        'IsMonday' => 'boolean',
        'IsTuesday' => 'boolean',
        'IsWednesday' => 'boolean',
        'IsThursday' => 'boolean',
        'IsFriday' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'AppUserId', 'Id');
    }

    public function subUserType(): BelongsTo
    {
        return $this->belongsTo(SubUserType::class, 'SubUserTypeId', 'Id');
    }
}
