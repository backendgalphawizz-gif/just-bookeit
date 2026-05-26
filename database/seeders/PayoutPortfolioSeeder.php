<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Database\Seeder;

class PayoutPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $activeVendors = Vendor::query()->where('status', 'active')->orderBy('id')->get();
        if ($activeVendors->isEmpty()) {
            return;
        }

        $payoutStatuses = array_merge(
            array_fill(0, 6, 'pending'),
            ['scheduled', 'scheduled', 'processing', 'paid', 'paid', 'paid', 'failed', 'cancelled']
        );

        foreach ($activeVendors as $index => $vendor) {
            $gross = rand(8000, 120000);
            $commission = round($gross * 0.12, 2);
            $status = $payoutStatuses[$index % count($payoutStatuses)];
            VendorPayout::query()->updateOrCreate(
                ['payout_code' => 'PAY'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'vendor_id' => $vendor->id,
                    'gross_amount' => $gross,
                    'commission_amount' => $commission,
                    'net_amount' => $gross - $commission,
                    'status' => $status,
                    'reference' => $status === 'paid' ? 'UTR'.fake()->numerify('##########') : null,
                    'paid_at' => $status === 'paid' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now()->subDays(rand(1, 60)),
                ]
            );
        }

        $serviceIds = Category::query()->where('type', 'service')->pluck('id');
        $portfolioStatuses = array_merge(
            array_fill(0, 8, 'pending'),
            array_fill(0, 12, 'approved'),
            ['rejected', 'rejected']
        );
        $portfolioTitles = [
            'Bridal lehenga look', 'Indo-western ensemble', 'Designer saree drape',
            'Party wear collection', 'Kids festive wear', 'Custom menswear fitting',
        ];

        foreach ($activeVendors->take(10) as $index => $vendor) {
            $status = $portfolioStatuses[$index % count($portfolioStatuses)];
            PortfolioItem::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'title' => $portfolioTitles[$index % count($portfolioTitles)],
                ],
                [
                    'category_id' => $serviceIds->isNotEmpty() ? $serviceIds->random() : null,
                    'description' => fake()->sentence(12),
                    'image_url' => 'https://picsum.photos/seed/jb-'.$vendor->id.'-'.$index.'/800/600',
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? 'Image quality does not meet guidelines.' : null,
                    'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(1, 20)) : null,
                    'created_at' => now()->subDays(rand(1, 45)),
                ]
            );
        }
    }
}
