<?php

// File: src/AvraApiServiceProvider.php

declare(strict_types=1);

namespace Avraapi\Laravel;

use Avraapi\Apix\ApixClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * AvraAPI Laravel Service Provider
 *
 * Responsibilities:
 *   1. Registers the ApixClient as a singleton in Laravel's service container,
 *      wired from the published `config/avraapi.php` configuration file.
 *   2. Enables publishing the configuration file via:
 *        php artisan vendor:publish --tag="avraapi-config"
 *   3. Registered automatically via Composer's package auto-discovery
 *      (`extra.laravel.providers` in composer.json) — no manual registration
 *      in config/app.php required.
 *
 * ── How the singleton is resolved ─────────────────────────────────────────────
 *
 * The Service Provider maps the ApixClient constructor's expected
 * SCREAMING_SNAKE_CASE keys from the avraapi config values:
 *
 *   config('avraapi.project_key')     → 'APIX_PROJECT_KEY'
 *   config('avraapi.api_secret')      → 'APIX_API_SECRET'
 *   config('avraapi.env')             → 'APIX_ENV'
 *   config('avraapi.base_url')        → 'APIX_BASE_URL'
 *   config('avraapi.timeout')         → 'APIX_TIMEOUT'
 *   config('avraapi.connect_timeout') → 'APIX_CONNECT_TIMEOUT'
 *
 * The pure PHP SDK's Config class reads these keys by name from its $config
 * array parameter, so passing them with the APIX_ prefix is both correct and
 * explicit — no magic coupling required.
 *
 * ── Facade binding key ────────────────────────────────────────────────────────
 *
 * The ApixClient singleton is bound to the container under two aliases:
 *   1. The FQCN `Avraapi\Apix\ApixClient` (for DI injection in constructors).
 *   2. The abstract string key `'avraapi'` (what the AvraAPI Facade resolves).
 *
 * Both resolve to the same singleton instance.
 */
final class AvraApiServiceProvider extends ServiceProvider
{
    /**
     * Register the ApixClient singleton in the Laravel service container.
     *
     * Called before boot(). Uses the config values to construct the client
     * so the singleton is ready by the time controllers / jobs request it.
     *
     * Note: We call $this->mergeConfigFrom() here (in register, not boot) so
     * config values are available even if the developer hasn't published the
     * config file — sensible defaults are merged automatically.
     */
    public function register(): void
    {
        // Merge package defaults so config('avraapi.*') always resolves,
        // even before the developer runs vendor:publish.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/avraapi.php',
            'avraapi'
        );

        // Register the ApixClient as a singleton bound to both the FQCN
        // and the short 'avraapi' abstract key used by the Facade.
        $this->app->singleton(ApixClient::class, static function (Application $app): ApixClient {
            /** @var array<string, mixed> $cfg */
            $cfg = $app['config']->get('avraapi', []);

            // Build the config array using the SCREAMING_SNAKE_CASE keys the
            // pure PHP SDK's Config class expects. Null/empty values are
            // omitted so the SDK falls back to its own defaults gracefully.
            $sdkConfig = array_filter([
                'APIX_PROJECT_KEY'    => $cfg['project_key']     ?? null,
                'APIX_API_SECRET'     => $cfg['api_secret']      ?? null,
                'APIX_ENV'            => $cfg['env']             ?? null,
                'APIX_BASE_URL'       => $cfg['base_url']        ?? null,
                'APIX_TIMEOUT'        => isset($cfg['timeout'])        ? (string) $cfg['timeout']         : null,
                'APIX_CONNECT_TIMEOUT'=> isset($cfg['connect_timeout']) ? (string) $cfg['connect_timeout'] : null,
            ], static fn (mixed $v): bool => $v !== null && $v !== '');

            return new ApixClient($sdkConfig);
        });

        // Alias the FQCN binding to the 'avraapi' abstract key that the
        // AvraAPI Facade's getFacadeAccessor() returns.
        $this->app->alias(ApixClient::class, 'avraapi');
    }

    /**
     * Bootstrap any package services.
     *
     * Publishes the configuration file so developers can customise it:
     *   php artisan vendor:publish --tag="avraapi-config"
     *
     * This places a copy of config/avraapi.php into the application's own
     * config/ directory, where it takes precedence over the package default.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/avraapi.php' => config_path('avraapi.php'),
            ], 'avraapi-config');
        }
    }

    /**
     * Declare which services this provider provides.
     *
     * Helps Laravel's deferred loading optimise the container — although
     * this provider is not deferred (it must run on every request), listing
     * the provided bindings is good practice and aids tooling introspection.
     *
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            ApixClient::class,
            'avraapi',
        ];
    }
}
