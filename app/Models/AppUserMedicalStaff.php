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
