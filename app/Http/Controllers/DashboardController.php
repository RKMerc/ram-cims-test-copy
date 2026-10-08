<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use App\Support\ClinicAccess;

class DashboardController extends Controller
{
    private const LOW_STOCK_AT = 10;

    public function __construct(private ClinicAccess $access)
    {
    }

    public function index()
    {
        if ($this->access->isStaff()) {
            return view('dashboard.index', $this->staffData());
        }

        return view('dashboard.index', $this->studentData());
    }

    public function nextPatient()
    {
        $treatmentFirst = (bool) session('clinic.treatment_first');
        $next = $this->waitingQuery($treatmentFirst)->first();

        if (! $next) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'No patients are waiting in the queue.');
        }

        $next->update(['STATUS' => 'In Consultation']);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Next patient: '.$next->PATIENT_NAME.'.');
    }

    public function toggleTreatmentMode()
    {
        $enabled = ! session('clinic.treatment_first');
        session(['clinic.treatment_first' => $enabled]);

        return redirect()
            ->route('dashboard')
            ->with('success', $enabled
                ? 'Treatment-first mode is on. Emergency visits are called first.'
                : 'Treatment-first mode is off. The queue follows the schedule.');
    }

    public function notifyLogistics()
    {
        $low = Inventory::query()->where('ITEM_QUANTITY', '<=', self::LOW_STOCK_AT)->count();
        $message = $low > 0
            ? 'Logistics has been notified about '.$low.' low-stock item'.($low === 1 ? '' : 's').'.'
            : 'Logistics has been notified. All inventory stock levels are within range.';

        return redirect()->route('dashboard')->with('success', $message);
    }

    private function staffData(): array
    {
        $treatmentFirst = (bool) session('clinic.treatment_first');

        return [
            'appointmentsToday' => Appointment::query()->whereDate('SCHEDULED_AT', today())->count(),
            'patientsQueued' => Appointment::query()->whereDate('SCHEDULED_AT', today())->queued()->count(),
            'queue' => $this->queueQuery($treatmentFirst)->take(8)->get(),
            'lowStockItems' => Inventory::query()
                ->where('ITEM_QUANTITY', '<=', self::LOW_STOCK_AT)
                ->orderBy('ITEM_QUANTITY')
                ->get(),
            'treatmentFirst' => $treatmentFirst,
        ];
    }

    private function studentData(): array
    {
        $account = $this->access->account();
        $upcoming = $account
            ? Appointment::query()->forPatient($account)->upcoming()->orderBy('SCHEDULED_AT')->get()
            : collect();
        $visits = $account
            ? MedicalRecord::query()
                ->forPatient($account)
                ->orderByDesc('MEDREC_CONSUL_DATE')
                ->orderByDesc('MEDREC_ID')
                ->get()
            : collect();

        return [
            'upcomingAppointments' => $upcoming,
            'upcomingCount' => $upcoming->count(),
            'visitCount' => $visits->count(),
            'latestVisit' => $visits->first(),
        ];
    }

    private function waitingQuery(bool $treatmentFirst)
    {
        return $this->queueQuery($treatmentFirst)->queued();
    }

    private function queueQuery(bool $treatmentFirst)
    {
        $query = Appointment::query()->where('SCHEDULED_AT', '>=', now()->startOfDay());

        if ($treatmentFirst) {
            $query->orderByRaw("CASE WHEN APPOINTMENT_TYPE = 'Emergency Care' THEN 0 ELSE 1 END");
        }

        return $query->orderBy('SCHEDULED_AT');
    }
}
