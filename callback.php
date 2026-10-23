<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/PayUHash.php';

$params = array_merge($_GET, $_POST);
$txnid = trim((string) ($params['txnid'] ?? ''));

// UPI intent/collect polling lands here without PayU hash — use verify API instead.
if ($txnid !== '' && !isset($params['hash'])) {
    header('Location: /payment-result.php?txnid=' . rawurlencode($txnid));
    exit;
}

$status = strtolower((string) ($params['status'] ?? 'unknown'));
$hashValid = isset($params['hash']) ? PayUHash::verifyResponseHash($params, $config['payu']['salt']) : null;

$logLine = date('c') . ' | ' . json_encode($params) . PHP_EOL;
@file_put_contents(__DIR__ . '/storage/callbacks.log', $logLine, FILE_APPEND);

$txnidSafe = htmlspecialchars($txnid, ENT_QUOTES, 'UTF-8');
$mihpayid = htmlspecialchars((string) ($params['mihpayid'] ?? ''), ENT_QUOTES, 'UTF-8');
$amount = htmlspecialchars((string) ($params['amount'] ?? ''), ENT_QUOTES, 'UTF-8');
$mode = htmlspecialchars((string) ($params['mode'] ?? ''), ENT_QUOTES, 'UTF-8');
$appName = htmlspecialchars($config['app']['name'] ?? 'PayU Store', ENT_QUOTES, 'UTF-8');

$isSuccess = in_array($status, ['success', 'captured'], true);
$isPending = in_array($status, ['pending', 'auth'], true);
$iconClass = $isSuccess ? 'success' : ($isPending ? 'pending' : 'failed');
$icon = $isSuccess ? '✓' : ($isPending ? '⏳' : '✕');
$title = $isSuccess ? 'Payment successful' : ($isPending ? 'Payment pending' : 'Payment failed');
$statusMessage = $isSuccess
    ? 'Your order has been confirmed. Thank you for shopping with us!'
    : ($isPending ? 'We are still processing your payment.' : 'Something went wrong. Please try again.');
$txnid = $txnidSafe;
$verificationNote = null;
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
