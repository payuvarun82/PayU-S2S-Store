<?php

declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$env      = trim($input['env']      ?? '');
$testKey  = trim($input['test_key']  ?? '');
$testSalt = trim($input['test_salt'] ?? '');
$prodKey  = trim($input['prod_key']  ?? '');
$prodSalt = trim($input['prod_salt'] ?? '');

if (!in_array($env, ['test', 'production'], true)) {
    echo json_encode(['success' => false, 'message' => 'env must be test or production']);
    exit;
}
if ($env === 'test' && ($testKey === '' || $testSalt === '')) {
    echo json_encode(['success' => false, 'message' => 'Test key and salt are required when using test env']);
    exit;
}
if ($env === 'production' && ($prodKey === '' || $prodSalt === '')) {
    echo json_encode(['success' => false, 'message' => 'Production key and salt are required when using production env']);
    exit;
}

$envFile = dirname(__DIR__) . '/.env';
$content = "PAYU_ENV={$env}\n"
         . "\n# Test credentials\n"
         . "PAYU_TEST_KEY={$testKey}\n"
         . "PAYU_TEST_SALT={$testSalt}\n"
         . "\n# Production credentials\n"
         . "PAYU_PROD_KEY={$prodKey}\n"
         . "PAYU_PROD_SALT={$prodSalt}\n";

if (file_put_contents($envFile, $content) === false) {
    echo json_encode(['success' => false, 'message' => 'Could not write .env file']);
    exit;
}

echo json_encode(['success' => true, 'env' => $env]);
