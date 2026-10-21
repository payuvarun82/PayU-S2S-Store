<?php

require_once __DIR__ . '/PayUHash.php';

class PayUClient
{
  private array $config;

  public function __construct(array $config)
  {
    $this->config = $config;
  }

  public function paymentEndpoint(): string
  {
    return $this->config['environment'] === 'production'
      ? 'https://secure.payu.in/_payment'
      : 'https://test.payu.in/_payment';
  }

  public function postServiceEndpoint(): string
  {
    return $this->config['environment'] === 'production'
      ? 'https://info.payu.in/merchant/postservice?form=2'
      : 'https://test.payu.in/merchant/postservice?form=2';
  }

  public function getKey(): string
  {
    return $this->config['merchant_key'];
  }

  public function getSalt(): string
  {
    return $this->config['merchant_salt'];
  }

  public function getBaseUrl(): string
  {
    return rtrim($this->config['base_url'], '/');
  }

  public function generateTxnId(): string
  {
    return 'TXN' . strtoupper(bin2hex(random_bytes(6))) . time();
  }

  public function buildBasePayment(array $customer, string $amount, string $productinfo, string $txnid): array
  {
    return [
      'key' => $this->getKey(),
      'txnid' => $txnid,
      'amount' => number_format((float) $amount, 2, '.', ''),
      'productinfo' => $productinfo,
      'firstname' => $customer['firstname'] ?? 'Customer',
      'lastname' => $customer['lastname'] ?? '',
      'email' => $customer['email'] ?? 'test@example.com',
      'phone' => $customer['phone'] ?? '9876543210',
      'surl' => $this->getBaseUrl() . '/callback.php?status=success',
      'furl' => $this->getBaseUrl() . '/callback.php?status=failure',
      'notifyurl' => $this->getBaseUrl() . '/callback.php?status=notify',
      'udf1' => $customer['udf1'] ?? '',
      'udf2' => $customer['udf2'] ?? '',
      'udf3' => $customer['udf3'] ?? '',
      'udf4' => $customer['udf4'] ?? '',
      'udf5' => $customer['udf5'] ?? '',
    ];
  }

  public function addS2sParams(array $params, ?string $clientIp = null, ?string $deviceInfo = null): array
  {
    $params['txn_s2s_flow'] = '4';
    $params['s2s_client_ip'] = $clientIp ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $params['s2s_device_info'] = $deviceInfo ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 PayU-S2S-Demo/1.0');
    return $params;
  }

  public function signPayment(array $params): array
  {
    $params['hash'] = PayUHash::paymentHash($params, $this->getSalt());
    return $params;
  }

  public function postPayment(array $params): array
  {
    $signed = $this->signPayment($params);
    return $this->curlPost($this->paymentEndpoint(), $signed);
  }

  public function validateVpa(string $vpa): array
  {
    $key = $this->getKey();
    $command = 'validateVPA';
    $hash = PayUHash::commandHash($key, $command, $vpa, $this->getSalt());

    $response = $this->curlPost($this->postServiceEndpoint(), [
      'key' => $key,
      'command' => $command,
      'var1' => $vpa,
      'hash' => $hash,
    ]);

    $decoded = json_decode($response['body'], true);
    return [
      'http_code' => $response['http_code'],
      'raw' => $response['body'],
      'data' => is_array($decoded) ? $decoded : null,
    ];
  }

  public function verifyPayment(string $txnid): array
  {
    $key = $this->getKey();
    $command = 'verify_payment';
    $hash = PayUHash::commandHash($key, $command, $txnid, $this->getSalt());

    $response = $this->curlPost($this->postServiceEndpoint(), [
      'key' => $key,
      'command' => $command,
      'var1' => $txnid,
      'hash' => $hash,
    ]);

    $decoded = json_decode($response['body'], true);
    return [
      'http_code' => $response['http_code'],
      'raw' => $response['body'],
      'data' => is_array($decoded) ? $decoded : null,
    ];
  }

  public function parsePaymentResponse(string $body): array
  {
    $json = json_decode($body, true);
    if (is_array($json)) {
      return $json;
    }
    return ['raw' => $body];
  }

  private function curlPost(string $url, array $data): array
  {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($data),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
      ],
      CURLOPT_TIMEOUT => 60,
      CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
      return ['http_code' => 500, 'body' => '', 'error' => $error ?: 'cURL request failed'];
    }

    return ['http_code' => $httpCode, 'body' => $body, 'error' => null];
  }
}
