<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutySlotState extends Model
{
    protected $fillable = [
        'PRACTITIONER_NAME',
        'SLOT_DATE',
        'START_TIME',
        'STATUS',
    ];

    protected $casts = [
        'SLOT_DATE' => 'date',
    ];
}
