<?php

namespace App\Console\Commands;

use App\Support\StockWatch;
use Illuminate\Console\Command;

class SendLowStockReminders extends Command
{
    protected $signature = 'clinic:stock-reminders';

    protected $description = 'Send one reminder for each inventory shortage that is still open after 24 hours.';

    public function handle(StockWatch $stock): int
    {
        $sent = $stock->remindDue();
        $this->info('Sent '.$sent.' low-stock reminder'.($sent === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
