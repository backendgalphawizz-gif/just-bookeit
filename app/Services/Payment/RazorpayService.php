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
     * Public checkout credentials for mobile / web clients.
     * Never includes the key secret.
     *
     * @return array{enabled: bool, key_id: string|null, currency: string}
     */
    public function publicClientConfig(): array
    {
        $enabled = $this->enabled();

        return [
            'enabled' => $enabled,
            'key_id' => $enabled ? $this->keyId() : null,
            'currency' => $this->currency(),
        ];
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

    /**
     * Confirm a Razorpay payment for place-order flows.
     * Prefer full signature verification; fall back to fetching a captured payment by id.
     *
     * @param  array{razorpay_order_id?: string|null, razorpay_payment_id?: string|null, razorpay_signature?: string|null}  $payload
     * @return array{payment_id: string, order_id: string|null, amount: float, status: string}
     */
    public function assertSuccessfulPayment(array $payload, ?float $expectedAmountRupees = null): array
    {
        $this->assertConfigured();

        $paymentId = trim((string) ($payload['razorpay_payment_id'] ?? ''));
        $orderId = trim((string) ($payload['razorpay_order_id'] ?? ''));
        $signature = trim((string) ($payload['razorpay_signature'] ?? ''));

        if ($paymentId === '') {
            throw new InvalidArgumentException('razorpay_payment_id is required.');
        }

        if ($orderId !== '' && $signature !== '') {
            $this->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);
        }

        try {
            $payment = $this->api()->payment->fetch($paymentId);
        } catch (Throwable $e) {
            Log::error('Razorpay payment fetch failed', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException('Unable to verify Razorpay payment. Please try again.', 0, $e);
        }

        $status = strtolower((string) ($payment['status'] ?? ''));
        if (! in_array($status, ['captured', 'authorized'], true)) {
            throw new InvalidArgumentException('Razorpay payment is not successful (status: '.$status.').');
        }

        if ($orderId !== '' && filled($payment['order_id'] ?? null) && (string) $payment['order_id'] !== $orderId) {
            throw new InvalidArgumentException('Razorpay payment does not match the given order.');
        }

        $amount = round(((int) ($payment['amount'] ?? 0)) / 100, 2);
        if ($expectedAmountRupees !== null && $amount + 0.5 < $expectedAmountRupees) {
            throw new InvalidArgumentException(
                'Paid amount ₹'.number_format($amount, 2).' is less than payable ₹'.number_format($expectedAmountRupees, 2).'.'
            );
        }

        return [
            'payment_id' => $paymentId,
            'order_id' => filled($payment['order_id'] ?? null) ? (string) $payment['order_id'] : ($orderId !== '' ? $orderId : null),
            'amount' => $amount,
            'status' => $status,
        ];
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
