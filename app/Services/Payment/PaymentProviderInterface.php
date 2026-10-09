<?php
/**
 * Payment Provider Interface
 */

namespace WorkShift\Services\Payment;

interface PaymentProviderInterface
{
    /**
     * Create checkout session
     * @param array $invoice
     * @param array $options
     * @return array ['checkout_url' => string, 'reference_number' => string]
     */
    public function createCheckoutSession(array $invoice, array $options = []): array;

    /**
     * Verify payment status
     * @param string $referenceNumber
     * @return array ['success' => bool, 'amount' => float, 'reference' => string, 'raw' => mixed]
     */
    public function verifyPayment(string $referenceNumber): array;

    /**
     * Handle incoming webhook
     * @param array $payload
     * @param string $signature
     * @return array ['success' => bool, 'invoice_id' => int, 'reference' => string]
     */
    public function handleWebhook(array $payload, string $signature): array;
}
