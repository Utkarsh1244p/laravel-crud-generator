<?php

namespace UtkarshGayguwal\SmartScaffold\Providers;

use Illuminate\Support\ServiceProvider;
use UtkarshGayguwal\SmartScaffold\Commands\SmartScaffoldCommand;

class SmartScaffoldServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SmartScaffoldCommand::class,
            ]);
        }
    }
}