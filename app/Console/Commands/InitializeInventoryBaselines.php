<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Support\StockBaseline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InitializeInventoryBaselines extends Command
{
    protected $signature = 'clinic:initialize-baselines';

    protected $description = 'Set a provisional stock baseline for medicine and supplies that do not have one.';

    public function handle(StockBaseline $baselines): int
    {
        $counts = [
            'opening' => 0,
            'current' => 0,
            'preserved' => 0,
            'review' => 0,
            'unchanged' => 0,
            'skipped' => 0,
        ];

        foreach (Inventory::query()->orderBy('ITEM_CODE')->get() as $item) {
            $quantity = (int) $item->ITEM_QUANTITY;
            $result = $baselines->initialize($item);
            $item->refresh();

            if ((int) $item->ITEM_QUANTITY !== $quantity) {
                $this->error($item->GENERIC_NAME.' changed quantity during baseline initialization.');

                return self::FAILURE;
            }

            $counts[$result] = ($counts[$result] ?? 0) + 1;

            if ($result === 'review') {
                $this->line($item->GENERIC_NAME.' ('.$item->ITEM_CATEGORY.') is out of stock and has no baseline.');
            } elseif (in_array($result, ['opening', 'current'], true)) {
                $this->line($item->GENERIC_NAME.' baseline '.$item->target_stock.' ('.$result.'), threshold '.$item->stockThreshold().'.');
            }
        }

        $initialized = $counts['opening'] + $counts['current'];
        $this->info('Initialized '.$initialized.' provisional baseline'.($initialized === 1 ? '' : 's').'.');
        $this->info('Preserved '.$counts['preserved'].' existing baseline'.($counts['preserved'] === 1 ? '' : 's').'.');
        $this->info($counts['review'].' item'.($counts['review'] === 1 ? '' : 's').' still need baseline review.');

        Log::info('Inventory baselines initialized.', $counts);

        return self::SUCCESS;
    }
}
