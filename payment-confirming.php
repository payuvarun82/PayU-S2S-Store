<?php

declare(strict_types=1);

$txnid = trim((string) ($_GET['txnid'] ?? ''));
if ($txnid === '') {
    header('Location: /');
    exit;
}
// quick=1 → user tapped "I have paid" manually; poll for ~15 s then redirect anyway
$quickMode = ($_GET['quick'] ?? '') === '1';
$txnidSafe = htmlspecialchars($txnid, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirming Payment — PayU Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'checkout'; require __DIR__ . '/includes/header.php'; ?>

<div class="waiting-page" style="min-height:calc(100vh - 64px)">
    <div class="waiting-card" style="max-width:400px">

        <div class="pulse-ring"></div>

        <div class="waiting-icon" style="font-size:36px;margin:8px 0">🏦</div>

        <h1 style="font-size:20px;margin:0 0 8px">Confirming your payment</h1>
        <p style="margin:0 0 4px;color:var(--text-muted);font-size:14px">
            We are checking your payment status with the bank.
        </p>
        <p style="margin:0 0 20px;color:var(--text-muted);font-size:13px">
            <?= $quickMode
                ? 'This will take up to 15 seconds — hang tight.'
                : 'Please do not close this page.' ?>
        </p>

        <p class="txn-id">Transaction: <strong><?= $txnidSafe ?></strong></p>

        <div class="status-pill pending" id="statusPill">Checking with bank…</div>

        <!-- shown only after extended wait -->
        <div id="manualSection" style="display:none;margin-top:16px">
            <p style="font-size:13px;color:var(--text-muted);margin:0 0 10px">
                Taking longer than usual. You can check your status manually.
            </p>
            <button class="btn btn-primary" id="checkBtn" type="button" style="width:100%">
                Check payment status
            </button>
        </div>

        <a class="btn btn-ghost" href="/" style="margin-top:12px">Back to store</a>
    </div>
</div>

<script>
const txnid     = <?= json_encode($txnid) ?>;
const quickMode = <?= json_encode($quickMode) ?>;   // poll ~15 s then redirect
const pill      = document.getElementById('statusPill');

// Clear the pending-UPI localStorage marker so that if checkout.php is opened
// again (e.g. by the UPI app's redirect) it does NOT bounce back here again.
try { localStorage.removeItem('payu_pending_upi'); } catch(e) {}

let finished  = false;
let attempts  = 0;
// In quick mode (user tapped "I have paid"): max 5 polls ≈ 15 s then redirect
const maxAttempts = quickMode ? 5 : 100;

function goToCallback() {
    window.location.href = '/callback.php?txnid=' + encodeURIComponent(txnid);
}

async function checkStatus() {
    if (finished) return;
    attempts++;

    try {
        const res  = await fetch('/api/verify-payment.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ txnid })
        });
        const data = await res.json();

        const txnData = (data.data?.transaction_details || {})[txnid] || null;
        const status  = txnData ? (txnData.status || '').toLowerCase() : '';

        if (status === 'success' || status === 'captured') {
            finished = true;
            pill.textContent = 'Payment confirmed — redirecting…';
            pill.className   = 'status-pill success';
            setTimeout(goToCallback, 400);
            return;
        }

        if (status === 'failure' || status === 'failed' || status === 'bounced') {
            finished = true;
            pill.textContent = 'Payment failed — redirecting…';
            pill.className   = 'status-pill failed';
            setTimeout(goToCallback, 1200);
            return;
        }

        // Still pending
        pill.textContent = 'Checking with bank…';
        pill.className   = 'status-pill pending';

    } catch(e) {
        // network hiccup — keep going
    }

    // Show manual button after 30 attempts in normal mode
    if (!quickMode && attempts >= 30) {
        var sec = document.getElementById('manualSection');
        if (sec) sec.style.display = 'block';
    }

    // Hit the attempt limit
    if (attempts >= maxAttempts) {
        finished = true;
        if (quickMode) {
            // User said they paid — redirect to callback anyway; result page
            // will do a final verify and show the real status
            pill.textContent = 'Redirecting to check your status…';
            pill.className   = 'status-pill pending';
            setTimeout(goToCallback, 600);
        } else {
            pill.textContent = 'Could not confirm — please check manually';
            pill.className   = 'status-pill failed';
            var sec = document.getElementById('manualSection');
            if (sec) sec.style.display = 'block';
        }
    }
}

// Manual button — redirect to result page immediately
var checkBtn = document.getElementById('checkBtn');
if (checkBtn) {
    checkBtn.addEventListener('click', function() {
        finished = true;
        pill.textContent = 'Redirecting…';
        goToCallback();
    });
}

// Start polling immediately, every 3 seconds
checkStatus();
setInterval(function() {
    if (!finished) checkStatus();
}, 3000);
</script>
</body>
</html>
