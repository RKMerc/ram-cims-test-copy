<?php

namespace App\Support;

use App\Mail\LowStockAlertMail;
use App\Models\ClinicNotification;
use App\Models\Inventory;
use App\Models\StockAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StockWatch
{
    public function sync(Inventory $item): void
    {
        $assessment = $item->stockAssessment();
        $active = StockAlert::query()
            ->where('item_code', $item->ITEM_CODE)
            ->whereNull('resolved_at')
            ->first();

        if (in_array($assessment['status'], ['NORMAL', 'CONFIGURATION REQUIRED'], true)) {
            if ($active) {
                $active->forceFill(['resolved_at' => now()])->save();
            }

            return;
        }

        $status = $assessment['status'] === 'OUT OF STOCK' ? 'out' : 'low';
        if ($active && $active->status === $status) {
            return;
        }

        if ($active) {
            $active->forceFill(['resolved_at' => now()])->save();
        }

        $alert = StockAlert::create([
            'item_code' => $item->ITEM_CODE,
            'status' => $status,
            'opened_at' => now(),
            'last_notified_at' => now(),
        ]);

        $this->notify($item, $alert, false);
    }

    public function remindDue(): int
    {
        $sent = 0;
        $due = StockAlert::query()
            ->whereNull('resolved_at')
            ->where('last_notified_at', '<=', now()->subDay())
            ->get();

        foreach ($due as $alert) {
            $item = Inventory::query()->where('ITEM_CODE', $alert->item_code)->first();
            $assessment = $item?->stockAssessment();
            if (! $item || in_array($assessment['status'] ?? 'NORMAL', ['NORMAL', 'CONFIGURATION REQUIRED'], true)) {
                $alert->forceFill(['resolved_at' => now()])->save();
                continue;
            }

            $alert->forceFill(['last_notified_at' => now()])->save();
            $this->notify($item, $alert, true);
            $sent++;
        }

        return $sent;
    }

    private function notify(Inventory $item, StockAlert $alert, bool $reminder): void
    {
        $status = $alert->status === 'out' ? 'OUT OF STOCK' : 'LOW STOCK';
        $title = $reminder ? 'Low-stock reminder: '.$item->GENERIC_NAME : 'Inventory alert: '.$item->GENERIC_NAME;
        $threshold = $item->stockThreshold();
        $minimum = $threshold === null ? 'Baseline not recorded' : $threshold.' '.$item->stockUnit();
        $body = $item->GENERIC_NAME.' has '.$item->ITEM_QUANTITY.' '.$item->stockUnit().' on hand. The minimum is '.$minimum.'. Status: '.$status.'.';

        ClinicNotification::create([
            'item_code' => $item->ITEM_CODE,
            'kind' => $reminder ? 'stock-reminder' : 'stock-'.$alert->status,
            'title' => $title,
            'body' => $body,
        ]);

        if (! config('clinic.alerts_enabled')) {
            return;
        }

        $recipient = config('clinic.alert_email');
        if (! is_string($recipient) || trim($recipient) === '') {
            Log::warning('Inventory email was not queued because CLINIC_ALERT_EMAIL is empty.', [
                'item_code' => $item->ITEM_CODE,
            ]);

            return;
        }

        $testRecipient = strtolower(trim((string) config('clinic.smtp_test_recipient')));
        if ($testRecipient !== '' && strtolower(trim($recipient)) === $testRecipient) {
            Log::warning('Inventory email was not queued because the clinic recipient is the SMTP test address.', [
                'item_code' => $item->ITEM_CODE,
            ]);

            return;
        }

        DB::afterCommit(function () use ($item, $status, $recipient) {
            try {
                Mail::to($recipient)->queue(new LowStockAlertMail($item, $status));
            } catch (Throwable $exception) {
                Log::warning('Low-stock email was not queued.', [
                    'item_code' => $item->ITEM_CODE,
                    'error' => $exception::class,
                ]);
            }
        });
    }
}
