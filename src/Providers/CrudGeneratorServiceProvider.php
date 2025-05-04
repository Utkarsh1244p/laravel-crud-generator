<?php

namespace UtkarshGayguwal\CrudGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use UtkarshGayguwal\CrudGenerator\Commands\GenerateCrudCommand;

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