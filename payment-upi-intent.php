<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/PaymentService.php';

$token = trim((string) ($_GET['token'] ?? ''));
$txnid = trim((string) ($_GET['txnid'] ?? ''));

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$service = new PaymentService($config, $baseUrl);
$payload = $service->getUpiIntentPayload($token);

if ($payload === null || $payload['intent_url'] === '') {
    http_response_code(410);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Session expired</title>'
        . '<link rel="stylesheet" href="/assets/css/app.css"></head><body class="waiting-page"><div class="waiting-card">'
        . '<h1>UPI session expired</h1><p>Please go back and try the payment again.</p>'
        . '<a class="btn btn-primary" href="/">Back to store</a></div></body></html>';
    exit;
}

$txnid     = $payload['txnid'] ?: $txnid;
$intentUrl = $payload['intent_url'];
$upiApp    = $payload['upi_app'] ?? 'generic';

// QR always uses clean generic upi://pay? — parse_url strips intent:// prefix and #fragment
$parsed     = parse_url($intentUrl);
$upiQuery   = $parsed['query'] ?? '';
$genericUrl = 'upi://pay?' . $upiQuery;
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($genericUrl);

$isMobile = (bool) preg_match('/Android|iPhone|iPad|Mobile/i', $_SERVER['HTTP_USER_AGENT'] ?? '');

$appLabels = [
    'gpay'      => 'Google Pay',
    'googlepay' => 'Google Pay',
    'phonepe'   => 'PhonePe',
    'paytm'     => 'Paytm',
    'bhim'      => 'BHIM',
];
$appLabel = $appLabels[$upiApp] ?? null; // null = generic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay with UPI — PayU Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'checkout'; require __DIR__ . '/includes/header.php'; ?>
<div class="waiting-page" style="min-height:calc(100vh - 64px)">
    <div class="waiting-card" style="max-width:460px">
        <h1>Pay with UPI</h1>

        <?php if ($isMobile): ?>
        <!-- ── Mobile: launch screen shown first, QR revealed as fallback ── -->

        <!-- Shown initially; hidden after deeplink fires + 3.5 s -->
        <div id="launchSection">
            <div class="pulse-ring" style="top:auto;position:relative;left:auto;transform:none;margin:12px auto"></div>
            <div class="waiting-icon" style="font-size:36px;margin:8px 0">
                <?= $appLabel ? '📲' : '📱' ?>
            </div>
            <p style="margin:0 0 4px">
                <?= $appLabel
                    ? 'Opening <strong>' . htmlspecialchars($appLabel, ENT_QUOTES) . '</strong>…'
                    : 'Opening your UPI app…' ?>
            </p>
            <p style="font-size:12px;color:var(--text-muted);margin:0 0 16px">Complete payment within <strong id="timer">03:00</strong></p>
        </div>

        <!-- Hidden initially; revealed after app launch attempt -->
        <div id="qrSection" style="display:none">
            <!-- "I have paid" — most reliable path back on mobile -->
            <button class="btn btn-primary" id="paidBtn" type="button" style="width:100%;margin-bottom:10px">
                ✓ I have completed the payment
            </button>
            <p style="font-size:12px;color:var(--text-muted);margin:0 0 10px">
                Tap above after paying. We'll confirm with the bank and show your result.
            </p>
            <p style="font-size:12px;color:var(--text-muted);margin:0 0 6px">
                App didn't open? Scan the QR below or
                <a id="retryLink" href="#" style="color:var(--payu-primary);text-decoration:none">open again</a>.
            </p>
            <div class="qr-wrap" style="margin:0 auto 12px">
                <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES) ?>" alt="UPI QR code" width="220" height="220" loading="lazy">
            </div>
        </div>

        <?php else: ?>
        <!-- ── Desktop: show QR directly ── -->
        <p>Scan the QR code with any UPI app on your phone. Complete within <strong id="timer">03:00</strong>.</p>
        <p style="font-size:13px;color:var(--text-muted);margin:0 0 8px">Point your phone camera at the QR below</p>
        <div class="qr-wrap" style="margin:0 auto 16px">
            <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES) ?>" alt="UPI QR code" width="260" height="260">
        </div>
        <?php endif; ?>

        <p class="txn-id">Transaction: <strong><?= htmlspecialchars($txnid, ENT_QUOTES) ?></strong></p>
        <!-- Confirming overlay — shown after user returns from UPI app -->
        <div id="confirmingOverlay" style="display:none;margin-top:8px;padding:14px 16px;background:var(--surface-2,#f8f9fa);border-radius:10px;border:1px solid var(--border,#e2e8f0);text-align:center">
            <div class="pulse-ring" style="position:relative;top:auto;left:auto;transform:none;margin:0 auto 10px;width:28px;height:28px"></div>
            <p style="margin:0 0 2px;font-weight:600;font-size:14px;color:var(--text)">Confirming your payment with the bank…</p>
            <p style="margin:0;font-size:12px;color:var(--text-muted)">Please wait, this may take a few seconds.</p>
        </div>
        <div class="status-pill pending" id="statusPill">Waiting for payment…</div>
        <button class="btn btn-ghost" id="checkBtn" type="button" style="margin-top:8px">Check payment status</button>
        <p style="font-size:12px;color:var(--text-muted);margin-top:8px">Status is checked automatically every 5 seconds.</p>
    </div>
</div>

