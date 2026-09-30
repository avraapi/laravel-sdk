<?php

declare(strict_types=1);

namespace Avraapi\Laravel\Tests\Feature;

use Avraapi\Apix\ApixClient;
use Avraapi\Apix\Services\PaymentService;
use Avraapi\Laravel\Facades\AvraAPI;
use Avraapi\Laravel\Tests\TestCase;

final class LaravelIntegrationTest extends TestCase
{
    public function test_the_container_and_facade_resolve_the_same_configured_client(): void
    {
        AvraAPI::clearResolvedInstance('avraapi');

        $client = $this->app->make(ApixClient::class);

        self::assertSame($client, $this->app->make('avraapi'));
        self::assertSame($client, AvraAPI::getFacadeRoot());
        self::assertSame('test-project-key', $client->config->projectKey);
        self::assertSame('test-project-secret', $client->config->apiSecret);
        self::assertSame('dev', $client->config->env);
        self::assertSame('https://example.test/api/v1', $client->config->baseUrl);
    }

    public function test_the_facade_exposes_the_php_sdk_payment_service(): void
    {
        AvraAPI::clearResolvedInstance('avraapi');

        self::assertInstanceOf(PaymentService::class, AvraAPI::payment());
    }

    public function test_provider_services_expose_the_inherited_privacy_mode_option(): void
    {
        $security = AvraAPI::security();

        self::assertSame($security, $security->withPrivacyMode());
    }
}
