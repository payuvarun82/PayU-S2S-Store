<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/PayUHash.php';

$payload = file_get_contents('php://input');
$params = [];

if ($payload) {
    parse_str($payload, $params);
}

if (!$params) {
    $params = $_POST;
}

$logLine = date('c') . ' | WEBHOOK | ' . json_encode($params) . PHP_EOL;
file_put_contents(__DIR__ . '/storage/webhooks.log', $logLine, FILE_APPEND);

$hashValid = PayUHash::verifyResponseHash($params, $config['payu']['salt']);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'received' => true,
    'hash_valid' => $hashValid,
    'status' => $params['status'] ?? null,
    'txnid' => $params['txnid'] ?? null,
]);
