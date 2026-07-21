<?php

namespace App\Http\Controllers\Api\V2;

use App\Services\Vendor\VendorEarningsAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EarningsController extends VendorApiController
{
    public function __construct(
        protected VendorEarningsAnalyticsService $analytics
    ) {}

    public function analytics(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $availableYears = $this->analytics->availableYears($vendor);
        $defaultYear = $availableYears[0] ?? (int) now()->year;

        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.((int) now()->year + 1)],
            'service_type' => [
                'nullable',
                'string',
                Rule::in(array_keys(VendorEarningsAnalyticsService::SERVICE_TYPES)),
            ],
        ]);

        $year = (int) ($data['year'] ?? $defaultYear);
        $payload = $this->analytics->analytics($vendor, $year);

        if (! empty($data['service_type'])) {
            $slug = $data['service_type'];
            $payload['by_service_type'] = array_values(array_filter(
                $payload['by_service_type'],
                fn (array $row) => ($row['service_type'] ?? '') === $slug
            ));
            $payload['monthly'] = array_map(function (array $month) use ($slug) {
                $amount = (float) ($month['by_service_type'][$slug] ?? 0);

                return [
                    ...$month,
                    'amount' => $amount,
                    'amount_label' => '₹'.number_format($amount, 0),
                    'by_service_type' => [
                        $slug => $amount,
                    ],
                ];
            }, $payload['monthly']);

            $filteredTotal = round(array_sum(array_column($payload['by_service_type'], 'amount')), 2);
            $payload['summary']['year_total'] = $filteredTotal;
            $payload['summary']['year_total_label'] = '₹'.number_format($filteredTotal, 0);
            $payload['summary']['orders_count'] = (int) ($payload['by_service_type'][0]['orders_count'] ?? 0);
            $payload['filtered_service_type'] = $slug;
        }

        return $this->success($payload);
    }
}
