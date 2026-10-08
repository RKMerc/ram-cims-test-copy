<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleReminder extends Model
{
    protected $fillable = [
        'email',
        'patient_name',
        'note',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }
}
