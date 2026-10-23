<?php

declare(strict_types=1);

/** @var string $headerMode 'store'|'checkout' */
$headerMode = $headerMode ?? 'store';

$_hConfig  = require __DIR__ . '/../lib/bootstrap.php';
$_hEnv     = $_hConfig['payu']['env'] ?? 'test';
$_hIsProd  = $_hEnv === 'production';
?>
<header class="site-header">
    <div class="site-header__inner">
        <a href="/" class="site-brand" aria-label="PayU Store home">
            <span class="site-brand__wordmark">pay<span class="site-brand__u">u</span></span>
            <span class="site-brand__divider"></span>
            <span class="site-brand__label">Store</span>
        </a>
        <div class="site-header__right">
            <?php if ($headerMode === 'checkout'): ?>
                <a href="/" class="header-link">← Back to store</a>
            <?php else: ?>
                <span class="header-pill">S2S Checkout</span>
            <?php endif; ?>
            <a href="/config-admin.php"
               class="header-env"
               style="text-decoration:none;cursor:pointer;<?= $_hIsProd ? 'background:rgba(215,0,127,.25);border-color:rgba(215,0,127,.5);' : '' ?>"
               title="Click to change environment">
                <?= $_hIsProd ? '🚀 PROD' : '🧪 TEST' ?>
            </a>
        </div>
    </div>
</header>
