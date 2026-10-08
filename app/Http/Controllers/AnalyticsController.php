<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use App\Support\ClinicAccess;
use App\Support\ClinicReport;
use App\Support\ClinicRoster;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
            'physicians' => ClinicReport::physicianOptions(),
            'reportFrom' => now('Asia/Manila')->toDateString(),
            'reportTo' => now('Asia/Manila')->toDateString(),
        ]);
    }

    public function report(Request $request)
    {
        $report = $this->reportData($request);

        return $this->document($report)->stream($this->filename($report));
    }

    public function download(Request $request)
    {
        $report = $this->reportData($request);

        return $this->document($report)->download($this->filename($report));
    }

    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'physician' => ['nullable', 'string', 'max:255'],
        ]);

        return ClinicReport::build(
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            $validated['physician'] ?? null,
            $this->preparedBy(),
        );
    }

    private function document(array $report)
    {
        return Pdf::loadView('analytics.report', ['report' => $report])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    private function filename(array $report): string
    {
        $from = $report['from']->format('Y-m-d');
        $to = $report['to']->format('Y-m-d');
        $span = $from === $to ? $from : $from.'_to_'.$to;

        return 'APC-Clinic-Analytics-Report-'.$span.'.pdf';
    }

    private function preparedBy(): string
    {
        $account = app(ClinicAccess::class)->account();

        if ($account) {
            return ClinicRoster::displayNameForEmail((string) $account->EmailAddress) ?? $account->fullName();
        }

        return auth()->user()->name ?? 'Clinic Staff';
    }
}
