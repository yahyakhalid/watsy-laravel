<?php

namespace Watsy\Laravel\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Watsy\Laravel\WatsyClient;
use Watsy\Laravel\WatsyException;
use Watsy\Laravel\Webhook;

class ClientTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function client(Response ...$responses): WatsyClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new WatsyClient('wsy_test_abc', [
            'http' => new Client(['handler' => $stack, 'base_uri' => 'https://watsy.me/', 'http_errors' => false]),
        ]);
    }

    private static function json(int $status, array $body, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'] + $headers, json_encode($body));
    }

    public function test_verify_sends_the_phone_options_and_idempotency_key(): void
    {
        $watsy = $this->client(self::json(201, ['id' => 'ver_1', 'status' => 'sent']));
        $v = $watsy->verify('07701234567', ['client_ip' => '203.0.113.7', 'channels' => ['whatsapp', 'telegram']], 'signup-42');

        $this->assertSame('ver_1', $v['id']);
        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/v1/verify', $request->getUri()->getPath());
        $this->assertSame('Bearer wsy_test_abc', $request->getHeaderLine('Authorization'));
        $this->assertSame('signup-42', $request->getHeaderLine('Idempotency-Key'));
        $this->assertSame(
            ['phone' => '07701234567', 'client_ip' => '203.0.113.7', 'channels' => ['whatsapp', 'telegram']],
            json_decode((string) $request->getBody(), true),
        );
    }

    public function test_other_calls_hit_the_right_paths(): void
    {
        $ok = fn () => self::json(200, ['object' => 'list', 'data' => [['id' => 'msg_1']]]);
        $watsy = $this->client($ok(), $ok(), $ok(), $ok(), $ok());
        $watsy->check('ver_1', '123456');
        $watsy->verification('ver_1');
        $watsy->cancel('ver_1');
        $messages = $watsy->messages(5);
        $watsy->balance();

        $calls = array_map(fn ($h) => $h['request']->getMethod().' '.$h['request']->getUri()->getPath().($h['request']->getUri()->getQuery() ? '?'.$h['request']->getUri()->getQuery() : ''), $this->history);
        $this->assertSame(
            ['POST /v1/verify/ver_1/check', 'GET /v1/verify/ver_1', 'POST /v1/verify/ver_1/cancel', 'GET /v1/messages?limit=5', 'GET /v1/balance'],
            $calls,
        );
        $this->assertSame(['code' => '123456'], json_decode((string) $this->history[0]['request']->getBody(), true));
        $this->assertSame('{}', (string) $this->history[2]['request']->getBody());
        $this->assertSame('msg_1', $messages[0]['id']);
    }

    public function test_errors_carry_the_code_both_messages_and_request_id(): void
    {
        $watsy = $this->client(self::json(402, ['error' => [
            'code' => 'insufficient_balance',
            'message' => 'Not enough balance.',
            'message_ar' => 'الرصيد غير كافٍ.',
        ]], ['X-Request-Id' => 'req_9']));

        try {
            $watsy->verify('07701234567');
            $this->fail('expected a WatsyException');
        } catch (WatsyException $e) {
            $this->assertSame('insufficient_balance', $e->errorCode);
            $this->assertSame(402, $e->status);
            $this->assertSame('الرصيد غير كافٍ.', $e->messageAr);
            $this->assertSame('req_9', $e->requestId);
        }
    }

    public function test_webhook_signatures(): void
    {
        $secret = 'whsec_test';
        $body = json_encode(['id' => 'evt_1', 'type' => 'verification.verified', 'data' => ['id' => 'ver_1']]);
        $t = 1800000000;
        $header = "t={$t},v1=".hash_hmac('sha256', "{$t}.{$body}", $secret);

        $this->assertTrue(Webhook::verifySignature($body, $header, $secret, now: $t + 10));
        $this->assertFalse(Webhook::verifySignature($body.' ', $header, $secret, now: $t + 10));
        $this->assertFalse(Webhook::verifySignature($body, $header, $secret, now: $t + 3600));
        $this->assertFalse(Webhook::verifySignature($body, null, $secret, now: $t));
    }
}
