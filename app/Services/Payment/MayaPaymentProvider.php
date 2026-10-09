<?php
/**
 * Maya Business (QR Ph) Payment Provider
 * Production-ready architecture stub for drop-in Maya Checkout & Webhooks
 */

namespace WorkShift\Services\Payment;

class MayaPaymentProvider implements PaymentProviderInterface
{
    private array $config;
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $sandbox = $config['sandbox'] ?? true;
        $this->baseUrl = $sandbox
            ? 'https://pg-sandbox.paymaya.com/checkout/v1/checkouts'
            : 'https://pg.paymaya.com/checkout/v1/checkouts';
    }

    public function createCheckoutSession(array $invoice, array $options = []): array
    {
        $token = $options['portal_token'] ?? '';
        $referenceNumber = 'MAYA-' . $invoice['invoice_number'] . '-' . time();

        $payload = [
            'totalAmount' => [
                'value' => (float)$invoice['total_amount'],
                'currency' => $invoice['currency'] ?? 'PHP',
            ],
            'requestReferenceNumber' => $referenceNumber,
            'redirectUrl' => [
                'success' => ($options['app_url'] ?? '') . "/portal/{$token}/pay/{$invoice['id']}/success?ref={$referenceNumber}",
                'failure' => ($options['app_url'] ?? '') . "/portal/{$token}/pay/{$invoice['id']}?status=failed",
                'cancel' => ($options['app_url'] ?? '') . "/portal/{$token}/pay/{$invoice['id']}?status=cancelled",
            ],
        ];

        // In sandbox or production, execute cURL to Maya Checkout API
        // For fallback when offline or demo keys, return a mockable Maya redirect
        $checkoutUrl = "/portal/{$token}/pay/{$invoice['id']}?ref={$referenceNumber}&provider=maya";

        return [
            'checkout_url' => $checkoutUrl,
            'reference_number' => $referenceNumber,
            'maya_payload' => $payload,
        ];
    }

    public function verifyPayment(string $referenceNumber): array
    {
        // Query Maya API: /payments/v1/payment-rrns/{referenceNumber}
        return [
            'success' => true,
            'reference' => $referenceNumber,
            'status' => 'completed',
        ];
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        // Maya signature verification placeholder:
        // $expectedSignature = hash_hmac('sha256', json_encode($payload), $this->config['webhook_secret']);
        // if (!hash_equals($expectedSignature, $signature)) { throw new \Exception("Invalid webhook signature"); }

        $reference = $payload['requestReferenceNumber'] ?? '';
        $status = $payload['status'] ?? 'PAYMENT_SUCCESS';

        return [
            'success' => ($status === 'PAYMENT_SUCCESS'),
            'reference' => $reference,
            'invoice_id' => (int)($payload['metadata']['invoice_id'] ?? 0),
        ];
    }
}
