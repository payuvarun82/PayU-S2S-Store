<?php

declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$envFile = dirname(__DIR__) . '/.env';

// Read existing .env to preserve APP_BASE_URL
$existingEnv = [];
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $existingEnv[$k] = $v;
    }
}

$content = "PAYU_ENV=test\n"
         . "\n# Test credentials\n"
         . "PAYU_TEST_KEY=\n"
         . "PAYU_TEST_SALT=\n"
         . "\n# Production credentials\n"
         . "PAYU_PROD_KEY=\n"
         . "PAYU_PROD_SALT=\n";

if (file_put_contents($envFile, $content) === false) {
    echo json_encode(['success' => false, 'message' => 'Could not write .env file']);
    exit;
}

echo json_encode(['success' => true]);
