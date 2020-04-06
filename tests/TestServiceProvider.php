<?php
namespace Hierarchy\Tests;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->setLocale('tw');
    }
}
