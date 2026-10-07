<?php

namespace App\Http\Controllers;

use App\Models\DoctorSchedule;
use App\Models\ScheduleReminder;
use App\Support\ClinicAccess;
use App\Support\ClinicRoster;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RamseyController extends Controller
{
    public function ask(Request $request)
    {
        $message = strtolower(trim($request->validate([
            'message' => 'required|string|max:500',
        ])['message']));

        if ($this->mentions($message, ['account', 'database', 'appuser', 'stored', 'sso', 'sign in', 'login', 'user type'])) {
            return $this->reply(
                'User accounts are stored in the Data Tier, in the AppUser table. Each account keeps the student or employee number, first name, last name, middle name, email, contact number, and UserTypeId. Role access is linked through UserType, SubUserType, and AppUser_MedicalStaff. Sign-in uses Microsoft SSO with APC credentials, then POST /users saves the account.',
                [
                    ['label' => 'Sign in', 'href' => '/'],
                ]
            );
        }

        if ($this->mentions($message, ['who are you', 'what can you', 'help', 'ramsey', 'purpose'])) {
            if (! $this->isStaff()) {
                return $this->reply(
                    'I am RAMsey, the clinic assistant. I can help you schedule a check-up, review your appointments, and read your own visit history.'
                );
            }

            return $this->reply(
                'I am RAMsey, the clinic assistant. I can point you to the queue, inventory, and medical records, and I can watch for an open schedule. If no clinic slot is available, I will save an email reminder for when a physician publishes one.'
            );
        }

        if ($this->mentions($message, ['inventory', 'stock', 'supply', 'medicine', 'supplies'])) {
            if (! $this->isStaff()) {
                return $this->reply(
                    'Inventory is part of clinic operations and is limited to doctors, nurses, and admins. I can help with your appointments and your own visit history.',
                    [
                        ['label' => 'My appointments', 'href' => '/appointments'],
                        ['label' => 'Visit history', 'href' => '/visit-history'],
                    ]
                );
            }

            return $this->reply(
                'Inventory is where the clinic tracks medicine and supplies, including quantity and expiration.',
                [
                    ['label' => 'Open Inventory', 'href' => '/inventory'],
                ]
            );
        }

        if ($this->mentions($message, ['record', 'diagnosis', 'chart', 'medical record', 'visit history'])) {
            if (! $this->isStaff()) {
                return $this->reply(
                    'Your visit history shows the symptoms, notes, and medicine from your own clinic visits.',
                    [
                        ['label' => 'Visit history', 'href' => '/visit-history'],
                    ]
                );
            }

            return $this->reply(
                'Medical records hold consultation notes, diagnosis, and dosage for a visit.',
                [
                    ['label' => 'Open Medical Records', 'href' => '/medical-records'],
                ]
            );
        }

        if ($this->mentions($message, ['dashboard', 'home', 'overview'])) {
            $summary = $this->isStaff()
                ? 'The operations dashboard summarizes today\'s queue and items that are running low.'
                : 'Your dashboard shows your upcoming check-ups and your latest clinic visit.';

            return $this->reply($summary, [
                ['label' => 'Open Dashboard', 'href' => '/dashboard'],
            ]);
        }

        if ($this->mentions($message, ['appointment', 'schedule', 'book', 'slot', 'check-up', 'checkup', 'availability', 'visit', 'doctor'])) {
            return $this->availabilityReply();
        }

        if (! $this->isStaff()) {
            return $this->reply(
                'I can help you book a check-up, review your appointments, or open your visit history. Ask me something like "Is there an open slot?"',
                [
                    ['label' => 'My appointments', 'href' => '/appointments'],
                    ['label' => 'Visit history', 'href' => '/visit-history'],
                ]
            );
        }

        return $this->reply(
            'I can help you book a visit, check physician availability, or jump to inventory and medical records. Ask me something like "Is there an open slot?"'
        );
    }

    private function isStaff(): bool
    {
        return app(ClinicAccess::class)->isStaff();
    }

    public function remind(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'patient_name' => 'nullable|string|max:255',
        ]);

        ScheduleReminder::create([
            'email' => $validated['email'],
            'patient_name' => $validated['patient_name'] ?? null,
            'note' => 'Notify when a clinic schedule opens.',
        ]);

        return response()->json([
            'reply' => 'Your reminder is saved. When a physician publishes an open schedule, the clinic can email '.$validated['email'].'.',
        ]);
    }

    private function availabilityReply()
    {
        $slots = DoctorSchedule::query()
            ->whereDate('AVAILABLE_DATE', '>=', Carbon::today())
            ->orderBy('AVAILABLE_DATE')
            ->orderBy('START_TIME')
            ->take(3)
            ->get();

        $standing = "These are the clinic duty hours:\n".ClinicRoster::summary();

        if ($slots->isEmpty()) {
            return $this->reply(
                $standing,
                [
                    ['label' => 'View Appointments', 'href' => '/appointments'],
                ]
            );
        }

        $lines = $slots->map(function ($slot) {
            $date = Carbon::parse($slot->AVAILABLE_DATE)->format('M d, Y');
            $start = Carbon::parse($slot->START_TIME)->format('g:i A');
            $end = Carbon::parse($slot->END_TIME)->format('g:i A');

            return $slot->DOCTOR_NAME.' on '.$date.', '.$start.' – '.$end;
        })->implode("\n");

        return $this->reply(
            $standing."\n\nExtra published dates:\n".$lines,
            [
                ['label' => 'Schedule a visit', 'href' => '/appointments'],
            ]
        );
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

    private function reply(string $reply, array $links = [], bool $needsReminder = false)
    {
        return response()->json([
            'reply' => $reply,
            'links' => $links,
            'needs_reminder' => $needsReminder,
        ]);
    }
}
