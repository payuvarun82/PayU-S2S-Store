<?php
/**
 * Copy this file to config.php and fill in your PayU test credentials.
 * Get key & salt from: https://docs.payu.in/docs/access-test-merchant-key-and-salt
 */
return [
    'merchant_key' => 'YOUR_MERCHANT_KEY',
    'merchant_salt' => 'YOUR_MERCHANT_SALT',
    'environment' => 'test', // test | production
    'base_url' => 'http://localhost:8080', // your site URL (no trailing slash)
];
