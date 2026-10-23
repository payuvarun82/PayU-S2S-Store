<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$config = require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/PaymentService.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$service = new PaymentService($config, $baseUrl);

$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) {
    $input = $_POST;
}

$txnid = trim((string) ($input['txnid'] ?? ''));
if ($txnid === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'txnid is required']);
    exit;
}

$response = $service->verify($txnid);
echo json_encode($response);
