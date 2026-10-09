<?php
/**
 * Mock Payment Provider (Simulates in-app checkout)
 */

namespace WorkShift\Services\Payment;

class MockPaymentProvider implements PaymentProviderInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function createCheckoutSession(array $invoice, array $options = []): array
    {
        $ref = 'MOCK-PAY-' . strtoupper(bin2hex(random_bytes(6)));
        $token = $options['portal_token'] ?? '';
        $checkoutUrl = "/portal/{$token}/pay/{$invoice['id']}?ref={$ref}";

        return [
            'checkout_url' => $checkoutUrl,
            'reference_number' => $ref,
        ];
    }

    public function verifyPayment(string $referenceNumber): array
    {
        return [
            'success' => true,
            'reference' => $referenceNumber,
            'status' => 'completed',
            'raw' => ['provider' => 'mock', 'time' => time()],
        ];
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        // Simulated webhook verification
        $invoiceId = (int)($payload['invoice_id'] ?? 0);
        $reference = $payload['reference'] ?? 'MOCK-WH-' . time();

        return [
            'success' => true,
            'invoice_id' => $invoiceId,
            'reference' => $reference,
        ];
    }
}
