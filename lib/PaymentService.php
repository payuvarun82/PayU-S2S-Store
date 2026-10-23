<?php

declare(strict_types=1);

require_once __DIR__ . '/PayUClient.php';
require_once __DIR__ . '/RedirectStore.php';
require_once __DIR__ . '/UpiIntent.php';
require_once __DIR__ . '/CustomerValidator.php';

final class PaymentService
{
    private PayUClient $client;
    private array $config;
    private RedirectStore $redirectStore;
    private string $baseUrl;

    public function __construct(array $config, ?string $baseUrl = null)
    {
        $this->config = $config;
        $this->client = new PayUClient($config);
        $this->redirectStore = new RedirectStore();
        $this->baseUrl = rtrim($baseUrl ?: ($config['app']['base_url'] ?? 'http://localhost:8080'), '/');
    }

    public function process(array $input, string $clientIp, string $userAgent): array
    {
        $paymode = $input['paymode'] ?? '';
        $txnid = $this->generateTxnId();

        $customer = CustomerValidator::validate($input);
        if (!$customer['valid']) {
            return [
                'success' => false,
                'message' => 'Please fix customer details',
                'errors' => $customer['errors'],
            ];
        }

        $base = [
            'txnid' => $txnid,
            'amount' => number_format((float) ($input['amount'] ?? 0), 2, '.', ''),
            'productinfo' => $input['productinfo'] ?? 'Order Payment',
            'firstname' => $customer['firstname'],
            'lastname' => $input['lastname'] ?? '',
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'surl' => $this->baseUrl . '/callback.php',
            'furl' => $this->baseUrl . '/callback.php',
            'udf1' => $input['udf1'] ?? '',
            'udf2' => $input['udf2'] ?? '',
            'udf3' => $input['udf3'] ?? '',
            'udf4' => $input['udf4'] ?? '',
            'udf5' => $input['udf5'] ?? '',
            'api_version' => '7',
            'txn_s2s_flow' => '4',
            's2s_client_ip' => $clientIp,
            's2s_device_info' => $userAgent ?: 'PayU-S2S-Store/1.0',
        ];

        if ((float) $base['amount'] < 1) {
            return ['success' => false, 'message' => 'Amount must be at least Rs. 1.00'];
        }

        $payload = match ($paymode) {
            'card' => $this->buildCardPayload($base, $input),
            'upi_collect' => $this->buildUpiCollectPayload($base, $input),
            'upi_intent' => $this->buildUpiIntentPayload($base, $input),
            'netbanking' => $this->buildNetBankingPayload($base, $input),
            default => null,
        };

        if ($payload === null) {
            return ['success' => false, 'message' => 'Invalid payment mode selected'];
        }

        $result = $this->client->initiatePayment($payload);
        $data = $result['data'];

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['error'] ?? 'Payment initiation failed',
                'txnid' => $txnid,
                'raw' => $result['body'],
            ];
        }

        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'Unexpected response from PayU',
                'txnid' => $txnid,
                'raw' => $result['body'],
            ];
        }

        $metaStatus = strtolower((string) ($data['metaData']['unmappedStatus'] ?? ''));
        $topStatus = strtolower((string) ($data['status'] ?? ''));

        if ($topStatus === 'failed' || $metaStatus === 'failure' || $metaStatus === 'failed') {
            return [
                'success' => false,
                'message' => strip_tags((string) ($data['metaData']['message'] ?? $data['message'] ?? 'Payment initiation failed')),
                'txnid' => $txnid,
                'error' => $data['error'] ?? null,
                'raw' => $result['body'],
            ];
        }

        return $this->formatS2SResponse($paymode, $txnid, $result, $input);
    }

    public function lookupBin(string $cardNumber): array
    {
        $digits = preg_replace('/\D/', '', $cardNumber);
        if (strlen($digits) < 6) {
            return ['success' => false, 'message' => 'Enter at least 6 card digits'];
        }

        $result = $this->client->getBinInfo($digits);
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['error'] ?? 'BIN lookup failed',
                'raw' => $result['body'],
            ];
        }

        $parsed = $this->parseBinResponse($result['data'], $digits);
        if ($parsed === null) {
            return [
                'success' => false,
                'message' => 'Could not identify card from BIN',
                'raw' => $result['body'],
            ];
        }

        return ['success' => true, 'bin' => $parsed];
    }

    public function validateVpa(string $vpa): array
    {
        if (!str_contains($vpa, '@')) {
            return ['success' => false, 'message' => 'Invalid VPA format'];
        }

        $result = $this->client->validateVpa($vpa);
        $decoded = $result['data'];

        if (!$result['success'] || !is_array($decoded)) {
            return ['success' => false, 'message' => 'VPA validation failed', 'raw' => $result['body']];
        }

        // PayU: isVPAValid=1 means valid; isVPAValid=0 means invalid (status=success is only API-level).
        $isValid = (int) ($decoded['isVPAValid'] ?? 0) === 1;

        return [
            'success' => $isValid,
            'message' => $isValid ? 'VPA is valid' : ($decoded['msg'] ?? 'VPA is invalid'),
            'data' => $decoded,
        ];
    }

    public function getBanks(): array
    {
        return $this->client->getNetBankingBanks();
    }

    public function verify(string $txnid): array
    {
        $result = $this->client->verifyPayment($txnid);
        return [
            'success' => $result['success'],
            'data' => $result['data'],
            'raw' => $result['body'],
        ];
    }

    public function getVerifiedTransaction(string $txnid): ?array
    {
        $result = $this->verify($txnid);
        if (!$result['success'] || !is_array($result['data'])) {
            return null;
        }

        $details = $result['data']['transaction_details'][$txnid] ?? null;

        return is_array($details) ? $details : null;
    }

    public function consumeBankRedirect(string $token): ?string
    {
        $entry = $this->redirectStore->consume($token);
        if ($entry === null || ($entry['type'] ?? '') !== 'html') {
            return null;
        }

        return (string) ($entry['html'] ?? '');
    }

    public function getUpiIntentPayload(string $token): ?array
    {
        $entry = $this->redirectStore->peek($token);
        if ($entry === null || ($entry['type'] ?? '') !== 'upi_intent') {
            return null;
        }

        return [
            'txnid'      => (string) ($entry['txnid']      ?? ''),
            'intent_url' => (string) ($entry['intent_url'] ?? ''),
            'amount'     => (string) ($entry['amount']     ?? ''),
            'upi_app'    => (string) ($entry['upi_app']    ?? 'generic'),
        ];
    }

    private function buildCardPayload(array $base, array $input): array
    {
        $category = strtolower((string) ($input['card_category'] ?? 'creditcard'));
        $isDebit = in_array($category, ['debitcard', 'dc', 'debit'], true);

        return array_merge($base, [
            'pg' => $isDebit ? 'DC' : 'CC',
            'bankcode' => $isDebit ? 'DC' : 'CC',
            'ccnum' => preg_replace('/\s+/', '', $input['ccnum'] ?? ''),
            'ccname' => $input['ccname'] ?? $base['firstname'],
            'ccvv' => $input['ccvv'] ?? '',
            'ccexpmon' => str_pad((string) ($input['ccexpmon'] ?? ''), 2, '0', STR_PAD_LEFT),
            'ccexpyr' => (string) ($input['ccexpyr'] ?? ''),
            'authentication_flow' => 'REDIRECT',
        ]);
    }

    private function buildUpiCollectPayload(array $base, array $input): array
    {
        return array_merge($base, [
            'pg' => 'UPI',
            'bankcode' => 'UPI',
            'vpa' => $input['vpa'] ?? '',
        ]);
    }

    private function buildUpiIntentPayload(array $base, array $input): array
    {
        $appMap = [
            'gpay' => 'googlepay',
            'phonepe' => 'phonepe',
            'paytm' => 'paytm',
            'bhim' => 'bhim',
            'amazonpay' => 'amazonpay',
            'cred' => 'cred',
            'whatsapp' => 'whatsapp',
            'generic' => 'genericintent',
        ];

        $app = $input['upi_app'] ?? 'generic';
        $upiAppName = $appMap[$app] ?? 'genericintent';

        return array_merge($base, [
            'pg' => 'UPI',
            'bankcode' => 'INTENT',
            'upiAppName' => $upiAppName,
        ]);
    }

    private function buildNetBankingPayload(array $base, array $input): array
    {
        return array_merge($base, [
            'pg' => 'NB',
            'bankcode' => $input['bankcode'] ?? 'TESTPGNB',
        ]);
    }

    private function formatS2SResponse(string $paymode, string $txnid, array $result, array $input = []): array
    {
        $data = $result['data'];
        $status = strtolower((string) ($data['metaData']['unmappedStatus'] ?? 'pending'));

        $response = [
            'success' => !in_array($status, ['failure', 'failed'], true),
            'txnid' => $txnid,
            'paymode' => $paymode,
            'status' => $status ?: 'pending',
            'http_code' => $result['http_code'],
            'meta' => $data['metaData'] ?? null,
            'result' => $data['result'] ?? null,
        ];

        if (!$response['success']) {
            $response['message'] = strip_tags((string) ($data['metaData']['message'] ?? 'Transaction failed'));
            return $response;
        }

        // UPI Intent: prefer intentURIData deeplink (upi://pay?) — not acsTemplate.
        if ($paymode === 'upi_intent') {
            $upiApp = (string) ($input['upi_app'] ?? 'generic');
            $intentUrl = UpiIntent::buildUrl($data, $upiApp);
            if ($intentUrl) {
                // Rewrite the `url` param inside the intent so that after payment
                // the UPI app (GPay / PhonePe / Paytm / UPI Simulator) redirects
                // directly to our confirming page instead of the store homepage.
                $confirmingUrl = $this->baseUrl . '/payment-confirming.php?txnid=' . urlencode($txnid);
                $intentUrl = $this->rewriteIntentReturnUrl($intentUrl, $confirmingUrl);

                $token = $this->redirectStore->putMeta([
                    'type' => 'upi_intent',
                    'txnid' => $txnid,
                    'intent_url' => $intentUrl,
                    'upi_app' => $upiApp,
                    'amount' => $data['result']['amount'] ?? null,
                ], 600);

                $response['action'] = 'redirect';
                $response['redirect_url'] = $this->baseUrl . '/payment-upi-intent.php?txnid='
                    . urlencode($txnid) . '&token=' . $token;
                return $response;
            }
        }

        if ($paymode !== 'upi_intent' && ($acsRedirect = $this->buildAcsRedirect($data))) {
            $response['action'] = 'redirect';
            $response['redirect_url'] = $acsRedirect;
            return $response;
        }

        if ($paymode === 'upi_collect') {
            $response['action'] = 'waiting';
            $response['redirect_url'] = $this->baseUrl . '/payment-waiting.php?txnid='
                . urlencode($txnid) . '&mode=collect';
            return $response;
        }

        if ($paymode === 'netbanking') {
            $postRedirect = $this->buildFormPostRedirect($data);
            if ($postRedirect) {
                $response['action'] = 'redirect';
                $response['redirect_url'] = $postRedirect;
                return $response;
            }
        }

        $response['message'] = strip_tags((string) ($data['metaData']['message'] ?? 'Payment initiated'));
        return $response;
    }

    private function buildAcsRedirect(array $data): ?string
    {
        $template = $data['result']['acsTemplate'] ?? null;
        if (!$template) {
            return null;
        }

        $html = base64_decode((string) $template, true);
        if ($html === false || trim($html) === '') {
            return null;
        }

        $token = $this->redirectStore->putHtml($html, 600);
        return $this->baseUrl . '/payment-redirect.php?token=' . $token;
    }

    private function buildFormPostRedirect(array $data): ?string
    {
        $uri = $data['result']['post_uri'] ?? null;
        $postData = $data['result']['post_data'] ?? null;

        if (!$uri || !is_array($postData)) {
            return null;
        }

        $fields = '';
        foreach ($postData as $key => $value) {
            $fields .= '<input type="hidden" name="' . htmlspecialchars((string) $key, ENT_QUOTES) . '" value="'
                . htmlspecialchars((string) $value, ENT_QUOTES) . '">';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting to bank…</title></head><body>'
            . '<p style="font-family:system-ui;text-align:center;margin-top:3rem">Redirecting to your bank…</p>'
            . '<form id="payu_nb" method="POST" action="' . htmlspecialchars((string) $uri, ENT_QUOTES) . '">'
            . $fields
            . '</form><script>document.getElementById("payu_nb").submit();</script></body></html>';

        $token = $this->redirectStore->putHtml($html, 600);
        return $this->baseUrl . '/payment-redirect.php?token=' . $token;
    }

    private function parseBinResponse(?array $data, string $digits): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        if (isset($data['issuingBank']) || isset($data['cardType']) || isset($data['cardCategory'])) {
            return $this->normalizeDomesticCheckResponse($data, $digits);
        }

        $binKey = substr($digits, 0, 6);
        $entry = null;

        if (isset($data['bins_data']) && is_array($data['bins_data'])) {
            foreach ($data['bins_data'] as $key => $bin) {
                if (is_array($bin)) {
                    $entry = $bin;
                    $binKey = (string) $key;
                    break;
                }
            }
        } elseif (isset($data['data']['bins_data']) && is_array($data['data']['bins_data'])) {
            foreach ($data['data']['bins_data'] as $key => $bin) {
                if (is_array($bin)) {
                    $entry = $bin;
                    $binKey = (string) $key;
                    break;
                }
            }
        } elseif (isset($data['issuing_bank']) || isset($data['card_category'])) {
            $entry = $data;
        }

        if (!is_array($entry)) {
            return null;
        }

        $scheme = strtoupper((string) ($entry['card_type'] ?? $entry['cardType'] ?? 'CARD'));
        $category = strtolower((string) ($entry['card_category'] ?? $entry['category'] ?? 'creditcard'));
        $isDebit = in_array($category, ['debitcard', 'dc', 'debit'], true);

        return $this->buildBinResult($binKey, $scheme, $isDebit, (string) ($entry['issuing_bank'] ?? $entry['issuingBank'] ?? 'Unknown Bank'), true);
    }

    private function normalizeDomesticCheckResponse(array $data, string $digits): array
    {
        $scheme = strtoupper((string) ($data['cardType'] ?? 'CARD'));
        $category = strtoupper((string) ($data['cardCategory'] ?? 'CC'));
        $isDebit = in_array($category, ['DC', 'DEBIT', 'DEBITCARD'], true);
        $isDomestic = strtoupper((string) ($data['isDomestic'] ?? 'Y')) === 'Y';

        return $this->buildBinResult(
            substr($digits, 0, 6),
            $scheme,
            $isDebit,
            (string) ($data['issuingBank'] ?? 'Unknown Bank'),
            $isDomestic
        );
    }

    private function buildBinResult(string $bin, string $scheme, bool $isDebit, string $bank, bool $isDomestic): array
    {
        $schemeLabels = [
            'VISA' => 'Visa',
            'MAST' => 'Mastercard',
            'MASTERCARD' => 'Mastercard',
            'AMEX' => 'American Express',
            'DINR' => 'Diners Club',
            'RUPAY' => 'RuPay',
        ];

        return [
            'bin' => $bin,
            'scheme' => $scheme,
            'scheme_label' => $schemeLabels[$scheme] ?? $scheme,
            'card_category' => $isDebit ? 'debitcard' : 'creditcard',
            'card_type_label' => $isDebit ? 'Debit Card' : 'Credit Card',
            'issuing_bank' => $bank,
            'is_domestic' => $isDomestic,
        ];
    }

    private function generateTxnId(): string
    {
        return 'TXN' . date('YmdHis') . random_int(1000, 9999);
    }

    /**
     * Replace (or add) the `url` query parameter inside a UPI intent URL.
     *
     * Handles both:
     *   upi://pay?pa=...&url=...
     *   intent://pay?pa=...&url=...#Intent;scheme=upi;package=...;end
     *
     * After payment UPI apps (GPay, PhonePe, Paytm, UPI Simulator) redirect
     * the browser to the `url` value. Pointing it to payment-confirming.php
     * ensures the browser lands on our polling/confirming page automatically.
     */
    private function rewriteIntentReturnUrl(string $intentUrl, string $returnUrl): string
    {
        // Split off Android #Intent fragment if present
        $fragment = '';
        if (str_contains($intentUrl, '#')) {
            [$intentUrl, $frag] = explode('#', $intentUrl, 2);
            $fragment = '#' . $frag;
        }

        $encoded = rawurlencode($returnUrl);

        // Replace existing url= param in-place without touching any other params.
        // Using parse_str + http_build_query would re-encode all params (e.g.
        // pa=payutest%40hdfcbank → pa=payutest%2540hdfcbank on some PHP versions).
        // Regex replacement leaves every other byte intact.
        if (preg_match('/([?&])url=[^&]*/i', $intentUrl)) {
            $intentUrl = preg_replace('/([?&])url=[^&]*/i', '$1url=' . $encoded, $intentUrl);
        } else {
            $sep = str_contains($intentUrl, '?') ? '&' : '?';
            $intentUrl .= $sep . 'url=' . $encoded;
        }

        return $intentUrl . $fragment;
    }
}
