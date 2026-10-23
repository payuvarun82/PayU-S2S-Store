<?php

declare(strict_types=1);

$txnid = htmlspecialchars(trim((string) ($_GET['txnid'] ?? '')), ENT_QUOTES, 'UTF-8');
$mode = ($_GET['mode'] ?? 'collect') === 'collect' ? 'collect' : 'collect';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve UPI Payment — PayU Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'checkout'; require __DIR__ . '/includes/header.php'; ?>
<div class="waiting-page" style="min-height:calc(100vh - 64px)">
    <div class="waiting-card">
        <div class="pulse-ring"></div>
        <div class="waiting-icon">📲</div>
        <h1>Approve on your UPI app</h1>
        <p>Accept the collect request on your phone. Complete within <strong id="timer">03:00</strong>.</p>
        <p class="txn-id">Transaction: <strong id="txnid"><?= $txnid ?></strong></p>
        <div class="status-pill pending" id="statusPill">Waiting for approval…</div>
        <button class="btn btn-primary" id="checkBtn" type="button">Check now</button>
        <a class="btn btn-ghost" href="/">Back to store</a>
    </div>
</div>
<script>
const txnid = <?= json_encode($_GET['txnid'] ?? '') ?>;
const pill = document.getElementById('statusPill');
const timerEl = document.getElementById('timer');
let secondsLeft = 180;
let pollTimer;
let countdownTimer;

function formatTime(s) {
    const m = String(Math.floor(s / 60)).padStart(2, '0');
    const sec = String(s % 60).padStart(2, '0');
    return m + ':' + sec;
}

async function checkStatus() {
    if (!txnid) return;
    pill.textContent = 'Verifying with PayU…';
    pill.className = 'status-pill pending';
    try {
        const res = await fetch('/api/verify-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ txnid })
        });
        const data = await res.json();
        const txnData = data.data?.transaction_details?.[txnid] || data.data;
        const status = (txnData?.status || '').toLowerCase();

        if (status === 'success' || status === 'captured') {
            pill.textContent = 'Payment verified — redirecting…';
            pill.className = 'status-pill success';
            clearInterval(pollTimer);
            clearInterval(countdownTimer);
            window.location.href = '/payment-result.php?txnid=' + encodeURIComponent(txnid);
            return;
        }
        if (status === 'failure' || status === 'failed') {
            pill.textContent = 'Payment failed';
            pill.className = 'status-pill failed';
            clearInterval(pollTimer);
            clearInterval(countdownTimer);
            setTimeout(() => {
                window.location.href = '/payment-result.php?txnid=' + encodeURIComponent(txnid);
            }, 1500);
            return;
        }
        pill.textContent = 'Waiting for approval…';
        pill.className = 'status-pill pending';
    } catch (e) {
        pill.textContent = 'Could not verify yet';
        pill.className = 'status-pill pending';
    }
}

document.getElementById('checkBtn').addEventListener('click', checkStatus);

countdownTimer = setInterval(() => {
    secondsLeft -= 1;
    timerEl.textContent = formatTime(Math.max(0, secondsLeft));
    if (secondsLeft <= 0) {
        clearInterval(countdownTimer);
        clearInterval(pollTimer);
        pill.textContent = 'Time expired — please try again';
        pill.className = 'status-pill failed';
    }
}, 1000);

checkStatus();
pollTimer = setInterval(checkStatus, 5000);
</script>
</body>
</html>
