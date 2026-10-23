<?php

declare(strict_types=1);

final class PayUHash
{
    public static function generatePaymentHash(array $params, string $salt): string
    {
        $udf1 = $params['udf1'] ?? '';
        $udf2 = $params['udf2'] ?? '';
        $udf3 = $params['udf3'] ?? '';
        $udf4 = $params['udf4'] ?? '';
        $udf5 = $params['udf5'] ?? '';
        $siDetails = $params['si_details'] ?? '';

        $hashString = implode('|', [
            $params['key'],
            $params['txnid'],
            $params['amount'],
            $params['productinfo'],
            $params['firstname'],
            $params['email'],
            $udf1,
            $udf2,
            $udf3,
            $udf4,
            $udf5,
            '',
            '',
            '',
            '',
            '',
            $siDetails,
            $salt,
        ]);

        return strtolower(hash('sha512', $hashString));
    }

    public static function verifyResponseHash(array $response, string $salt): bool
    {
        $response = self::normalizeResponse($response);
        $postedHash = strtolower(trim((string) ($response['hash'] ?? '')));
        if ($postedHash === '' || !isset($response['status'])) {
            return false;
        }

        foreach (self::buildResponseHashVariants($response, $salt) as $calculated) {
            if (hash_equals($calculated, $postedHash)) {
                return true;
            }
        }

        return false;
    }

    public static function calculateResponseHash(array $response, string $salt): string
    {
        $response = self::normalizeResponse($response);

        return self::buildResponseHashVariants($response, $salt)[0];
    }

    /**
     * PayU reverse hash variants:
     * - standard: SALT|status||||||udf5|...|key
     * - additionalCharges: additionalCharges|SALT|status||||||udf5|...|key
     * - splitInfo: SALT|status|splitInfo||||||udf5|...|key
     * - combined: additionalCharges|SALT|status|splitInfo||||||udf5|...|key
     */
    private static function buildResponseHashVariants(array $response, string $salt): array
    {
        $tail = self::responseTail($response);
        $status = (string) ($response['status'] ?? '');
        $additionalCharges = self::field($response, 'additionalCharges');
        $splitInfo = self::field($response, 'splitInfo');
        $emptyUdfSlots = self::emptyUdfSlots();

        $variants = [];

        if ($additionalCharges !== '' && $splitInfo !== '') {
            $variants[] = self::hashParts([$additionalCharges, $salt, $status, $splitInfo, ...$emptyUdfSlots, ...$tail]);
        }
        if ($additionalCharges !== '') {
            $variants[] = self::hashParts([$additionalCharges, $salt, $status, ...$emptyUdfSlots, ...$tail]);
        }
        if ($splitInfo !== '') {
            $variants[] = self::hashParts([$salt, $status, $splitInfo, ...$emptyUdfSlots, ...$tail]);
        }

        $variants[] = self::hashParts([$salt, $status, ...$emptyUdfSlots, ...$tail]);

        return array_values(array_unique($variants));
    }

    /** Five empty fields between status/splitInfo and udf5 (PayU "||||||" segment). */
    private static function emptyUdfSlots(): array
    {
        return ['', '', '', '', ''];
    }

    private static function responseTail(array $response): array
    {
        return [
            (string) ($response['udf5'] ?? ''),
            (string) ($response['udf4'] ?? ''),
            (string) ($response['udf3'] ?? ''),
            (string) ($response['udf2'] ?? ''),
            (string) ($response['udf1'] ?? ''),
            (string) ($response['email'] ?? ''),
            (string) ($response['firstname'] ?? ''),
            (string) ($response['productinfo'] ?? ''),
            (string) ($response['amount'] ?? ''),
            (string) ($response['txnid'] ?? ''),
            (string) ($response['key'] ?? ''),
        ];
    }

    private static function hashParts(array $parts): string
    {
        return strtolower(hash('sha512', implode('|', $parts)));
    }

    private static function normalizeResponse(array $response): array
    {
        if (isset($response['additional_charges']) && !isset($response['additionalCharges'])) {
            $response['additionalCharges'] = $response['additional_charges'];
        }

        if (isset($response['split_info']) && !isset($response['splitInfo'])) {
            $response['splitInfo'] = $response['split_info'];
        }

        return $response;
    }

    private static function field(array $response, string $key): string
    {
        if (!array_key_exists($key, $response)) {
            return '';
        }

        $value = $response[$key];
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    public static function generateCommandHash(string $key, string $command, string $var1, string $salt): string
    {
        return strtolower(hash('sha512', $key . '|' . $command . '|' . $var1 . '|' . $salt));
    }
}
