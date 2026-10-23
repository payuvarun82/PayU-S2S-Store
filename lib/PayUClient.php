<?php

declare(strict_types=1);

require_once __DIR__ . '/PayUHash.php';
require_once __DIR__ . '/BankIcons.php';

final class PayUClient
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initiatePayment(array $payload): array
    {
        $payload['key'] = $this->config['payu']['key'];
        $payload['hash'] = PayUHash::generatePaymentHash($payload, $this->config['payu']['salt']);

        return $this->postForm($this->config['payu']['payment_url'], $payload);
    }

    public function validateVpa(string $vpa): array
    {
        $key = $this->config['payu']['key'];
        $command = 'validateVPA';
        $hash = PayUHash::generateCommandHash($key, $command, $vpa, $this->config['payu']['salt']);

        return $this->postForm($this->config['payu']['postservice_url'], [
            'key' => $key,
            'command' => $command,
            'var1' => $vpa,
            'hash' => $hash,
        ]);
    }

    public function verifyPayment(string $txnid): array
    {
        $key = $this->config['payu']['key'];
        $command = 'verify_payment';
        $hash = PayUHash::generateCommandHash($key, $command, $txnid, $this->config['payu']['salt']);

        return $this->postForm($this->config['payu']['postservice_url'], [
            'key' => $key,
            'command' => $command,
            'var1' => $txnid,
            'hash' => $hash,
        ]);
    }

    public function getBinInfo(string $cardNumber): array
    {
        $digits = preg_replace('/\D/', '', $cardNumber);

        if (strlen($digits) < 6) {
            return [
                'success' => false,
                'http_code' => 400,
                'error' => 'Card number must be at least 6 digits',
                'body' => null,
                'data' => null,
            ];
        }

        $key = $this->config['payu']['key'];
        $command = 'check_isDomestic';
        $var1 = $digits;
        $hash = PayUHash::generateCommandHash($key, $command, $var1, $this->config['payu']['salt']);

        return $this->postForm($this->config['payu']['postservice_url'], [
            'key' => $key,
            'command' => $command,
            'var1' => $var1,
            'hash' => $hash,
        ]);
    }

    public function getNetBankingBanks(): array
    {
        $key = $this->config['payu']['key'];
        $command = 'getNetbankingStatus';
        $var1 = 'default';
        $hash = PayUHash::generateCommandHash($key, $command, $var1, $this->config['payu']['salt']);

        $result = $this->postForm($this->config['payu']['postservice_url'], [
            'key' => $key,
            'command' => $command,
            'var1' => $var1,
            'hash' => $hash,
        ]);

        if (!$result['success']) {
            return $this->defaultBanks();
        }

        $decoded = json_decode($result['body'], true);
        if (!is_array($decoded)) {
            return $this->defaultBanks();
        }

        $banks = [];
        foreach ($decoded as $code => $bank) {
            if (!is_array($bank) || ($bank['mode'] ?? '') !== 'NB') {
                continue;
            }

            $bankCode = strtoupper((string) ($bank['ibibo_code'] ?? $code));
            if (str_ends_with($bankCode, 'TPV')) {
                continue;
            }

            $title = (string) ($bank['title'] ?? $code);
            $banks[] = [
                'code' => $bankCode,
                'name' => $title,
                'active' => (int) ($bank['up_status'] ?? 0) === 1,
                'icon_url' => BankIcons::url($bankCode, $title),
            ];
        }

        usort($banks, static function (array $a, array $b): int {
            if ($a['active'] !== $b['active']) {
                return $b['active'] <=> $a['active'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $banks ?: $this->defaultBanks();
    }

    private function defaultBanks(): array
    {
        return [
            [
                'code' => 'TESTPGNB',
                'name' => 'Test Net Banking',
                'active' => true,
                'icon_url' => BankIcons::url('TESTPGNB', 'Test Net Banking'),
            ],
            [
                'code' => 'SBIB',
                'name' => 'State Bank of India',
                'active' => true,
                'icon_url' => BankIcons::url('SBIB', 'State Bank of India'),
            ],
            [
                'code' => 'AXIB',
                'name' => 'AXIS Bank NetBanking',
                'active' => true,
                'icon_url' => BankIcons::url('AXIB', 'AXIS Bank'),
            ],
            [
                'code' => 'HDFB',
                'name' => 'HDFC Bank',
                'active' => true,
                'icon_url' => BankIcons::url('HDFB', 'HDFC Bank'),
            ],
            [
                'code' => 'ICIB',
                'name' => 'ICICI Bank',
                'active' => true,
                'icon_url' => BankIcons::url('ICIB', 'ICICI Bank'),
            ],
        ];
    }

    private function postForm(string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => $error,
                'body' => null,
                'data' => null,
            ];
        }

        $decoded = json_decode((string) $body, true);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'error' => null,
            'body' => $body,
            'data' => is_array($decoded) ? $decoded : null,
        ];
    }
}
