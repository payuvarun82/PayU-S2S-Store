<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jsonResponse(['success' => false, 'message' => 'POST required'], 405);
}

$body = readJsonBody();
$mode = strtolower(trim($body['payment_mode'] ?? ''));
$amount = $body['amount'] ?? '10.00';
$productinfo = trim($body['productinfo'] ?? 'Store Purchase');
$customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];

$client = new PayUClient($config);
$txnid = $client->generateTxnId();
$params = $client->buildBasePayment($customer, (string) $amount, $productinfo, $txnid);
$params = $client->addS2sParams(
  $params,
  $body['client_ip'] ?? null,
  $body['device_info'] ?? null
);

switch ($mode) {
  case 'card':
    $params = buildCardPayment($params, $body, $client);
    break;
  case 'upi_collect':
    $params = buildUpiCollectPayment($params, $body);
    break;
  case 'upi_intent':
    $params = buildUpiIntentPayment($params, $body);
    break;
  case 'netbanking':
    $params = buildNetBankingPayment($params, $body);
    break;
  default:
    jsonResponse(['success' => false, 'message' => 'Invalid payment_mode. Use: card, upi_collect, upi_intent, netbanking'], 422);
}

$response = $client->postPayment($params);
$parsed = $client->parsePaymentResponse($response['body']);

jsonResponse([
  'success' => $response['http_code'] === 200 && empty($response['error']),
  'txnid' => $txnid,
  'payment_mode' => $mode,
  'http_code' => $response['http_code'],
  'payu_response' => $parsed,
  'raw' => $response['body'],
  'error' => $response['error'],
  'next_action' => detectNextAction($mode, $parsed),
]);

function buildCardPayment(array $params, array $body, PayUClient $client): array
{
  $card = is_array($body['card'] ?? null) ? $body['card'] : [];

  $ccnum = preg_replace('/\s+/', '', $card['number'] ?? '');
  $ccvv = $card['cvv'] ?? '';
  $ccexpmon = $card['exp_month'] ?? '';
  $ccexpyr = $card['exp_year'] ?? '';
  $ccname = trim($card['name'] ?? 'Card Holder');
  $bankcode = $card['bankcode'] ?? 'CC';

  if ($ccnum === '' || $ccvv === '' || $ccexpmon === '' || $ccexpyr === '') {
    jsonResponse(['success' => false, 'message' => 'Card number, CVV, expiry month and year are required'], 422);
  }

  $params['pg'] = 'CC';
  $params['bankcode'] = $bankcode;
  $params['ccnum'] = $ccnum;
  $params['ccname'] = $ccname;
  $params['ccvv'] = $ccvv;
  $params['ccexpmon'] = str_pad((string) $ccexpmon, 2, '0', STR_PAD_LEFT);
  $params['ccexpyr'] = (string) $ccexpyr;
  $params['authentication_flow'] = 'REDIRECT';
  $params['termUrl'] = $client->getBaseUrl() . '/callback.php?status=term';

  return $params;
}

function buildUpiCollectPayment(array $params, array $body): array
{
  $vpa = trim($body['vpa'] ?? '');
  if ($vpa === '') {
    jsonResponse(['success' => false, 'message' => 'UPI VPA is required for UPI Collect'], 422);
  }

  $params['pg'] = 'UPI';
  $params['bankcode'] = 'UPI';
  $params['vpa'] = $vpa;

  return $params;
}

function buildUpiIntentPayment(array $params, array $body): array
{
  $params['pg'] = 'UPI';
  $params['bankcode'] = 'INTENT';
  $params['upiAppName'] = $body['upi_app'] ?? 'qr';

  return $params;
}

function buildNetBankingPayment(array $params, array $body): array
{
  $bankcode = trim($body['bankcode'] ?? 'TESTPGNB');

  if ($bankcode === '') {
    jsonResponse(['success' => false, 'message' => 'bankcode is required for Net Banking'], 422);
  }

  $params['pg'] = 'NB';
  $params['bankcode'] = $bankcode;

  return $params;
}

function detectNextAction(string $mode, array $parsed): array
{
  $meta = $parsed['metaData'] ?? [];
  $result = $parsed['result'] ?? $parsed;
  $status = strtolower((string) ($meta['unmappedStatus'] ?? $result['status'] ?? ''));

  $action = [
    'type' => 'poll',
    'message' => 'Payment initiated. Poll verify-payment or wait for callback.',
    'status' => $status,
  ];

  if ($mode === 'upi_intent') {
    $intentData = $result['intentURIData'] ?? $result['intentUriData'] ?? null;
    if ($intentData) {
      $deeplink = str_starts_with($intentData, 'upi://') ? $intentData : 'upi://pay?' . $intentData;
      $action = [
        'type' => 'upi_qr',
        'deeplink' => $deeplink,
        'message' => 'Scan QR or open UPI app to complete payment.',
        'status' => $status,
      ];
    }
  }

  $acsUrl = $result['acsUrl'] ?? $result['post_uri'] ?? $result['redirectUrl'] ?? null;
  if ($acsUrl) {
    $action = [
      'type' => 'redirect',
      'url' => $acsUrl,
      'message' => 'Redirect customer to bank ACS for 3DS authentication.',
      'status' => $status,
    ];
  }

  $formPost = $result['post_data'] ?? $result['formPost'] ?? null;
  if (is_array($formPost) && !empty($formPost)) {
    $action = [
      'type' => 'form_post',
      'url' => $result['post_uri'] ?? $acsUrl,
      'fields' => $formPost,
      'message' => 'Submit ACS form to complete card authentication.',
      'status' => $status,
    ];
  }

  if ($mode === 'netbanking') {
    $nbUrl = $result['redirectUrl'] ?? $result['bankUrl'] ?? $acsUrl;
    if ($nbUrl) {
      $action = [
        'type' => 'redirect',
        'url' => $nbUrl,
        'message' => 'Redirect customer to net banking page.',
        'status' => $status,
      ];
    }
  }

  if (in_array($status, ['success', 'captured'], true)) {
    $action['type'] = 'success';
    $action['message'] = 'Payment successful.';
  }

  if (in_array($status, ['failure', 'failed', 'bounced'], true)) {
    $action['type'] = 'failure';
    $action['message'] = $meta['message'] ?? 'Payment failed.';
  }

  return $action;
}
