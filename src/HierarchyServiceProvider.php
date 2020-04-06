<?php

namespace Hierarchy;

use Illuminate\Support\ServiceProvider;

class HierarchyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../resources/lang/tw' => resource_path('lang/vendor/hierarchy'),
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'hierarchy');
        $this->loadRoutesFrom(__DIR__ . '/route.php');
    }

    public function register()
    {
        $this->app->singleton('hp', HierarchyProfitService::class);
    }
}
