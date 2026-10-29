# watsy/laravel

[![Latest version](https://img.shields.io/packagist/v/watsy/laravel)](https://packagist.org/packages/watsy/laravel) [![Tests](https://github.com/yahyakhalid/watsy-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/yahyakhalid/watsy-laravel/actions/workflows/tests.yml) [![PHP](https://img.shields.io/packagist/dependency-v/watsy/laravel/php)](composer.json) [![License](https://img.shields.io/packagist/l/watsy/laravel)](LICENSE)

Official Laravel package for [Watsy](https://watsy.me): verification codes (OTP) for Iraq over WhatsApp, with automatic fallback to Telegram and SMS.

- Laravel 10, 11, 12 and 13 (PHP 8.1+); the client also works without Laravel.
- `Watsy` facade, config file, and a middleware that checks webhook signatures.
- Errors carry a stable code and the message in English and Arabic.

```bash
composer require watsy/laravel
```

Add your keys to `.env`:

```dotenv
WATSY_KEY=wsy_live_...
WATSY_WEBHOOK_SECRET=whsec_...
```

To change the defaults, publish the config: `php artisan vendor:publish --tag=watsy-config`.

## Send and check a code

```php
use Watsy\Laravel\Facades\Watsy;

// 1. Send. Pass the user's IP so per-IP rate limits apply to them, not to your server.
$verification = Watsy::verify('07701234567', [
    'client_ip' => $request->ip(),
    'channels' => ['whatsapp', 'telegram'], // optional, in order
]);

session(['watsy_verification' => $verification['id']]);

// 2. Check what the user typed.
$check = Watsy::check(session('watsy_verification'), $request->input('code'));

if ($check['valid']) {
    // signed in
}
```

Options for `verify()`: `channels`, `client_ip`, `code_length` (4–8), `ttl_seconds` (60–1800), `locale` (`ar` or `en`), `metadata`.

Retry safely with an idempotency key: the same key returns the first response instead of sending a second code.

```php
Watsy::verify($phone, ['client_ip' => $request->ip()], idempotencyKey: "signup-{$user->id}");
```

## Other calls

```php
Watsy::verification($id); // status, channel used, messages and cost
Watsy::cancel($id);       // the code stops working
Watsy::messages(20);      // latest send attempts
Watsy::balance();         // ['balance' => 3000, 'currency' => 'IQD', ...]
```

## Errors

```php
use Watsy\Laravel\WatsyException;

try {
    Watsy::verify($phone);
} catch (WatsyException $e) {
    $e->errorCode;  // e.g. insufficient_balance, rate_limited, validation_failed
    $e->status;     // HTTP status
    $e->getMessage(); // English
    $e->messageAr;  // Arabic
    $e->requestId;  // quote it to support
}
```

## Webhooks

Exclude the route from CSRF protection, and put the signature middleware on it:

```php
use Watsy\Laravel\Http\Middleware\VerifyWatsySignature;

Route::post('/watsy/webhook', function (Request $request) {
    $event = $request->json()->all();

    if ($event['type'] === 'verification.verified') {
        // $event['data'] is the verification
    }

    return response()->noContent();
})->middleware(VerifyWatsySignature::class);
```

Events: `verification.sent`, `verification.failed`, `verification.verified`, `verification.expired`. Outside a route you can check a payload yourself with `Watsy\Laravel\Webhook::constructEvent($rawBody, $signatureHeader, $secret)`.

## Without Laravel

```php
$watsy = new Watsy\Laravel\WatsyClient(getenv('WATSY_KEY'));
$watsy->verify('07701234567', ['client_ip' => $_SERVER['REMOTE_ADDR']]);
```

## Test mode

Test keys (`wsy_test_…`) are free and only send to the project's test numbers, with a fixed code (`000000` for 6 digits).

## Links

- Docs: https://docs.watsy.me
- Dashboard: https://app.watsy.me
- Support: support@watsy.me

MIT © akkadX
