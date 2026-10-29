<?php

namespace Watsy\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\ResponseInterface;

/**
 * Watsy API client. Works with or without Laravel: in Laravel use the
 * `Watsy` facade (or inject this class); elsewhere `new WatsyClient($key)`.
 *
 * Every method returns the API's JSON as an array and throws WatsyException
 * on an error response.
 */
class WatsyClient
{
    public const VERSION = '0.1.0';

    private ClientInterface $http;

    /**
     * @param array{base_url?: string, timeout?: int|float, http?: ClientInterface} $options
     */
    public function __construct(private string $apiKey, array $options = [])
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('Watsy: set WATSY_KEY in your .env (wsy_live_… or wsy_test_…).');
        }
        $this->http = $options['http'] ?? new Client([
            'base_uri' => rtrim($options['base_url'] ?? 'https://watsy.me', '/').'/',
            'timeout' => $options['timeout'] ?? 15,
            'http_errors' => false,
        ]);
    }

    /**
     * Sends a verification code to an Iraqi number, over the first channel that delivers.
     *
     * @param array{
     *     channels?: list<'whatsapp'|'telegram'|'sms'>,
     *     client_ip?: string,
     *     code_length?: int,
     *     ttl_seconds?: int,
     *     locale?: 'ar'|'en',
     *     metadata?: array<string, mixed>
     * } $options Pass your user's IP as client_ip so per-IP limits apply to them.
     * @param string|null $idempotencyKey The same key returns the first response instead of sending again.
     * @return array<string, mixed> The verification.
     */
    public function verify(string $phone, array $options = [], ?string $idempotencyKey = null): array
    {
        $body = array_filter(['phone' => $phone] + $options, fn ($v) => $v !== null);

        return $this->request('POST', 'v1/verify', $body, $idempotencyKey);
    }

    /**
     * Checks the code the user typed. `valid` is true once; then the verification is closed.
     *
     * @return array{valid: bool, status: string, attempts: int, attempts_remaining: int, verified_at: ?string}
     */
    public function check(string $verificationId, string $code): array
    {
        return $this->request('POST', 'v1/verify/'.rawurlencode($verificationId).'/check', ['code' => $code]);
    }

    /** @return array<string, mixed> The verification with its messages (channel, status, cost). */
    public function verification(string $verificationId): array
    {
        return $this->request('GET', 'v1/verify/'.rawurlencode($verificationId));
    }

    /** @return array<string, mixed> The canceled verification; its code no longer works. */
    public function cancel(string $verificationId): array
    {
        return $this->request('POST', 'v1/verify/'.rawurlencode($verificationId).'/cancel', []);
    }

    /** @return list<array<string, mixed>> The latest send attempts, newest first (1–100). */
    public function messages(int $limit = 20): array
    {
        return $this->request('GET', 'v1/messages?limit='.$limit)['data'] ?? [];
    }

    /** @return array{balance: int|float, balance_milli: int, currency: string, mode: string} */
    public function balance(): array
    {
        return $this->request('GET', 'v1/balance');
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
    {
        $options = [
            'headers' => array_filter([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'application/json',
                'User-Agent' => 'watsy-laravel/'.self::VERSION,
                'Idempotency-Key' => $idempotencyKey,
            ]),
        ];
        if ($body !== null) {
            // An empty body must still be a JSON object, not a list.
            $options['body'] = $body === [] ? '{}' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $options['headers']['Content-Type'] = 'application/json';
        }

        try {
            $response = $this->http->request($method, $path, $options);
        } catch (ConnectException $e) {
            throw new WatsyException('connection_failed', 0, 'Could not reach the Watsy API: '.$e->getMessage(), previous: $e);
        }

        return $this->decode($response);
    }

    /** @return array<string, mixed> */
    private function decode(ResponseInterface $response): array
    {
        $json = json_decode((string) $response->getBody(), true);
        $status = $response->getStatusCode();
        if ($status >= 400) {
            $error = is_array($json) ? ($json['error'] ?? []) : [];
            throw new WatsyException(
                $error['code'] ?? 'http_error',
                $status,
                $error['message'] ?? "Watsy API returned HTTP {$status}",
                $error['message_ar'] ?? null,
                $error['detail'] ?? null,
                $response->getHeaderLine('X-Request-Id') ?: null,
            );
        }

        return is_array($json) ? $json : [];
    }
}
