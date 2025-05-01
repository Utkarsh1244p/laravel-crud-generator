<?php

namespace Utkarsh1244p\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'auto:generate {model} {--fields=}';
    protected $description = 'Generate CRUD Model, Controller, Migration and Routes';

    public function handle()
    {
        $modelName = $this->argument('model');
        $fields = $this->option('fields');

        // Generate Model
        $this->generateModel($modelName);

        // Generate Controller
        $this->generateController($modelName);

        // Generate Migration if fields are provided
        if ($fields) {
            $this->generateMigration($modelName, $fields);
        }

        // Add API Resource Route
        $this->addApiResourceRoute($modelName);

        $this->info("CRUD files for {$modelName} created successfully!");
        if ($fields) {
            $this->info("Migration file created with fields: {$fields}");
        }
    }

    protected function generateModel($modelName)
    {
        $modelPath = app_path("Models/{$modelName}.php");
        $stub = File::get(__DIR__.'/../../resources/stubs/model.stub');
        $content = str_replace(
            ['{{ class }}', '{{ table }}'],
            [$modelName, Str::snake(Str::plural($modelName))],
            $stub
        );
        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, $content);
    }

    protected function generateController($modelName)
    {
        $controllerPath = app_path("Http/Controllers/{$modelName}Controller.php");
        $stub = File::get(__DIR__.'/../../resources/stubs/controller.stub');
        $content = str_replace(
            ['{{ model }}', '{{ modelVariable }}'],
            [$modelName, Str::camel($modelName)],
            $stub
        );
        File::ensureDirectoryExists(dirname($controllerPath));
        File::put($controllerPath, $content);
    }

    protected function generateMigration($modelName, $fields)
    {
        $tableName = Str::snake(Str::plural($modelName));
        $migrationName = "create_{$tableName}_table";
        $timestamp = date('Y_m_d_His');

        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        $stub = File::get(__DIR__.'/../../resources/stubs/migration.stub');
        $schemaContent = $this->buildSchemaContent($fields);
        
        $content = str_replace(
            ['{{ table }}', '{{ schema }}'],
            [$tableName, $schemaContent],
            $stub
        );

        File::put($migrationPath, $content);
    }

    protected function buildSchemaContent($fields)
    {
        $fieldDefinitions = explode(',', $fields);
        $schemaLines = ["\$table->id();"];

        foreach ($fieldDefinitions as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            $type = trim($parts[1] ?? 'string');
            
            // Parse modifiers (nullable, default, unique, index)
            $modifiers = array_slice($parts, 2);
            $columnDefinition = "\$table->{$type}('{$name}')";

            foreach ($modifiers as $modifier) {
                $modifier = trim($modifier);
                
                // Handle nullable/required
                if ($modifier === 'nullable') {
                    $columnDefinition .= "->nullable()";
                } elseif ($modifier === 'required') {
                    // No modifier needed (default behavior)
                }
                // Handle default values with default(value) syntax
                elseif (preg_match('/^default\((.*)\)$/', $modifier, $matches)) {
                    $defaultValue = $matches[1];
                    $columnDefinition .= "->default('{$defaultValue}')";
                }
                // Handle unique/index
                elseif ($modifier === 'unique') {
                    $columnDefinition .= "->unique()";
                } elseif ($modifier === 'index') {
                    $columnDefinition .= "->index()";
                }
                // Handle foreign keys
                elseif ($modifier === 'foreign') {
                    $foreignTable = trim($parts[3] ?? 'users');
                    $foreignColumn = trim($parts[4] ?? 'id');
                    $columnDefinition = "\$table->foreignId('{$name}')->constrained('{$foreignTable}', '{$foreignColumn}')";
                    
                    // Handle onDelete if specified
                    if (in_array('cascade', $modifiers)) {
                        $columnDefinition .= "->onDelete('cascade')";
                    } elseif (in_array('setNull', $modifiers)) {
                        $columnDefinition .= "->onDelete('set null')";
                    }
                }
            }

            $schemaLines[] = $columnDefinition . ";";
        }

        $schemaLines[] = "\$table->timestamps();";
        $schemaLines[] = "\$table->softDeletes();";

        return implode("\n            ", $schemaLines);
    }

    protected function addApiResourceRoute($modelName)
    {
        $apiRoutesPath = base_path('routes/api.php');
        $routeName = Str::kebab(Str::plural($modelName));
        $controllerName = "{$modelName}Controller";

        $useStatement = "use App\Http\Controllers\\{$controllerName};";
        $routeDefinition = "Route::apiResource('{$routeName}', {$controllerName}::class);";

        $contents = File::get($apiRoutesPath);

        // Add use statement if not exists
        if (!Str::contains($contents, $useStatement)) {
            $contents = preg_replace('/<\?php\n/', "<?php\n\n{$useStatement}\n", $contents);
        }

        // Add route if not exists
        if (!Str::contains($contents, $routeDefinition)) {
            $contents .= "\n{$routeDefinition}\n";
        }

        File::put($apiRoutesPath, $contents);
    }
}