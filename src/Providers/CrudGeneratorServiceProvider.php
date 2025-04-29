<?php

namespace YourVendor\CrudGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use YourVendor\CrudGenerator\Commands\GenerateCrudCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCrudCommand::class,
            ]);
        }
    }
}