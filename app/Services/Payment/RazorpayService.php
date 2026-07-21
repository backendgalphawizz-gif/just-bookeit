<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use RuntimeException;
use Throwable;

class RazorpayService
{
    public function enabled(): bool
    {
        return filled(config('services.razorpay.key_id'))
            && filled(config('services.razorpay.key_secret'));
    }

    public function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    public function currency(): string
    {
        return strtoupper((string) config('services.razorpay.currency', 'INR'));
    }

    /**
     * @param  array<string, mixed>  $notes
     * @return array{id: string, amount: int, currency: string, receipt: string, status: string}
     */
    public function createOrder(float $amountRupees, string $receipt, array $notes = []): array
    {
        $this->assertConfigured();

        $amountPaise = (int) round(max(0, $amountRupees) * 100);
        if ($amountPaise < 100) {
            throw new InvalidArgumentException('Payment amount must be at least ₹1.');
        }

        $receipt = substr(preg_replace('/[^A-Za-z0-9_\-]/', '', $receipt) ?: 'order', 0, 40);

        try {
            $order = $this->api()->order->create([
                'receipt' => $receipt,
                'amount' => $amountPaise,
                'currency' => $this->currency(),
                'notes' => $notes,
                'payment_capture' => 1,
            ]);
        } catch (Throwable $e) {
            Log::error('Razorpay order create failed', ['message' => $e->getMessage()]);
            throw new RuntimeException('Unable to start Razorpay payment. Please try again.', 0, $e);
        }

        return [
            'id' => (string) $order['id'],
            'amount' => (int) $order['amount'],
            'currency' => (string) $order['currency'],
            'receipt' => (string) ($order['receipt'] ?? $receipt),
            'status' => (string) ($order['status'] ?? 'created'),
        ];
    }

    /**
     * @param  array{razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string}  $payload
     */
    public function verifyPaymentSignature(array $payload): void
    {
        $this->assertConfigured();

        try {
            $this->api()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $payload['razorpay_order_id'],
                'razorpay_payment_id' => $payload['razorpay_payment_id'],
                'razorpay_signature' => $payload['razorpay_signature'],
            ]);
        } catch (SignatureVerificationError $e) {
            throw new InvalidArgumentException('Payment verification failed. Please try again.', 0, $e);
        }
    }

    /** @return array<string, mixed> */
    public function checkoutOptions(
        string $razorpayOrderId,
        int $amountPaise,
        string $description,
        ?string $customerName = null,
        ?string $customerEmail = null,
        ?string $customerContact = null,
    ): array {
        return [
            'key' => $this->keyId(),
            'amount' => $amountPaise,
            'currency' => $this->currency(),
            'name' => config('app.name', 'Just Book IT'),
            'description' => $description,
            'order_id' => $razorpayOrderId,
            'prefill' => array_filter([
                'name' => $customerName,
                'email' => $customerEmail,
                'contact' => $customerContact,
            ]),
            'theme' => [
                'color' => '#e85d3a',
            ],
        ];
    }

    protected function api(): Api
    {
        $this->assertConfigured();

        return new Api($this->keyId(), (string) config('services.razorpay.key_secret'));
    }

    protected function assertConfigured(): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env.');
        }
    }
}
