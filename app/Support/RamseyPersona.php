<?php

namespace App\Support;

class RamseyPersona
{
    public const STUDENT_TITLE = 'RAMsey Student Health Assistant';

    public const STAFF_TITLE = 'RAMsey Clinic Operations Assistant';

    public const STUDENT_DESCRIPTION = 'Your automated assistant for clinic appointment reminders, checking personal visit history, and navigating student health services. RAMsey cannot provide medical diagnoses or prescribe medication.';

    public const STAFF_DESCRIPTION = 'Your operational copilot for clinic workflow shortcuts, low-stock inventory alerts, queue summary updates, and report generation assistance.';

    public const STUDENT_BLOCK = 'Sorry, this action is restricted to APC Clinic Staff.';

    public const STAFF_BLOCK = 'RAMsey is an operational assistant and cannot make clinical decisions, diagnose patients, or issue prescriptions. Please perform clinical entries manually.';

    public function staff(): bool
    {
        if (DeveloperMode::enabled()) {
            $preview = session('clinic.ramsey_persona');

            if ($preview === 'staff') {
                return true;
            }

            if ($preview === 'student') {
                return false;
            }
        }

        return (bool) app(ClinicAccess::class)->account()?->isClinicStaff();
    }

    public function title(): string
    {
        return $this->staff() ? self::STAFF_TITLE : self::STUDENT_TITLE;
    }

    public function description(): string
    {
        return $this->staff() ? self::STAFF_DESCRIPTION : self::STUDENT_DESCRIPTION;
    }

    public function greeting(): string
    {
        if ($this->staff()) {
            return 'I can pull low stock, today\'s queue, a physician\'s open and occupied hours, and the clinic visit report.';
        }

        return 'I can help with your check-ups, your own visit history, prescriptions already on your chart, and slot alerts. I cannot diagnose you or prescribe medication.';
    }

    public function chips(): array
    {
        if ($this->staff()) {
            return [
                ['label' => 'Show items below minimum threshold', 'ask' => 'Show items below minimum threshold'],
                ['label' => 'Daily Queue Summary', 'ask' => 'Daily Queue Summary'],
                ['label' => 'Doctor Duty Schedule Quick-Check', 'panel' => 'duty'],
                ['label' => 'Open Inventory Manager', 'ask' => 'Open Inventory Manager'],
                ['label' => 'View Daily Queue', 'ask' => 'View Daily Queue'],
                ['label' => 'Treatment-First Mode (TFM)', 'ask' => 'Treatment-First Mode (TFM)'],
                ['label' => 'Generate Clinic Visit Report', 'ask' => 'Generate Clinic Visit Report'],
            ];
        }

        return [
            ['label' => 'Set Schedule Availability Reminders', 'panel' => 'remind'],
            ['label' => 'Schedule Check-Up', 'ask' => 'Schedule Check-Up'],
            ['label' => 'View My Latest Visit Summary', 'ask' => 'View My Latest Visit Summary'],
            ['label' => 'View My Active Prescriptions', 'ask' => 'View My Active Prescriptions'],
            ['label' => 'View My Vitals / Contact Info', 'ask' => 'View My Vitals / Contact Info'],
        ];
    }

    public function payload(): array
    {
        return [
            'staff' => $this->staff(),
            'title' => $this->title(),
            'description' => $this->description(),
            'greeting' => $this->greeting(),
            'chips' => $this->chips(),
        ];
    }
}
