<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $statusMessage */
/** @var string $iconClass */
/** @var string $icon */
/** @var string $txnid */
/** @var string $mihpayid */
/** @var string $amount */
/** @var string $mode */
/** @var string $status */
/** @var bool|null $hashValid */
/** @var string|null $verificationNote */
/** @var string $appName */
?>
<div class="result-page">
    <div class="result-card">
        <div class="result-icon <?= htmlspecialchars($iconClass, ENT_QUOTES) ?>"><?= $icon ?></div>
        <h1 style="margin:0 0 8px;font-size:1.75rem"><?= htmlspecialchars($title, ENT_QUOTES) ?></h1>
        <p style="color:var(--text-muted);margin:0 0 8px"><?= htmlspecialchars($statusMessage, ENT_QUOTES) ?></p>

        <div class="result-details">
            <?php if ($txnid !== ''): ?>
            <div class="result-row"><span>Transaction ID</span><span><?= htmlspecialchars($txnid, ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($mihpayid !== ''): ?>
            <div class="result-row"><span>PayU ID</span><span><?= htmlspecialchars($mihpayid, ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($amount !== ''): ?>
            <div class="result-row"><span>Amount</span><span>₹<?= htmlspecialchars($amount, ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <?php if ($mode !== ''): ?>
            <div class="result-row"><span>Payment mode</span><span><?= htmlspecialchars($mode, ENT_QUOTES) ?></span></div>
            <?php endif; ?>
            <div class="result-row"><span>Status</span><span><?= htmlspecialchars(ucfirst($status), ENT_QUOTES) ?></span></div>
            <?php if ($hashValid !== null): ?>
            <div class="result-row">
                <span>Hash verified</span>
                <span style="color:<?= $hashValid ? 'var(--success)' : 'var(--danger)' ?>"><?= $hashValid ? 'Yes' : 'No' ?></span>
            </div>
            <?php elseif (!empty($verificationNote)): ?>
            <div class="result-row"><span>Verification</span><span><?= htmlspecialchars($verificationNote, ENT_QUOTES) ?></span></div>
            <?php endif; ?>
        </div>

        <a href="/" class="btn btn-primary" style="width:100%">Continue shopping</a>
    </div>
</div>
<script>
// Clear any pending UPI intent marker — payment is done, no need to intercept checkout
try { localStorage.removeItem('payu_pending_upi'); } catch(e) {}
</script>
