<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Support\ClinicAccess;

class VisitHistoryController extends Controller
{
    public function index(ClinicAccess $access)
    {
        $account = $access->account();
        $visits = $account
            ? MedicalRecord::query()
                ->forPatient($account)
                ->orderByDesc('MEDREC_CONSUL_DATE')
                ->orderByDesc('MEDREC_ID')
                ->get()
            : collect();

        return view('visit-history.index', compact('visits'));
    }
}
