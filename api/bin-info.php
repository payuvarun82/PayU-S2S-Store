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

$service = new PaymentService($config);

$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) {
    $input = $_POST;
}

$cardNumber = trim((string) ($input['card_number'] ?? $input['ccnum'] ?? ''));
$response = $service->lookupBin($cardNumber);

http_response_code($response['success'] ? 200 : 422);
echo json_encode($response);
