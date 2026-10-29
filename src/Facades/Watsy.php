<?php

namespace Watsy\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Watsy\Laravel\WatsyClient;

/**
 * @method static array verify(string $phone, array $options = [], ?string $idempotencyKey = null)
 * @method static array check(string $verificationId, string $code)
 * @method static array verification(string $verificationId)
 * @method static array cancel(string $verificationId)
 * @method static array messages(int $limit = 20)
 * @method static array balance()
 *
 * @see WatsyClient
 */
class Watsy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WatsyClient::class;
    }
}
