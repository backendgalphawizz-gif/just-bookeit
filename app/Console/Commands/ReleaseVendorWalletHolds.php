<?php

namespace App\Console\Commands;

use App\Services\Vendor\VendorWalletService;
use Illuminate\Console\Command;

class ReleaseVendorWalletHolds extends Command
{
    protected $signature = 'wallet:release-holds
                            {--force : Release all held funds immediately, ignoring the hold period}';

    protected $description = 'Move vendor funds from digital wallet to actual wallet after the hold period';

    public function handle(VendorWalletService $walletService): int
    {
        $force = (bool) $this->option('force');

        if ($force && ! $this->confirm('Force-release ALL held wallet amounts now (skip hold period)?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $released = $walletService->releaseExpiredHolds($force);

        $this->info(
            $force
                ? "Force-released {$released} order hold(s) to actual wallet."
                : "Released {$released} order hold(s) to actual wallet."
        );

        return self::SUCCESS;
    }
}
