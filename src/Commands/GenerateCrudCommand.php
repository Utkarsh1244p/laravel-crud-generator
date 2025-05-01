<?php

namespace Utkarsh1244p\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'make:crud {model} {--fields=}';
    protected $description = 'Generate CRUD Model, Controller, Migration and Routes';

    public function handle()
    {
        $modelName = $this->argument('model');
        $fields = $this->option('fields');

        // Generate Response Trait
        $this->generateResponseTrait();

        // Generate Model
        $this->generateModel($modelName);

        // Generate Controller
        $this->generateController($modelName, $fields ?: null);

        // Generate Migration if fields are provided
        if ($fields) {
            $this->generateMigration($modelName, $fields);
            $this->generateFactory($modelName, $fields);
            $this->generateRequestFiles($modelName, $fields);
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

    protected function generateController($modelName, $fields = null)
    {
        $controllerPath = app_path("Http/Controllers/{$modelName}Controller.php");
        $stub = File::get(__DIR__.'/../../resources/stubs/controller.stub');
        
        $replacements = [
            '{{ model }}' => $modelName,
            '{{ modelVariable }}' => Str::camel($modelName),
        ];
        
        // Only parse fields if they exist
        if ($fields) {
            $replacements['{{ fields }}'] = $this->getFieldNames($fields);
        }
        
        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
        
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
            $parts = array_map('trim', explode(':', $field));
            $name = $parts[0];
            $type = $parts[1] ?? 'string';
            
            // Skip explicit 'id' field
            if ($name === 'id') {
                continue;
            }

            $modifiers = array_slice($parts, 2);
            
            // Handle foreign keys separately
            if ($type === 'foreign') {
                $foreignTable = $parts[2] ?? 'users';
                $foreignColumn = $parts[3] ?? 'id';
                
                $schemaLines[] = "\$table->unsignedBigInteger('{$name}');";
                
                $fkConstraint = "\$table->foreign('{$name}')"
                            . "->references('{$foreignColumn}')"
                            . "->on('{$foreignTable}')";
                
                if (in_array('cascade', $modifiers)) {
                    $fkConstraint .= "->onDelete('cascade')";
                } elseif (in_array('setNull', $modifiers)) {
                    $fkConstraint .= "->onDelete('set null')";
                }
                
                $schemaLines[] = $fkConstraint . ";";
                continue;
            }

            // Regular field definition
            $columnDefinition = "\$table->{$type}('{$name}')";

            foreach ($modifiers as $modifier) {
                switch (true) {
                    case $modifier === 'nullable':
                        $columnDefinition .= "->nullable()";
                        break;
                        
                    case preg_match('/^default\((.*)\)$/', $modifier, $matches):
                        $columnDefinition .= "->default('{$matches[1]}')";
                        break;
                        
                    case $modifier === 'unique':
                        $columnDefinition .= "->unique()";
                        break;
                        
                    case $modifier === 'index':
                        $columnDefinition .= "->index()";
                        break;
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

    protected function generateFactory($modelName, $fields)
    {
        $factoryPath = database_path("factories/{$modelName}Factory.php");
        
        $fieldDefinitions = explode(',', $fields);
        $factoryFields = [];
        
        foreach ($fieldDefinitions as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            $type = trim($parts[1] ?? 'string');
            
            // Skip id and timestamps (handled later)
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Handle foreign keys differently
            if ($type === 'foreign') {
                $foreignTable = trim($parts[2] ?? 'users');
                $factoryFields[] = "'{$name}' => \\App\\Models\\".Str::studly(Str::singular($foreignTable))."::factory(),";
                continue;
            }

            // Generate fake data based on field type
            $factoryFields[] = $this->getFactoryFieldDefinition($name, $type);
        }
        
        // Add timestamps
        $factoryFields[] = "'created_at' => now(),";
        $factoryFields[] = "'updated_at' => now(),";
        
        $stub = File::get(__DIR__.'/../../resources/stubs/factory.stub');
        $content = str_replace(
            ['{{ model }}', '{{ fields }}'],
            [$modelName, implode("\n            ", $factoryFields)],
            $stub
        );
        
        File::ensureDirectoryExists(dirname($factoryPath));
        File::put($factoryPath, $content);
    }

    protected function getFactoryFieldDefinition($name, $type)
    {
        return match($type) {
            'integer', 'bigInteger' => "'{$name}' => \$this->faker->randomNumber(),",
            'float', 'double', 'decimal' => "'{$name}' => \$this->faker->randomFloat(2, 0, 1000),", 
            'boolean' => "'{$name}' => \$this->faker->boolean,",
            'date', 'dateTime' => "'{$name}' => \$this->faker->dateTime,",
            'text', 'longText' => "'{$name}' => \$this->faker->text,",
            'json' => "'{$name}' => json_encode(['key' => 'value']),",
            default => "'{$name}' => \$this->faker->word," // string and others
        };
    }
    protected function generateRequestFiles($modelName, $fields)
    {
        $fieldDefinitions = explode(',', $fields);
        
        // Generate Store Request
        $this->generateRequestFile(
            $modelName, 
            'Store', 
            $fieldDefinitions,
            ['required', 'string', 'max:255'] // Default rules for store
        );
        
        // Generate Update Request
        $this->generateRequestFile(
            $modelName, 
            'Update', 
            $fieldDefinitions,
            ['sometimes', 'string', 'max:255'] // Default rules for update
        );
    }

    protected function generateRequestFile($modelName, $type, $fields, $defaultRules)
    {
        $requestPath = app_path("Http/Requests/{$type}{$modelName}Request.php");
        $rules = [];
        $messages = [];

        foreach ($fields as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            $fieldType = trim($parts[1] ?? 'string');
            
            // Skip these fields
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Get rules based on field type
            $fieldRules = $this->getValidationRules($fieldType, $type === 'Store');
            $rules[] = "'{$name}' => ['".implode("', '", $fieldRules)."'],";
            
            // Generate messages
            foreach ($fieldRules as $rule) {
                $messages[] = "'{$name}.{$rule}' => 'The {$name} field must be valid.',";
            }
        }

        $stubName = strtolower($type) . '-request.stub';
        $stub = File::get(__DIR__ . "/../../resources/stubs/{$stubName}");
        $content = str_replace(
            ['{{ model }}', '{{ rules }}', '{{ messages }}'],
            [$modelName, implode("\n            ", $rules), implode("\n            ", $messages)],
            $stub
        );

        File::ensureDirectoryExists(dirname($requestPath));
        File::put($requestPath, $content);
    }

    protected function getValidationRules($fieldType, $isRequired = true)
    {
        $rules = $isRequired ? ['required'] : ['sometimes'];
        
        return match($fieldType) {
            'integer', 'bigInteger' => array_merge($rules, ['integer']),
            'float', 'double', 'decimal' => array_merge($rules, ['numeric']),
            'boolean' => array_merge($rules, ['boolean']),
            'date', 'dateTime' => array_merge($rules, ['date']),
            'email' => array_merge($rules, ['email', 'max:255']),
            'text', 'longText' => array_merge($rules, ['string']),
            'json' => array_merge($rules, ['json']),
            'foreign' => ['exists:'.($parts[2] ?? 'users').','.($parts[3] ?? 'id')],
            default => array_merge($rules, ['string', 'max:255']) // string and others
        };
    }

    protected function generateResponseTrait()
    {
        $traitPath = app_path('Traits/Response.php');

        File::ensureDirectoryExists(dirname($traitPath));

        $stub = File::get(__DIR__ . '/../../resources/stubs/response-trait.stub');

        File::put($traitPath, $stub);
    }

    protected function getFieldNames($fields)
    {
        return array_map(function($field) {
            $parts = explode(':', $field);
            return [
                'name' => trim($parts[0]),
                'type' => trim($parts[1] ?? 'string')
            ];
        }, explode(',', $fields));
    }
}