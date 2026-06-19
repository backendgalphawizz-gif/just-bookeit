<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDemoDataCommand extends Command
{
    protected $signature = 'demo:clear
                            {--force : Skip confirmation prompt}';

    protected $description = 'Remove seeded demo data (products, vendors, customers, drivers, orders, and related records). Keeps admins, roles, categories, locations, and settings.';

    /** @var list<string> */
    protected array $truncateTables = [
        'dispute_messages',
        'disputes',
        'refunds',
        'order_reviews',
        'driver_delivery_skips',
        'driver_wallet_transactions',
        'vendor_wallet_transactions',
        'chat_messages',
        'conversations',
        'support_tickets',
        'notification_reads',
        'cart_items',
        'portfolio_item_images',
        'portfolio_item_variants',
        'portfolio_item_damage_deductions',
        'portfolio_items',
        'vendor_payouts',
        'vendor_shop_images',
        'vendor_portfolio_images',
        'orders',
        'account_status_histories',
        'customer_addresses',
        'customer_measurements',
        'otp_verifications',
        'vendors',
        'customers',
        'drivers',
        'banners',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will permanently delete all customers, vendors, drivers, products, orders, and related demo data. Continue?')) {
            $this->components->warn('Cancelled.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        foreach ($this->truncateTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->truncate();
            $this->line("  Cleared <info>{$table}</info>");
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $deleted = DB::table('personal_access_tokens')
                ->whereIn('tokenable_type', [
                    Customer::class,
                    Vendor::class,
                    Driver::class,
                ])
                ->delete();
            $this->line("  Removed <info>{$deleted}</info> API tokens for customers, vendors, and drivers");
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->components->info('Demo data cleared. Admins, roles, permissions, categories, locations, FAQs, and platform settings were kept.');

        return self::SUCCESS;
    }
}
