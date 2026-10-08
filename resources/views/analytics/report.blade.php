<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>APC Clinic Analytics Report</title>
    <style>
        @page { margin: 28px 32px 36px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #10243f;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        .rule { height: 4px; background: #003B7A; margin: 0 0 2px; }
        .gold { height: 3px; background: #E1B11A; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: middle; }
        .logo { width: 78px; }
        .logo img { width: 72px; height: auto; }
        h1 { margin: 0; color: #003B7A; font-size: 18px; letter-spacing: 0.02em; }
        .school { margin: 0; color: #003B7A; font-size: 12px; font-weight: bold; }
        .sub { margin: 2px 0 0; color: #5c6b80; font-size: 10px; }
        .meta { margin-top: 12px; }
        .meta td { width: 25%; padding: 6px 8px; background: #f4f7fb; border: 1px solid #d7e0ec; vertical-align: top; }
        .label { display: block; color: #5c6b80; font-size: 8px; font-weight: bold; letter-spacing: 0.06em; text-transform: uppercase; }
        h2 { margin: 16px 0 6px; color: #003B7A; font-size: 13px; }
        .cards td { width: 14.28%; padding: 8px 6px; border: 1px solid #d7e0ec; text-align: center; }
        .cards strong { display: block; color: #003B7A; font-size: 16px; }
        .cards span { color: #5c6b80; font-size: 8px; font-weight: bold; letter-spacing: 0.04em; text-transform: uppercase; }
        .data th { background: #003B7A; color: #fff; text-align: left; font-size: 9px; letter-spacing: 0.04em; text-transform: uppercase; padding: 6px; }
        .data td { border-bottom: 1px solid #e4ebf3; padding: 5px 6px; vertical-align: top; }
        .data tr.alt td { background: #f7f9fc; }
        .empty { padding: 10px 6px; color: #5c6b80; }
        .sign { margin-top: 22px; }
        .sign td { width: 33%; padding-top: 28px; vertical-align: bottom; }
        .line { border-top: 1px solid #10243f; margin-right: 18px; padding-top: 4px; font-size: 9px; color: #5c6b80; }
        .foot { margin-top: 16px; color: #5c6b80; font-size: 8px; }
    </style>
</head>
<body>
    <div class="rule"></div>
    <div class="gold"></div>

    <table class="head">
        <tr>
            <td class="logo">
                <img src="{{ public_path('images/apc-logo.png') }}" alt="Asia Pacific College">
            </td>
            <td>
                <p class="school">Asia Pacific College</p>
                <h1>Clinic Analytics Report</h1>
                <p class="sub">Records and Medication — Clinic Information Management System</p>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <span class="label">Report date</span>
                {{ $report['generated']->format('F j, Y') }}
            </td>
            <td>
                <span class="label">Report time</span>
                {{ $report['generated']->format('g:i A') }} PHT
            </td>
            <td>
                <span class="label">Physician</span>
                {{ $report['physician'] }}
            </td>
            <td>
                <span class="label">Prepared by</span>
                {{ $report['prepared_by'] }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Coverage</span>
                @if($report['from']->isSameDay($report['to']))
                    {{ $report['from']->format('F j, Y') }}
                @else
                    {{ $report['from']->format('M j, Y') }} – {{ $report['to']->format('M j, Y') }}
                @endif
            </td>
            <td>
                <span class="label">Reference</span>
                {{ $report['reference'] }}
            </td>
            <td>
                <span class="label">Campus</span>
                APC Clinic
            </td>
            <td>
                <span class="label">Document</span>
                Official clinic copy
            </td>
        </tr>
    </table>

    <h2>Summary</h2>
    <table class="cards">
        <tr>
            <td><strong>{{ $report['summary']['appointments'] }}</strong><span>Appointments</span></td>
            <td><strong>{{ $report['summary']['patients'] }}</strong><span>Patients</span></td>
            <td><strong>{{ $report['summary']['queued'] }}</strong><span>Queued</span></td>
            <td><strong>{{ $report['summary']['completed'] }}</strong><span>Completed</span></td>
            <td><strong>{{ $report['summary']['cancelled'] }}</strong><span>Cancelled</span></td>
            <td><strong>{{ $report['summary']['visits'] }}</strong><span>Visit records</span></td>
            <td><strong>{{ $report['summary']['low_stock'] }}</strong><span>Low stock</span></td>
        </tr>
    </table>

    <h2>Physicians on duty</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Physician / staff</th>
                <th>Role</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['on_duty'] as $index => $duty)
                <tr class="{{ $index % 2 ? 'alt' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($duty['date'])->format('D, M j, Y') }}</td>
                    <td>{{ $duty['name'] }}</td>
                    <td>{{ $duty['role'] }}</td>
                    <td>{{ $duty['hours'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="4">No rostered clinic staff are on duty for this coverage.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Appointments by physician</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Physician</th>
                <th>Appointments</th>
                <th>Queued</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_physician'] as $index => $row)
                <tr class="{{ $index % 2 ? 'alt' : '' }}">
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['queued'] }}</td>
                    <td>{{ $row['completed'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="4">No appointments in this coverage.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Appointments by status</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_status'] as $status => $total)
                <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
                    <td>{{ $status }}</td>
                    <td>{{ $total }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="2">No appointments in this coverage.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Appointment register</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Patient</th>
                <th>Patient ID</th>
                <th>Physician</th>
                <th>Type</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['appointments'] as $appointment)
                <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($appointment->SCHEDULED_AT)->format('M j, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($appointment->SCHEDULED_AT)->format('g:i A') }}</td>
                    <td>{{ $appointment->PATIENT_NAME }}</td>
                    <td>{{ $appointment->PATIENT_ID }}</td>
                    <td>{{ $appointment->ATTENDING_PHYSICIAN }}</td>
                    <td>{{ $appointment->APPOINTMENT_TYPE }}</td>
                    <td>{{ $appointment->APPOINTMENT_REASON }}</td>
                    <td>{{ $appointment->STATUS }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="8">No appointments in this coverage.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Clinic visits</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Patient ID</th>
                <th>Physician</th>
                <th>Diagnosis</th>
                <th>Prescription / dosage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['records'] as $record)
                <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($record->MEDREC_CONSUL_DATE)->format('M j, Y') }}</td>
                    <td>{{ $record->PATIENT_ID }}</td>
                    <td>{{ $report['physician_by_appointment'][$record->APPT_ID] ?? '—' }}</td>
                    <td>{{ $record->MEDREC_DIAGNOSIS }}</td>
                    <td>{{ $record->MEDREC_MEDICINE_DOSAGE }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">No clinic visits recorded in this coverage.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Low stock</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Code</th>
                <th>Generic name</th>
                <th>Brand</th>
                <th>Dosage</th>
                <th>Category</th>
                <th>Expires</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['low_stock'] as $item)
                <tr class="{{ $loop->iteration % 2 === 0 ? 'alt' : '' }}">
                    <td>{{ $item->ITEM_CODE }}</td>
                    <td>{{ $item->GENERIC_NAME }}</td>
                    <td>{{ $item->BRAND_NAME ?: '—' }}</td>
                    <td>{{ trim(($item->ITEM_DOSAGE ?: '').' '.($item->ITEM_UNIT ?: '').' '.($item->ITEM_FORM ?: '')) ?: '—' }}</td>
                    <td>{{ $item->ITEM_CATEGORY }}</td>
                    <td>{{ $item->ITEM_EXPIRATION_DATE ? \Carbon\Carbon::parse($item->ITEM_EXPIRATION_DATE)->format('M j, Y') : '—' }}</td>
                    <td>{{ $item->ITEM_QUANTITY }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="7">All inventory stock levels are normal.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td><div class="line">Prepared by<br>{{ $report['prepared_by'] }}</div></td>
            <td><div class="line">Attending physician<br>{{ $report['physician'] }}</div></td>
            <td><div class="line">Noted by<br>APC Clinic</div></td>
        </tr>
    </table>

    <p class="foot">{{ $report['reference'] }} · Generated {{ $report['generated']->format('F j, Y g:i A') }} Philippine Time · Asia Pacific College Clinic · RAM-CIMS</p>
</body>
</html>
