<?php

namespace Watsy\Laravel;

/**
 * Watsy signs each webhook with `Watsy-Signature: t=<unix seconds>,v1=<hex>`,
 * where v1 is HMAC-SHA256(secret, "<t>.<raw body>").
 */
class Webhook
{
    /** True when the header matches the raw body and is no older than $toleranceSeconds. */
    public static function verifySignature(string $rawBody, ?string $header, string $secret, int $toleranceSeconds = 300, ?int $now = null): bool
    {
        if ($header === null || $header === '' || $secret === '') {
            return false;
        }
        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }
        $t = $parts['t'] ?? '';
        $v1 = $parts['v1'] ?? '';
        if (! ctype_digit($t) || $v1 === '') {
            return false;
        }
        if (abs(($now ?? time()) - (int) $t) > $toleranceSeconds) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $t.'.'.$rawBody, $secret), $v1);
    }

    /**
     * Verifies the signature and returns the event
     * (['id' => 'evt_…', 'type' => 'verification.verified', 'created_at' => …, 'data' => verification]).
     *
     * @return array<string, mixed>
     */
    public static function constructEvent(string $rawBody, ?string $header, string $secret, int $toleranceSeconds = 300): array
    {
        if (! self::verifySignature($rawBody, $header, $secret, $toleranceSeconds)) {
            throw new WatsyException('invalid_signature', 400, 'Invalid Watsy-Signature header.');
        }

        return json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
    }
}
