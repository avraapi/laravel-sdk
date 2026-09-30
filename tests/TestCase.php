<?php

declare(strict_types=1);

namespace Avraapi\Laravel\Tests;

use Avraapi\Laravel\AvraApiServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [AvraApiServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('avraapi.project_key', 'test-project-key');
        $app['config']->set('avraapi.api_secret', 'test-project-secret');
        $app['config']->set('avraapi.env', 'development');
        $app['config']->set('avraapi.base_url', 'https://example.test/api/v1');
    }
}