<script>
const txnid     = <?= json_encode($txnid) ?>;
const intentUrl = <?= json_encode($intentUrl) ?>;
const isMobile  = <?= json_encode($isMobile) ?>;

const confirmingUrl = '/payment-confirming.php?txnid=' + encodeURIComponent(txnid);

// Save pending UPI txnid to localStorage.
// checkout.php checks this on load; if set, it redirects to payment-confirming.php.
// This catches the case where the UPI Simulator (or any UPI app) redirects the
// browser back to checkout.php instead of honouring the `url` param in the intent.
try {
    localStorage.setItem('payu_pending_upi', JSON.stringify({ txnid: txnid, ts: Date.now() }));
} catch(e) {}

/* ── Deeplink: hidden anchor fires intent; visibilitychange navigates away ── */
function launchAndGo(url) {
    var navigated = false;

    // Navigate to confirming page — called once, idempotent
    function goConfirming() {
        if (navigated) return;
        navigated = true;
        document.removeEventListener('visibilitychange', onVisibility);
        window.location.href = confirmingUrl;
    }

    // visibilitychange fires the instant Android switches to the UPI app
    // (tab becomes hidden). Navigate to confirming page right then —
    // before Chrome has any chance to throttle/suspend the JS timer.
    // NOTE: no fallback timer here intentionally — if the UPI app did NOT
    // open (no app installed, deeplink blocked), visibilitychange won't fire
    // and the 5-second QR fallback below will kick in instead.
    function onVisibility() {
        if (document.hidden) goConfirming();
    }
    document.addEventListener('visibilitychange', onVisibility);

    // Dispatch intent to Android OS via hidden anchor click
    var a = document.createElement('a');
    a.href = url;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    setTimeout(function() { try { document.body.removeChild(a); } catch(e){} }, 500);
}

/* ── Desktop: QR shown directly — standard polling on this page ── */
const pill     = document.getElementById('statusPill');
const timerEl  = document.getElementById('timer');
const checkBtn = document.getElementById('checkBtn');
let pollTimer, countdownTimer, secondsLeft = 180, finished = false;

function formatTime(s) {
    return String(Math.floor(s/60)).padStart(2,'0') + ':' + String(s%60).padStart(2,'0');
}
function markFinished() {
    finished = true;
    clearInterval(pollTimer);
    clearInterval(countdownTimer);
}
async function checkStatus() {
    if (!txnid || finished) return;
    try {
        const res  = await fetch('/api/verify-payment.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ txnid })
        });
        const data = await res.json();
        const txnData = (data.data?.transaction_details || {})[txnid] || null;
        const status  = txnData ? (txnData.status || '').toLowerCase() : '';
        if (status === 'success' || status === 'captured') {
            pill.textContent = 'Payment verified — redirecting…';
            pill.className   = 'status-pill success';
            markFinished();
            try { localStorage.removeItem('payu_pending_upi'); } catch(e) {}
            setTimeout(function(){ window.location.href = '/callback.php?txnid=' + encodeURIComponent(txnid); }, 300);
            return;
        }
        if (status === 'failure' || status === 'failed' || status === 'bounced') {
            pill.textContent = 'Payment failed — redirecting…';
            pill.className   = 'status-pill failed';
            markFinished();
            try { localStorage.removeItem('payu_pending_upi'); } catch(e) {}
            setTimeout(function(){ window.location.href = '/callback.php?txnid=' + encodeURIComponent(txnid); }, 1500);
            return;
        }
        pill.textContent = 'Waiting for payment…';
        pill.className   = 'status-pill pending';
    } catch(e) {}
}

if (checkBtn) checkBtn.addEventListener('click', checkStatus);

/* ── Mobile flow ────────────────────────────────────────── */
if (isMobile) {
    const launchSection = document.getElementById('launchSection');
    const qrSection     = document.getElementById('qrSection');

    // Fire deeplink 400 ms after page load (gives DOM time to settle)
    // launchAndGo() then navigates to confirming page via visibilitychange
    setTimeout(function() { launchAndGo(intentUrl); }, 400);

    // Fallback: if somehow still on this page after 5 s, show QR + manual button
    // Also kick off automatic polling so the user doesn't have to tap manually
    setTimeout(function() {
        if (launchSection) launchSection.style.display = 'none';
        if (qrSection)     qrSection.style.display     = 'block';
        if (!finished) {
            checkStatus();
            pollTimer = setInterval(function() { if (!finished) checkStatus(); }, 5000);
        }
    }, 5000);

    var paidBtn  = document.getElementById('paidBtn');
    var retryLink = document.getElementById('retryLink');

    if (paidBtn) {
        paidBtn.addEventListener('click', function() {
            // quick=1 tells confirming page to poll for ~15 s then redirect
            // regardless — user already said they paid, no point waiting longer
            window.location.href = confirmingUrl + '&quick=1';
        });
    }
    if (retryLink) {
        retryLink.addEventListener('click', function(e) {
            e.preventDefault();
            launchAndGo(intentUrl);
        });
    }

} else {
    /* Desktop: poll on this page while user scans QR */
    checkStatus();
    pollTimer = setInterval(checkStatus, 5000);
    countdownTimer = setInterval(function() {
        secondsLeft -= 1;
        if (timerEl) timerEl.textContent = formatTime(Math.max(0, secondsLeft));
        if (secondsLeft <= 0) {
            markFinished();
            pill.textContent = 'Time expired — please try again';
            pill.className   = 'status-pill failed';
        }
    }, 1000);
}
</script>
</body>
</html>
