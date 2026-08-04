<?php

namespace LBHurtado\FormHandlerLocation\Tests;

use LBHurtado\FormHandlerLocation\LocationHandlerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            LocationHandlerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        // Laravel Data configuration
        $app['config']->set('data.validation_strategy', 'only_requests');
        $app['config']->set('data.max_transformation_depth', 6);
        $app['config']->set('data.throw_when_max_transformation_depth_reached', 6);

        // Location handler configuration
        $app['config']->set('location-handler.opencage_api_key', 'test_key');
        $app['config']->set('location-handler.map_provider', 'mapbox');
        $app['config']->set('location-handler.mapbox_token', 'test_mapbox_token');
        $app['config']->set('location-handler.capture_snapshot', true);
        $app['config']->set('location-handler.require_address', false);
    }
}
