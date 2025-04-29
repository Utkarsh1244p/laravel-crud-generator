<?php

namespace Utkarsh1244p\CrudGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Utkarsh1244p\CrudGenerator\Commands\GenerateCrudCommand;

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