<?php

declare(strict_types=1);

final class UpiIntent
{
    /**
     * PayU docs — UPI Intent S2S (Step 4):
     * Generic / QR: upi://pay? + intentURIData
     * iOS app-specific prefixes (limited availability):
     * @see https://docs.payu.in/docs/upi-intent-server-to-server
     */
    private const IOS_PREFIXES = [
        'phonepe' => 'phonepe://upi/pay?',
        'paytm' => 'paytm://upi/pay?',
        'gpay' => 'gpay://upi/pay?',
        'googlepay' => 'gpay://upi/pay?',
        'bhim' => 'bhim://upi/pay?',
        'cred' => 'credpay://upi/pay?',
        'credpay' => 'credpay://upi/pay?',
    ];

    /** Android intent:// URLs with package names per PayU manifest guidance. */
    private const ANDROID_PACKAGES = [
        'phonepe' => 'com.phonepe.app',
        'paytm' => 'net.one97.paytm',
        'gpay' => 'com.google.android.apps.nbu.paisa.user',
        'googlepay' => 'com.google.android.apps.nbu.paisa.user',
        'bhim' => 'in.org.npci.upiapp',
        'cred' => 'com.dreamplug.androidapp',
        'credpay' => 'com.dreamplug.androidapp',
        'amazonpay' => 'in.amazon.mShop.android.shopping',
    ];

    public static function buildUrl(array $data, string $app = 'generic'): ?string
    {
        $intentData = $data['result']['intentURIData']
            ?? $data['metaData']['intentURIData']
            ?? null;

        if ($intentData === null || $intentData === '') {
            return null;
        }

        $query = self::extractQuery((string) $intentData);
        if ($query === '') {
            return null;
        }

        $app = strtolower(trim($app));
        if ($app === '' || $app === 'generic' || $app === 'genericintent') {
            return 'upi://pay?' . $query;
        }

        if (self::isAndroid()) {
            $fallbackUrl = self::extractFallbackUrl($data);
            $androidUrl = self::buildAndroidIntentUrl($query, $app, $fallbackUrl);
            if ($androidUrl !== null) {
                return $androidUrl;
            }
        }

        $prefix = self::IOS_PREFIXES[$app] ?? 'upi://pay?';
        return $prefix . $query;
    }

    public static function buildAndroidIntentUrl(string $query, string $app, ?string $fallbackUrl = null): ?string
    {
        $package = self::ANDROID_PACKAGES[$app] ?? null;
        if ($package === null) {
            return null;
        }

        $url = 'intent://pay?' . $query . '#Intent;scheme=upi;package=' . $package . ';';
        if ($fallbackUrl !== null) {
            $url .= 'S.browser_fallback_url=' . rawurlencode($fallbackUrl) . ';';
        }
        $url .= 'end';

        return $url;
    }

    /**
     * Extracts the UPI fallback URL from the PayU response.
     * For UPI Intent, acsTemplate (base64-decoded) may contain a redirect URL
     * used when the target app is not installed on the device.
     */
    private static function extractFallbackUrl(array $data): ?string
    {
        $acsTemplate = $data['result']['acsTemplate'] ?? null;
        if (!$acsTemplate) {
            return null;
        }

        $decoded = base64_decode((string) $acsTemplate, true);
        if ($decoded === false || trim($decoded) === '') {
            return null;
        }

        $decoded = trim($decoded);
        if (filter_var($decoded, FILTER_VALIDATE_URL)) {
            return $decoded;
        }

        return null;
    }

    private static function isAndroid(): bool
    {
        return (bool) preg_match('/Android/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    private static function extractQuery(string $intentData): string
    {
        if (str_starts_with($intentData, 'upi://pay?')) {
            return substr($intentData, 10);
        }

        foreach (self::IOS_PREFIXES as $prefix) {
            if (str_starts_with($intentData, $prefix)) {
                return substr($intentData, strlen($prefix));
            }
        }

        if (str_starts_with($intentData, 'upi://')) {
            $parts = parse_url($intentData);
            $query = $parts['query'] ?? '';
            if ($query !== '') {
                return $query;
            }
        }

        return ltrim($intentData, '?');
    }
}
