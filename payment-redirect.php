<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/PaymentService.php';

$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    http_response_code(400);
    echo 'Missing redirect token.';
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$service = new PaymentService($config, $baseUrl);
$html = $service->consumeBankRedirect($token);

if ($html === null || trim($html) === '') {
    http_response_code(410);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Session expired</title>'
        . '<link rel="stylesheet" href="/assets/css/app.css"></head><body class="waiting-page"><div class="waiting-card">'
        . '<h1>Payment session expired</h1>'
        . '<p>The bank redirect link is no longer valid. Please start checkout again.</p>'
        . '<a class="btn btn-primary" href="/">Back to store</a></div></body></html>';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
