<?php

namespace App\Console\Commands;

use App\Support\StockWatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendLowStockReminders extends Command
{
    protected $signature = 'clinic:stock-reminders';

    protected $description = 'Send one reminder for each inventory shortage that is still open after 24 hours.';

    public function handle(StockWatch $stock): int
    {
        $lock = Cache::lock('clinic-stock-reminders', 120);

        if (! $lock->get()) {
            $this->info('A stock reminder run is already in progress.');

            return self::SUCCESS;
        }

        try {
            $sent = $stock->remindDue();
            $this->info('Sent '.$sent.' low-stock reminder'.($sent === 1 ? '' : 's').'.');
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}
