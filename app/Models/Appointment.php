<?php

namespace App\Models;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $primaryKey = 'APPOINTMENT_ID';

    protected $fillable = [
        'PATIENT_ID',
        'PATIENT_NAME',
        'APPOINTMENT_TYPE',
        'APPOINTMENT_REASON',
        'ATTENDING_PHYSICIAN',
        'SCHEDULED_AT',
        'STATUS',
    ];

    public function scopeForPatient(Builder $query, AppUser $account): Builder
    {
        $ids = $account->appointmentIds();
        $names = $account->nameVariants();

        return $query->where(function (Builder $inner) use ($ids, $names) {
            $applied = false;

            if ($ids !== []) {
                $inner->whereIn('PATIENT_ID', $ids);
                $applied = true;
            }

            foreach ($names as $name) {
                $applied ? $inner->orWhere('PATIENT_NAME', $name) : $inner->where('PATIENT_NAME', $name);
                $applied = true;
            }

            if (! $applied) {
                $inner->whereRaw('0 = 1');
            }
        });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('SCHEDULED_AT', '>=', now())
            ->whereRaw("LOWER(COALESCE(STATUS, '')) NOT IN ('completed', 'cancelled')");
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->whereRaw("LOWER(COALESCE(STATUS, '')) IN ('pending', 'scheduled', 'queued', 'waiting')");
    }
}