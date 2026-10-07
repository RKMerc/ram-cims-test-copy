<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_record';
    protected $primaryKey = 'MEDREC_ID';

    protected $fillable = [
        'MEDREC_CONSUL_DATE',
        'MEDREC_DIAGNOSIS',
        'MEDREC_NOTES',
        'MEDREC_MEDICINE_DOSAGE',
        'PATIENT_ID',
        'APPT_ID',
    ];
}