<?php

namespace YourVendor\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = '{model}';
    protected $description = 'Generate CRUD Model and Controller';

    public function handle()
    {
        $modelName = $this->argument('model');
        $controllerName = "{$modelName}Controller";

        // Generate Model
        $modelPath = app_path("Models/{$modelName}.php");
        $this->createFromStub('model.stub', $modelPath, [
            '{{ class }}' => $modelName,
            '{{ table }}' => Str::snake(Str::pluralStudly($modelName)),
        ]);

        // Generate Controller
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");
        $this->createFromStub('controller.stub', $controllerPath, [
            '{{ model }}' => $modelName,
            '{{ modelVariable }}' => Str::camel($modelName),
        ]);

        $this->info("CRUD files for {$modelName} created successfully!");
    }

    protected function createFromStub($stubName, $path, $replacements)
    {
        if (File::exists($path)) {
            $this->error("File already exists: {$path}");
            return;
        }

        $stub = File::get(__DIR__."/../../resources/stubs/{$stubName}");
        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }
}