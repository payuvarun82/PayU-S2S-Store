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

$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (str_contains($clientIp, ',')) {
    $clientIp = trim(explode(',', $clientIp)[0]);
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'PayU-S2S-Store/1.0';

try {
    $response = $service->process($input, $clientIp, $userAgent);
    http_response_code($response['success'] ? 200 : 422);
    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
    ]);
}
