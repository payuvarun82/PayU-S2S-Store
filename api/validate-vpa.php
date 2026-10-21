<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jsonResponse(['success' => false, 'message' => 'POST required'], 405);
}

$body = readJsonBody();
$vpa = trim($body['vpa'] ?? '');

if ($vpa === '') {
  jsonResponse(['success' => false, 'message' => 'VPA is required'], 422);
}

$client = new PayUClient($config);
$result = $client->validateVpa($vpa);

$data = $result['data'];
$isValid = false;
$payerName = null;

if (is_array($data)) {
  $status = strtolower((string) ($data['status'] ?? $data['isVPAValid'] ?? ''));
  $isValid = in_array($status, ['1', 'true', 'success', 'valid', 'vpa valid'], true)
    || ($data['isVPAValid'] ?? 0) == 1;
  $payerName = $data['payerAccountName'] ?? $data['name'] ?? null;
}

jsonResponse([
  'success' => $result['http_code'] === 200,
  'valid' => $isValid,
  'payer_name' => $payerName,
  'response' => $data,
  'raw' => $result['raw'],
]);
