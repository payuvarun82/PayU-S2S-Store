<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jsonResponse(['success' => false, 'message' => 'POST required'], 405);
}

$body = readJsonBody();
$txnid = trim($body['txnid'] ?? '');

if ($txnid === '') {
  jsonResponse(['success' => false, 'message' => 'txnid is required'], 422);
}

$client = new PayUClient($config);
$result = $client->verifyPayment($txnid);

jsonResponse([
  'success' => $result['http_code'] === 200,
  'txnid' => $txnid,
  'data' => $result['data'],
  'raw' => $result['raw'],
]);
