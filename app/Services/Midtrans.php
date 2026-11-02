<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Midtrans Core API client: create a QRIS charge, read a
 * transaction's status and verify notification signatures.
 */
class Midtrans
{
    public function __construct(
        private readonly ?string $serverKey,
        private readonly bool $isProduction,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('services.midtrans.server_key'), config('services.midtrans.is_production'));
    }

    public function isConfigured(): bool
    {
        return filled($this->serverKey);
    }

    /**
     * Create a QRIS transaction.
     *
     * @param  array{first_name: string, email: string}  $customer
     * @return array<string, mixed> Midtrans response (status_code 201 on success)
     *
     * @throws RequestException
     */
    public function chargeQris(string $orderId, int $amount, string $itemName, array $customer, int $expiryMinutes): array
    {
        return $this->request()->post('/v2/charge', [
            'payment_type' => 'qris',
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $amount],
            'item_details' => [['id' => 'MEMBERSHIP', 'price' => $amount, 'quantity' => 1, 'name' => $itemName]],
            'customer_details' => $customer,
            'qris' => ['acquirer' => 'gopay'],
            'custom_expiry' => ['expiry_duration' => $expiryMinutes, 'unit' => 'minute'],
        ])->throw()->json();
    }

    /**
     * Current status of a transaction, straight from Midtrans. Errors (such as
     * an unknown order, status_code "404") are returned in the body, not thrown.
     *
     * @return array<string, mixed>
     */
    public function status(string $orderId): array
    {
        return $this->request()->get('/v2/'.rawurlencode($orderId).'/status')->json() ?? [];
    }

    /**
     * Check a notification's signature: sha512(order_id + status_code + gross_amount + server_key).
     *
     * @param  array<string, mixed>  $notification
     */
    public function hasValidSignature(array $notification): bool
    {
        if (! $this->isConfigured() || ! isset($notification['signature_key'], $notification['order_id'], $notification['status_code'], $notification['gross_amount'])) {
            return false;
        }

        $expected = hash('sha512', $notification['order_id'].$notification['status_code'].$notification['gross_amount'].$this->serverKey);

        return hash_equals($expected, (string) $notification['signature_key']);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com')
            ->withBasicAuth((string) $this->serverKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 500, fn (\Throwable $exception) => $exception instanceof ConnectionException, throw: false);
    }
}
