<?php

namespace Watsy\Laravel;

use Illuminate\Support\ServiceProvider;

class WatsyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/watsy.php', 'watsy');

        $this->app->singleton(WatsyClient::class, function ($app) {
            $config = $app['config']->get('watsy');

            return new WatsyClient((string) ($config['api_key'] ?? ''), [
                'base_url' => $config['base_url'] ?? 'https://watsy.me',
                'timeout' => $config['timeout'] ?? 15,
            ]);
        });
        $this->app->alias(WatsyClient::class, 'watsy');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/watsy.php' => config_path('watsy.php')], 'watsy-config');
        }
    }
}
