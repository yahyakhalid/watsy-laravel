<?php

namespace Watsy\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Watsy\Laravel\Webhook;

/**
 * Rejects webhook requests whose Watsy-Signature does not match
 * WATSY_WEBHOOK_SECRET. Add it to your webhook route:
 *
 *     Route::post('/watsy/webhook', WatsyWebhookController::class)
 *         ->middleware(VerifyWatsySignature::class);
 *
 * (and exclude that route from CSRF verification).
 */
class VerifyWatsySignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('watsy.webhook_secret', '');
        if (! Webhook::verifySignature($request->getContent(), $request->header('Watsy-Signature'), $secret)) {
            abort(400, 'Invalid Watsy signature.');
        }

        return $next($request);
    }
}
