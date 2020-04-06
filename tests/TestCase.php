<?php

namespace Hierarchy\Tests;

use Hierarchy\Models\ProfitStrip;
use Hierarchy\HierarchyServiceProvider;
use Hierarchy\Supports\Facades\HProfit;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected static $coId = 6;

    protected static $ownerId = 7;

    protected static $ownerRole = 'SA';

    protected $fakers;

    private $strip;

    protected function setUp()
    {
        parent::setUp();
        $this->withFactories(__DIR__ . '/Fakers');
    }
    /**
     * Load package service provider
     * @param  \Illuminate\Foundation\Application $app
     * @return \Hierarchy\HierarchyServiceProvider
     */
    protected function getPackageProviders($app)
    {
        return [
            TestServiceProvider::class,
            HierarchyServiceProvider::class,
        ];
    }

    /**
     * Load package alias
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'HProfit' => HProfit::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', env('DB_CONNECTION', 'test_bench'));
        $app['config']->set('database.connections.test_bench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Get application timezone.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return 'Asia/Taipei';
    }

    protected function setStrip()
    {
        $this->strip = HProfit::profitAccrued(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            factory(ProfitStrip::class)->make()->toArray()
        );
    }

    protected function strip()
    {
        if (empty($this->strip)) {
            $this->setStrip();
        }
        return $this->strip;
    }
}
