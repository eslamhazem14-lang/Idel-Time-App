<?php

namespace App\Console\Commands;

use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileWallets extends Command
{
    protected $signature = 'wallet:reconcile';

    protected $description = 'Verify that every cached wallet balance equals the sum of its ledger entries';

    public function handle(WalletService $wallets): int
    {
        $problems = $wallets->reconcile();
        if ($problems === []) {
            $this->info('All wallets reconcile with the ledger.');

            return self::SUCCESS;
        }

        $this->table(['wallet', 'bucket', 'cached', 'ledger'], $problems);
        Log::critical('Wallet reconciliation mismatch', $problems);

        return self::FAILURE;
    }
}
