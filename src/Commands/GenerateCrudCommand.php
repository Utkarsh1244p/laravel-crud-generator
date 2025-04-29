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

        // Add route to api.php
        $this->addApiResourceRoute($modelName, $routeName);

        $this->info("CRUD files for {$modelName} created successfully!");
        $this->info("API Resource route added for {$routeName}");
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

    protected function addApiResourceRoute($modelName)
    {
        $apiRoutesPath = base_path('routes/api.php');
        $routeName = Str::kebab(Str::plural($modelName));
        $controllerName = "{$modelName}Controller";
        
        // 1. Add the use statement at top
        $this->addUseStatement($apiRoutesPath, $controllerName);
        
        // 2. Add the simplified route
        $routeDefinition = "Route::apiResource('{$routeName}', {$controllerName}::class);";
        
        if (Str::contains(File::get($apiRoutesPath), $routeDefinition)) {
            return;
        }
        
        File::append($apiRoutesPath, "\n{$routeDefinition}\n");
    }

    protected function addUseStatement($filePath, $controllerName)
    {
        $contents = File::get($filePath);
        $useStatement = "use App\Http\Controllers\\{$controllerName};";
        
        // Don't add if already exists
        if (Str::contains($contents, $useStatement)) {
            return;
        }
        
        // Insert after opening PHP tag or namespace
        if (Str::contains($contents, 'namespace ')) {
            $contents = preg_replace(
                '/(namespace .+?;\n)/',
                "$1\n{$useStatement}\n",
                $contents
            );
        } else {
            $contents = preg_replace(
                '/<\?php\n/',
                "<?php\n\n{$useStatement}\n",
                $contents
            );
        }
        
        File::put($filePath, $contents);
    }
}