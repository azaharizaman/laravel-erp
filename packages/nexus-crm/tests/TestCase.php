<?php

declare(strict_types=1);

namespace Nexus\Crm\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Nexus\Crm\CrmServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Define your environment setup here
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up a basic database connection for models
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set the connection resolver
        $connection = $app['db']->connection('testing');
        $resolver = \Illuminate\Database\Connection::getResolver();
        if ($resolver) {
            \Illuminate\Database\Eloquent\Model::setConnectionResolver($app['db']);
        }
    }
}