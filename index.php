<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
$products = require __DIR__ . '/lib/products.php';
$appName = $config['app']['name'] ?? 'NovaStore';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName) ?> — Premium Digital Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'store'; require __DIR__ . '/includes/header.php'; ?>
    <div class="container">
        <section class="hero">
            <h1>Made in India,<br><span>for the world</span></h1>
            <p>Secure one-time checkout powered by PayU Server-to-Server — cards, UPI &amp; net banking.</p>
        </section>

        <section class="products">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <div class="product-visual" style="background: <?= htmlspecialchars($product['gradient']) ?>">
                        <span class="product-badge"><?= htmlspecialchars($product['badge']) ?></span>
                        <?= $product['emoji'] ?>
                    </div>
                    <div class="product-body">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="tagline"><?= htmlspecialchars($product['tagline']) ?></p>
                        <div class="product-features">
                            <?php foreach ($product['features'] as $feature): ?>
                                <span class="chip"><?= htmlspecialchars($feature) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="product-price">
                            <span class="current">₹<?= number_format($product['price']) ?></span>
                            <?php if (!empty($product['compare_at'])): ?>
                                <span class="was">₹<?= number_format($product['compare_at']) ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="/checkout.php?product=<?= urlencode($product['id']) ?>" class="btn btn-primary">
                            Buy now →
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>

    <footer class="footer">
        PayU Store · One-time payments (non-SI) · Test environment
    </footer>
</body>
</html>
