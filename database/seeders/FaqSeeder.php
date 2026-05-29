<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            Faq::AUDIENCE_USER => [
                ['question' => 'How do I book a service?', 'answer' => 'Browse categories, choose a vendor, select a time slot, and confirm your booking.', 'sort_order' => 1],
                ['question' => 'Can I cancel my booking?', 'answer' => 'Yes. Open your order details and request cancellation according to the vendor policy.', 'sort_order' => 2],
            ],
            Faq::AUDIENCE_VENDOR => [
                ['question' => 'How do I get approved as a vendor?', 'answer' => 'Complete registration with your business details and documents. Admin will review your profile.', 'sort_order' => 1],
                ['question' => 'When do I receive payouts?', 'answer' => 'Payouts are processed after order completion based on the platform commission schedule.', 'sort_order' => 2],
            ],
            Faq::AUDIENCE_DRIVER => [
                ['question' => 'How do I accept delivery orders?', 'answer' => 'Once your driver account is approved, available delivery tasks will appear in the driver app.', 'sort_order' => 1],
                ['question' => 'What documents are required?', 'answer' => 'Aadhar front/back and a valid driving licence are required during registration.', 'sort_order' => 2],
            ],
        ];

        foreach ($samples as $audience => $faqs) {
            foreach ($faqs as $faq) {
                Faq::query()->firstOrCreate(
                    [
                        'audience' => $audience,
                        'question' => $faq['question'],
                    ],
                    [
                        'answer' => $faq['answer'],
                        'sort_order' => $faq['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
