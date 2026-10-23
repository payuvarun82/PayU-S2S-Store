<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        // .env always wins — override whatever the system environment has
        if ($name !== '') {
            putenv($name . '=' . $value);
            $_ENV[$name]     = $value;
            $_SERVER[$name]  = $value;
        }
    }
}

$payuEnv = getenv('PAYU_ENV') ?: 'test';
$isProd  = $payuEnv === 'production';

// Use the credentials that match the active environment
$payuKey  = $isProd
    ? (getenv('PAYU_PROD_KEY')  ?: '')
    : (getenv('PAYU_TEST_KEY')  ?: '');
$payuSalt = $isProd
    ? (getenv('PAYU_PROD_SALT') ?: '')
    : (getenv('PAYU_TEST_SALT') ?: '');

return [
    'payu' => [
        'key'  => $payuKey,
        'salt' => $payuSalt,
        'env'  => $payuEnv,
        'payment_url' => $isProd
            ? 'https://secure.payu.in/_payment'
            : 'https://test.payu.in/_payment',
        'postservice_url' => $isProd
            ? 'https://secure.payu.in/merchant/postservice?form=2'
            : 'https://test.payu.in/merchant/postservice?form=2',
    ],
    'app' => [
        'name' => 'PayU Store',
    ],
];
