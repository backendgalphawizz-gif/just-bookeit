<?php

namespace App\Console\Commands;

use App\Services\Vendor\VendorWalletService;
use Illuminate\Console\Command;

class ReleaseVendorWalletHolds extends Command
{
    protected $signature = 'wallet:release-holds';

    protected $description = 'Move vendor funds from digital wallet to actual wallet after the hold period';

    public function handle(VendorWalletService $walletService): int
    {
        $released = $walletService->releaseExpiredHolds();

        $this->info("Released {$released} order hold(s) to actual wallet.");

        return self::SUCCESS;
    }
}
