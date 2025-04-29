<?php

namespace Utkarsh1244p\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'auto:generate {model}';
    protected $description = 'Generate CRUD Model and Controller';

    public function handle()
    {
        $modelName = $this->argument('model');
        $controllerName = "{$modelName}Controller";
        $routeName = Str::kebab(Str::plural($modelName));

        // Ensure API routes file exists
        $this->ensureApiRoutesFileExists();

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

        // Add route to api.php
        $this->addApiResourceRoute($modelName, $routeName);

        $this->info("CRUD files for {$modelName} created successfully!");
        $this->info("API Resource route added for {$routeName}");
    }

    protected function ensureApiRoutesFileExists()
    {
        $apiRoutesPath = base_path('routes/api.php');
        
        if (!File::exists($apiRoutesPath)) {
            $this->info('API routes file not found. Installing...');
            
            // Method 1: Use Sanctum's installer (if installed)
            if (class_exists('Laravel\Sanctum\SanctumServiceProvider')) {
                $process = new Process(['php', 'artisan', 'api:install']);
                $process->run();
            } 
            // Method 2: Create basic API routes file
            else {
                File::put($apiRoutesPath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n");
                $this->info('Created basic routes/api.php file');
            }
        }
    }

    protected function addApiResourceRoute($controllerName, $routeName)
    {
        $apiRoutesPath = base_path('routes/api.php');
        $routeDefinition = "Route::apiResource('{$routeName}', \\App\\Http\\Controllers\\{$controllerName}Controller::class);";
        
        // Check if route already exists
        if (Str::contains(file_get_contents($apiRoutesPath), $routeDefinition)) {
            return;
        }

        // Add route with newline before it
        file_put_contents(
            $apiRoutesPath,
            "\n".$routeDefinition."\n",
            FILE_APPEND
        );
    }
}