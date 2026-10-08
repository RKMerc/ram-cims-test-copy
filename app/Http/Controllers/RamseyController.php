<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use App\Support\ClinicAccess;
use App\Support\ClinicRoster;
use App\Support\DeveloperMode;
use App\Support\DutyBoard;
use App\Support\RamseyPersona;
use App\Support\ScheduleAlerts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RamseyController extends Controller
{
    private const LOW_STOCK_AT = 10;

    public function __construct(private RamseyPersona $persona, private DutyBoard $board)
    {
    }

    public function ask(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'physician' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $message = strtolower(trim($validated['message']));

        if ($this->persona->staff()) {
            if ($this->clinicalRequest($message)) {
                return $this->reply(RamseyPersona::STAFF_BLOCK);
            }

            return $this->answerStaff($request, $message);
        }

        if ($this->studentRestricted($message)) {
            return $this->reply(RamseyPersona::STUDENT_BLOCK);
        }

        return $this->answerStudent($message);
    }

    public function remind(Request $request)
    {
        if ($this->persona->staff()) {
            return $this->reply('Slot alerts are part of the student health assistant.');
        }

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'physician' => ['required', 'string', Rule::in(ClinicRoster::names())],
            'date' => 'required|date',
        ]);

        $when = Carbon::parse($data['date'])->toDateString();
        $result = ScheduleAlerts::watch(
            $data['email'],
            app(ClinicAccess::class)->account()?->fullName(),
            $data['physician'],
            $when,
        );

        $label = Carbon::parse($when)->format('F j, Y');

        if ($result['open'] && $result['sent']) {
            $reply = 'A slot is already open for '.$data['physician'].' on '.$label.'. I emailed '.$data['email'].'.';
        } elseif ($result['open']) {
            $reply = 'A slot is already open for '.$data['physician'].' on '.$label.', but the email could not be sent. Your alert is saved for '.$data['email'].'.';
        } else {
            $reply = 'Your alert is saved. When a slot opens for '.$data['physician'].' on '.$label.', I will email '.$data['email'].'.';
        }

        return $this->reply($reply);
    }

    public function view(Request $request)
    {
        abort_unless(DeveloperMode::enabled(), 403);

        $choice = $request->validate([
            'view' => 'required|in:student,staff',
        ])['view'];

        session(['clinic.ramsey_persona' => $choice]);

        return response()->json($this->persona->payload());
    }

    private function answerStudent(string $message)
    {
        if ($this->mentions($message, ['diagnose', 'what is wrong with me', 'am i sick'])) {
            return $this->reply('RAMsey cannot provide medical diagnoses or prescribe medication.');
        }

        return match ($message) {
            'schedule check-up', 'schedule checkup' => $this->checkUpReply(),
            'view my latest visit summary' => $this->visitReply(),
            'view my active prescriptions' => $this->prescriptionReply(),
            'view my vitals / contact info' => $this->contactReply(),
            default => $this->studentKeywords($message),
        };
    }

    private function answerStaff(Request $request, string $message)
    {
        if ($message === 'doctor duty schedule quick-check' || ($request->filled('physician') && str_contains($message, 'duty'))) {
            return $this->dutyReply($request);
        }

        if ($message === 'treatment-first mode (tfm)') {
            return $this->toggleTreatment();
        }

        return match ($message) {
            'show items below minimum threshold' => $this->lowStockReply(),
            'daily queue summary', 'view daily queue' => $this->queueReply(),
            'open inventory manager' => $this->inventoryLink(),
            'generate clinic visit report' => $this->reportReply(),
            default => $this->staffKeywords($message),
        };
    }

    private function studentKeywords(string $message)
    {
        if ($this->mentions($message, ['who are you', 'what can you', 'help', 'ramsey', 'purpose'])) {
            return $this->reply($this->persona->description());
        }

        if ($this->mentions($message, ['vital', 'contact', 'emergency'])) {
            return $this->contactReply();
        }

        if ($this->mentions($message, ['prescription', 'medicine', 'medication'])) {
            return $this->prescriptionReply();
        }

        if ($this->mentions($message, ['visit', 'summary', 'history'])) {
            return $this->visitReply();
        }

        if ($this->mentions($message, ['check-up', 'checkup', 'schedule', 'book', 'appointment'])) {
            return $this->checkUpReply();
        }

        if ($name = $this->namedPhysician($message)) {
            return $this->reply(
                $name." is on duty during:\n".$this->rosterHours($name)."\n\nChoose that physician and a date below if you want an email when a slot opens.",
                [
                    ['label' => 'Schedule Check-Up', 'href' => '/appointments'],
                ]
            );
        }

        if ($this->mentions($message, ['remind', 'availability', 'slot', 'open'])) {
            return $this->reply('Choose a physician and a date below. I will email you when an appointment slot opens.');
        }

        if ($this->mentions($message, ['dashboard', 'home'])) {
            return $this->reply('Your dashboard shows your upcoming check-ups and your latest clinic visit.', [
                ['label' => 'Open Dashboard', 'href' => '/dashboard'],
            ]);
        }

        return $this->reply(
            'I can schedule a check-up, show your latest visit, list prescriptions on your chart, or email you when a slot opens.',
            [
                ['label' => 'Schedule Check-Up', 'href' => '/appointments'],
                ['label' => 'Visit history', 'href' => '/visit-history'],
            ]
        );
    }

    private function staffKeywords(string $message)
    {
        if ($this->mentions($message, ['who are you', 'what can you', 'help', 'ramsey', 'purpose'])) {
            return $this->reply($this->persona->description());
        }

        if ($this->mentions($message, ['threshold', 'low stock', 'below minimum', 'low-stock'])) {
            return $this->lowStockReply();
        }

        if ($this->mentions($message, ['inventory', 'stock', 'supply', 'supplies'])) {
            return $this->inventoryLink();
        }

        if ($this->mentions($message, ['queue', 'waiting'])) {
            return $this->queueReply();
        }

        if ($this->mentions($message, ['treatment-first', 'treatment first', 'tfm'])) {
            return $this->treatmentStatus();
        }

        if ($this->mentions($message, ['report', 'analytics'])) {
            return $this->reportReply();
        }

        if ($this->mentions($message, ['duty', 'timeslot', 'time slot'])) {
            return $this->reply('Choose an attending physician and a date in the duty check. I will list the open and occupied hours.');
        }

        if ($this->mentions($message, ['medical record', 'chart'])) {
            return $this->reply('Medical records are entered by the clinic team. I can open the register, and clinical notes stay a manual entry.', [
                ['label' => 'Open Medical Records', 'href' => '/medical-records'],
            ]);
        }

        if ($this->mentions($message, ['appointment', 'schedule', 'dashboard', 'home'])) {
            return $this->reply('The operations dashboard has today\'s queue. Appointments is where visits are booked.', [
                ['label' => 'Open the queue', 'href' => '/dashboard'],
                ['label' => 'Appointments', 'href' => '/appointments'],
            ]);
        }

        return $this->reply($this->persona->description(), [
            ['label' => 'Open the queue', 'href' => '/dashboard'],
            ['label' => 'Open Inventory Manager', 'href' => '/inventory'],
        ]);
    }

    private function checkUpReply()
    {
        $account = app(ClinicAccess::class)->account();
        $next = $account
            ? Appointment::query()->forPatient($account)->upcoming()->orderBy('SCHEDULED_AT')->first()
            : null;

        $reply = 'You can schedule a check-up from My Appointments.';

        if ($next) {
            $reply .= "\nYour next visit is ".Carbon::parse($next->SCHEDULED_AT)->format('F j, Y g:i A')
                .' with '.$next->ATTENDING_PHYSICIAN.' ('.$next->STATUS.').';
        }

        return $this->reply($reply, [
            ['label' => 'Schedule Check-Up', 'href' => '/appointments'],
        ]);
    }

    private function visitReply()
    {
        $account = app(ClinicAccess::class)->account();
        $visit = $account
            ? MedicalRecord::query()
                ->forPatient($account)
                ->orderByDesc('MEDREC_CONSUL_DATE')
                ->orderByDesc('MEDREC_ID')
                ->first()
            : null;

        if (! $visit) {
            return $this->reply('You do not have a clinic visit on file yet.', [
                ['label' => 'Schedule Check-Up', 'href' => '/appointments'],
            ]);
        }

        $reply = 'Latest visit summary for '.Carbon::parse($visit->MEDREC_CONSUL_DATE)->format('F j, Y').":\n"
            .'Diagnosis on file: '.$visit->MEDREC_DIAGNOSIS."\n"
            .'Notes: '.($visit->MEDREC_NOTES ?: 'None recorded.')."\n"
            .'Medicine: '.($visit->MEDREC_MEDICINE_DOSAGE ?: 'None recorded.');

        return $this->reply($reply, [
            ['label' => 'Visit history', 'href' => '/visit-history'],
        ]);
    }

    private function prescriptionReply()
    {
        $account = app(ClinicAccess::class)->account();
        $records = $account
            ? MedicalRecord::query()
                ->forPatient($account)
                ->whereNotNull('MEDREC_MEDICINE_DOSAGE')
                ->where('MEDREC_MEDICINE_DOSAGE', '!=', '')
                ->orderByDesc('MEDREC_CONSUL_DATE')
                ->orderByDesc('MEDREC_ID')
                ->take(5)
                ->get()
            : collect();

        if ($records->isEmpty()) {
            return $this->reply('You have no prescriptions recorded on your visits.', [
                ['label' => 'Visit history', 'href' => '/visit-history'],
            ]);
        }

        $lines = $records->map(function ($record) {
            return Carbon::parse($record->MEDREC_CONSUL_DATE)->format('M j, Y').' — '.$record->MEDREC_MEDICINE_DOSAGE;
        })->implode("\n");

        return $this->reply("Prescriptions recorded on your visits:\n".$lines, [
            ['label' => 'Visit history', 'href' => '/visit-history'],
        ]);
    }

    private function contactReply()
    {
        $account = app(ClinicAccess::class)->account();
        $number = trim((string) $account?->ContactNo);
        $contact = $number !== ''
            ? 'Your registered emergency contact number is '.$number.'.'
            : 'No emergency contact number is saved on your profile yet.';

        return $this->reply(
            "No vital signs are stored on your student record. Those are taken during a clinic visit.\n".$contact,
            [
                ['label' => 'Profile', 'href' => '/profile'],
            ]
        );
    }

    private function lowStockReply()
    {
        $items = Inventory::query()
            ->where('ITEM_QUANTITY', '<=', self::LOW_STOCK_AT)
            ->orderBy('ITEM_QUANTITY')
            ->orderBy('GENERIC_NAME')
            ->get();

        if ($items->isEmpty()) {
            return $this->reply('All inventory stock levels are normal.', [
                ['label' => 'Open Inventory Manager', 'href' => '/inventory'],
            ]);
        }

        $shown = $items->take(12);
        $lines = $shown->map(function ($item) {
            $brand = $item->BRAND_NAME ? ' ('.$item->BRAND_NAME.')' : '';

            return $item->ITEM_CODE.' '.$item->GENERIC_NAME.$brand.' — '.$item->ITEM_QUANTITY.' left';
        })->implode("\n");

        $extra = $items->count() - $shown->count();
        $reply = $items->count().' item'.($items->count() === 1 ? '' : 's').' at or below the minimum threshold of '.self::LOW_STOCK_AT.":\n".$lines;

        if ($extra > 0) {
            $reply .= "\n".$extra.' more in the inventory manager.';
        }

        return $this->reply($reply, [
            ['label' => 'Open Inventory Manager', 'href' => '/inventory'],
        ]);
    }

    private function queueReply()
    {
        $day = today()->toDateString();
        $treatmentFirst = (bool) session('clinic.treatment_first');
        $query = Appointment::query()->whereDate('SCHEDULED_AT', $day)->queued();

        if ($treatmentFirst) {
            $query->orderByRaw("CASE WHEN APPOINTMENT_TYPE = 'Emergency Care' THEN 0 ELSE 1 END");
        }

        $waiting = $query->orderBy('SCHEDULED_AT')->get();
        $label = today()->format('F j, Y');

        if ($waiting->isEmpty()) {
            return $this->reply('No patients are waiting in today\'s queue ('.$label.').', [
                ['label' => 'Open the queue', 'href' => '/dashboard'],
            ]);
        }

        $lines = $waiting->take(12)->map(function ($appointment) {
            return Carbon::parse($appointment->SCHEDULED_AT)->format('g:i A')
                .' — '.$appointment->PATIENT_NAME
                .' — '.$appointment->APPOINTMENT_TYPE
                .' — '.$appointment->STATUS;
        })->implode("\n");

        $reply = $waiting->count().' patient'.($waiting->count() === 1 ? '' : 's').' waiting in today\'s queue ('.$label.').';

        if ($treatmentFirst) {
            $reply .= ' Treatment-first mode is on, so emergency visits are listed first.';
        }

        $reply .= "\n".$lines;

        return $this->reply($reply, [
            ['label' => 'View Daily Queue', 'href' => '/dashboard'],
            ['label' => 'Appointments', 'href' => '/appointments'],
        ]);
    }

    private function dutyReply(Request $request)
    {
        $physician = trim((string) $request->input('physician'));
        $date = $request->input('date');

        if ($physician === '' || ! $date || ! in_array($physician, ClinicRoster::names(), true)) {
            return $this->reply('Choose an attending physician and a date in the duty check. I will list the open and occupied hours.');
        }

        $day = Carbon::parse($date)->startOfDay();
        $formatted = $day->format('l, F j, Y');
        $hours = $this->rosterHours($physician);
        $monday = $day->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $link = [[
            'label' => 'Open duty board',
            'href' => '/appointments?practitioner='.urlencode($physician).'&week='.$monday,
        ]];

        if ($day->isWeekend()) {
            return $this->reply($physician.' is not on the Monday–Friday duty board for '.$formatted.".\nStanding hours:\n".$hours, $link);
        }

        $week = $this->board->week($physician, $day->copy()->startOfWeek(Carbon::MONDAY));
        $open = [];
        $occupied = [];

        foreach ($week['rows'] as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['date'] !== $day->toDateString()) {
                    continue;
                }

                if ($cell['kind'] === 'available') {
                    $open[] = $row['label'].' — AVAILABLE';
                }

                if ($cell['kind'] === 'occupied') {
                    $occupied[] = $row['label'].' — OCCUPIED - '.$cell['patient'];
                }
            }
        }

        if ($open === [] && $occupied === []) {
            return $this->reply($physician.' has no open or occupied hours on '.$formatted.".\nStanding hours:\n".$hours, $link);
        }

        $reply = $physician.' on '.$formatted.":\n".implode("\n", array_merge($open, $occupied));

        return $this->reply($reply, $link);
    }

    private function reportReply()
    {
        $day = today()->toDateString();
        $label = today()->format('F j, Y');
        $appointments = Appointment::query()->whereDate('SCHEDULED_AT', $day)->count();
        $queued = Appointment::query()->whereDate('SCHEDULED_AT', $day)->queued()->count();
        $visits = MedicalRecord::query()->whereDate('MEDREC_CONSUL_DATE', $day)->count();
        $low = Inventory::query()->where('ITEM_QUANTITY', '<=', self::LOW_STOCK_AT)->count();

        $reply = "Clinic visit report for {$label}:\n"
            ."Appointments: {$appointments}\n"
            ."Patients waiting: {$queued}\n"
            ."Visits recorded: {$visits}\n"
            ."Low-stock items: {$low}\n"
            .'The PDF includes the APC logo, date, time, and physician coverage.';

        return $this->reply($reply, [
            ['label' => 'View PDF', 'href' => '/analytics/report?from='.$day.'&to='.$day, 'target' => '_blank'],
            ['label' => 'Download PDF', 'href' => '/analytics/report/download?from='.$day.'&to='.$day, 'target' => '_blank'],
        ]);
    }

    private function inventoryLink()
    {
        return $this->reply('The inventory manager tracks medicine and supplies, including quantity and expiration.', [
            ['label' => 'Open Inventory Manager', 'href' => '/inventory'],
        ]);
    }

    private function toggleTreatment()
    {
        $enabled = ! session('clinic.treatment_first');
        session(['clinic.treatment_first' => $enabled]);

        $reply = $enabled
            ? 'Treatment-first mode is now on. Emergency visits are called first.'
            : 'Treatment-first mode is now off. The queue follows the schedule.';

        return $this->reply($reply, [
            ['label' => 'Open the queue', 'href' => '/dashboard'],
        ]);
    }

    private function treatmentStatus()
    {
        $reply = session('clinic.treatment_first')
            ? 'Treatment-first mode is on. Emergency visits are called first.'
            : 'Treatment-first mode is off. The queue follows the schedule.';

        return $this->reply($reply, [
            ['label' => 'Open the queue', 'href' => '/dashboard'],
        ]);
    }

    private function rosterHours(string $name): string
    {
        $rows = array_values(array_filter(
            ClinicRoster::rows(),
            fn (array $row) => $row['name'] === $name
        ));

        if ($rows === []) {
            return 'No standing hours are published.';
        }

        return collect($rows)
            ->map(fn (array $row) => $row['days'].', '.$row['hours'])
            ->implode("\n");
    }

    private function namedPhysician(string $message): ?string
    {
        foreach (ClinicRoster::names() as $name) {
            if (str_contains($message, strtolower($name))) {
                return $name;
            }
        }

        return null;
    }

    private function studentRestricted(string $message): bool
    {
        if ($this->mentions($message, [
            'other patient', 'another patient', 'all patients', 'other patients', 'patient list', 'someone else',
            'inventory', 'stock', 'supply', 'supplies',
            'analytics', 'clinic report', 'generate report', 'generate clinic',
            'prescribe', 'issue prescription', 'issue a prescription', 'write a prescription',
            'queue', 'treatment-first', 'treatment first', 'tfm',
            'below minimum', 'minimum threshold', 'medical record', 'duty schedule quick-check',
        ])) {
            return true;
        }

        $aboutMedicine = $this->mentions($message, ['prescription', 'medication']);
        $ownMedicine = $this->mentions($message, [
            'my active prescription', 'my prescription', 'active prescription', 'my medicine', 'my medication',
        ]);

        return $aboutMedicine && ! $ownMedicine;
    }

    private function clinicalRequest(string $message): bool
    {
        return $this->mentions($message, [
            'diagnose', 'prescribe', 'prescription', 'clinical decision',
            'what medication', 'recommend medicine', 'recommend a medicine',
        ]);
    }

    private function mentions(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function reply(string $reply, array $links = [])
    {
        return response()->json([
            'reply' => $reply,
            'links' => $links,
        ]);
    }
}
