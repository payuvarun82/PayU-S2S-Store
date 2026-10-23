<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/PaymentService.php';

$service = new PaymentService($config);
$banks = $service->getBanks();

echo json_encode([
    'success' => true,
    'banks' => $banks,
]);
