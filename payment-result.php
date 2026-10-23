<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/PaymentService.php';

$txnid = trim((string) ($_GET['txnid'] ?? ''));
if ($txnid === '') {
    header('Location: /');
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$service = new PaymentService($config, $baseUrl);
$txn = $service->getVerifiedTransaction($txnid);

$appName = htmlspecialchars($config['app']['name'] ?? 'PayU Store', ENT_QUOTES, 'UTF-8');
$status = strtolower((string) ($txn['status'] ?? $_GET['status'] ?? 'unknown'));
$mihpayid = htmlspecialchars((string) ($txn['mihpayid'] ?? $_GET['mihpayid'] ?? ''), ENT_QUOTES, 'UTF-8');
$amount = htmlspecialchars((string) ($txn['amt'] ?? $txn['transaction_amount'] ?? $_GET['amount'] ?? ''), ENT_QUOTES, 'UTF-8');
$mode = htmlspecialchars((string) ($txn['mode'] ?? ''), ENT_QUOTES, 'UTF-8');
$txnidSafe = htmlspecialchars($txnid, ENT_QUOTES, 'UTF-8');

$isSuccess = in_array($status, ['success', 'captured'], true);
$isPending = in_array($status, ['pending', 'auth'], true);
$iconClass = $isSuccess ? 'success' : ($isPending ? 'pending' : 'failed');
$icon = $isSuccess ? '✓' : ($isPending ? '⏳' : '✕');
$title = $isSuccess ? 'Payment successful' : ($isPending ? 'Payment pending' : 'Payment failed');
$statusMessage = $txn
    ? ($isSuccess ? 'Your order has been confirmed. Thank you for shopping with us!' : ($isPending ? 'We are still processing your payment.' : 'Something went wrong. Please try again.'))
    : 'Could not fetch transaction details from PayU.';
$txnid = $txnidSafe;
$hashValid = null;
$verificationNote = $txn ? 'Verified via PayU Verify Payment API' : null;

@file_put_contents(
    __DIR__ . '/storage/callbacks.log',
    date('c') . ' | VERIFY | ' . json_encode(['txnid' => $txnid, 'txn' => $txn]) . PHP_EOL,
    FILE_APPEND
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES) ?> — <?= $appName ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'checkout'; require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/payment-result-view.php'; ?>
</body>
</html>
