<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\MedicalRecord;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('analytics.index', [
            'appointmentsToday' => Appointment::query()->whereDate('SCHEDULED_AT', today())->count(),
            'patientsQueued' => Appointment::query()->whereDate('SCHEDULED_AT', today())->queued()->count(),
            'lowStock' => Inventory::query()->where('ITEM_QUANTITY', '<=', 10)->count(),
            'visits' => MedicalRecord::query()->count(),
            'byStatus' => Appointment::query()
                ->selectRaw('STATUS, COUNT(*) as total')
                ->groupBy('STATUS')
                ->orderByDesc('total')
                ->get(),
        ]);
    }
}
