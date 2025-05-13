<?php

namespace UtkarshGayguwal\SmartScaffold\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SmartScaffoldCommand extends Command
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
            $this->generateResources($modelName, $fields);
            $this->generateFilters($modelName, $fields);
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
            ['{{ class }}', '{{ table }}', '{{ model }}'],  // Add {{ model }} here
            [
                $modelName, 
                Str::snake(Str::plural($modelName)),
                $modelName  // Add replacement value
            ],
            $stub
        );
        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, $content);
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
            $fieldDefinitions
        );
        
        // Generate Update Request (now same as Store)
        $this->generateRequestFile(
            $modelName, 
            'Update', 
            $fieldDefinitions
        );
    }

    protected function generateRequestFile($modelName, $type, $fields)
    {
        $requestPath = app_path("Http/Requests/{$type}{$modelName}Request.php");
        $rules = [];
        $messages = [];

        foreach ($fields as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            $fieldType = trim($parts[1] ?? 'string');
            
            if ($fieldType === 'foreign') {
                $foreignTable = trim($parts[2] ?? 'users');
                $foreignColumn = trim($parts[3] ?? 'id');
            }
            
            // Skip these fields
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Get rules - no more isRequired parameter
            $fieldRules = $this->getValidationRules(
                $fieldType, 
                $fieldType === 'foreign' ? [$foreignTable, $foreignColumn] : []
            );
            
            $rules[] = "'{$name}' => ['".implode("', '", $fieldRules)."'],";
            
            foreach ($fieldRules as $rule) {
                $messages[] = "'{$name}.{$rule}' => 'The {$name} field must be valid.',";
            }
        }

        $stubName = strtolower($type) . '-request.stub';
        $stub = File::get(__DIR__."/../../resources/stubs/{$stubName}");
        $content = str_replace(
            ['{{ model }}', '{{ rules }}', '{{ messages }}'],
            [$modelName, implode("\n            ", $rules), implode("\n            ", $messages)],
            $stub
        );

        File::ensureDirectoryExists(dirname($requestPath));
        File::put($requestPath, $content);
    }

    protected function getValidationRules($fieldType, $foreign = [])
    {
        $rules = ['required']; // Always required now
        
        if ($fieldType === 'foreign' && $foreign) {
            $foreignTable = $foreign[0];
            $foreignColumn = $foreign[1];
            return array_merge($rules, ["exists:{$foreignTable},{$foreignColumn}"]);
        }
        
        return match($fieldType) {
            'integer', 'bigInteger' => array_merge($rules, ['integer']),
            'float', 'double', 'decimal' => array_merge($rules, ['numeric']),
            'boolean' => array_merge($rules, ['boolean']),
            'date', 'dateTime' => array_merge($rules, ['date']),
            'email' => array_merge($rules, ['email', 'max:255']),
            'text', 'longText' => array_merge($rules, ['string']),
            'json' => array_merge($rules, ['json']),
            default => array_merge($rules, ['string', 'max:255'])
        };
    }

    protected function generateResponseTrait()
    {
        $traitPath = app_path('Traits/Response.php');

        File::ensureDirectoryExists(dirname($traitPath));

        $stub = File::get(__DIR__ . '/../../resources/stubs/response-trait.stub');

        File::put($traitPath, $stub);
    }

    protected function generateController($modelName, $fields = null)
    {
        $controllerPath = app_path("Http/Controllers/{$modelName}Controller.php");
        
        // Choose the appropriate stub based on fields presence
        $stubName = $fields ? 'controller-with-fields.stub' : 'controller-no-fields.stub';
        $stub = File::get(__DIR__."/../../resources/stubs/{$stubName}");
        
        $replacements = [
            '{{ model }}' => $modelName,
            '{{ modelVariable }}' => Str::camel($modelName),
        ];

        // Add fields replacement if they exist
        if ($fields) {
            $replacements['{{ fields }}'] = $this->generateFieldMappings($fields);
        }

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::ensureDirectoryExists(dirname($controllerPath));
        File::put($controllerPath, $content);
    }

    protected function generateFieldMappings($fields)
    {
        $fieldDefinitions = explode(',', $fields);
        $mappings = [];
        
        foreach ($fieldDefinitions as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            $mappings[] = "'{$name}' => \$request->input('{$name}')";
        }
        
        return implode(",\n                ", $mappings);
    }

    //Code for Resources
    protected function generateResources($modelName, $fields)
    {
        $this->generateResourceFile($modelName, $fields);
        $this->generateResourceCollection($modelName);
    }

    protected function generateResourceFile($modelName, $fields)
    {
        $resourcePath = app_path("Http/Resources/{$modelName}Resource.php");
        
        $fieldDefinitions = explode(',', $fields);
        $resourceFields = [];
        
        // Always include ID first
        $resourceFields[] = "'id' => \$this->id,";
        
        foreach ($fieldDefinitions as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            
            // Skip these fields (we already added id)
            if (!in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $resourceFields[] = "'{$name}' => \$this->{$name},";
            }
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/resource.stub');
        $content = str_replace(
            ['{{ model }}', '{{ fields }}'],
            [$modelName, implode("\n            ", $resourceFields)],
            $stub
        );

        File::ensureDirectoryExists(dirname($resourcePath));
        File::put($resourcePath, $content);
    }

    protected function generateResourceCollection($modelName)
    {
        $collectionPath = app_path("Http/Resources/{$modelName}Collection.php");
        $stub = File::get(__DIR__.'/../../resources/stubs/resource-collection.stub');
        
        $content = str_replace(
            '{{ model }}',
            $modelName,
            $stub
        );

        File::ensureDirectoryExists(dirname($collectionPath));
        File::put($collectionPath, $content);
    }

    //Filtering Functionality Work
    protected function generateFilters($modelName, $fields)
    {
        // Create base QueryFilter if doesn't exist
        $this->generateBaseFilter();
        
        // Create model-specific filter
        $this->generateModelFilter($modelName, $fields);
    }

    protected function generateBaseFilter()
    {
        $filterPath = app_path('Filters/QueryFilter.php');
        
        if (!File::exists($filterPath)) {
            File::ensureDirectoryExists(dirname($filterPath));
            File::put(
                $filterPath,
                File::get(__DIR__.'/../../resources/stubs/query-filter.stub')
            );
        }
    }

    protected function generateModelFilter($modelName, $fields)
    {
        $filterPath = app_path("Filters/{$modelName}Filter.php");
        $fieldDefinitions = explode(',', $fields);
        
        $methods = [];
        foreach ($fieldDefinitions as $field) {
            $parts = explode(':', $field);
            $name = trim($parts[0]);
            
            if (!in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $methods[] = $this->generateFilterMethod($name, $parts[1] ?? 'string');
            }
        }

        $stub = File::get(__DIR__.'/../../resources/stubs/model-filter.stub');
        $content = str_replace(
            ['{{ model }}', '{{ methods }}'],
            [$modelName, implode("\n\n    ", $methods)],
            $stub
        );

        File::ensureDirectoryExists(dirname($filterPath));
        File::put($filterPath, $content);
    }

    protected function generateFilterMethod($fieldName, $fieldType)
    {
        $methodName = Str::camel($fieldName);
        
        $method = "public function {$methodName}(\$value)\n";
        $method .= "{\n";
        
        switch ($fieldType) {
            case 'string':
            case 'text':
                $method .= "    return \$this->builder->where('{$fieldName}', 'LIKE', \"%{\$value}%\");\n";
                break;
            case 'integer':
            case 'bigInteger':
            case 'float':
            case 'decimal':
                $method .= "    if (str_contains(\$value, ',')) {\n";
                $method .= "        \$values = explode(',', \$value);\n";
                $method .= "        return \$this->builder->whereBetween('{$fieldName}', \$values);\n";
                $method .= "    }\n";
                $method .= "    return \$this->builder->where('{$fieldName}', \$value);\n";
                break;
            case 'date':
            case 'datetime':
                $method .= "    if (str_contains(\$value, ',')) {\n";
                $method .= "        \$dates = explode(',', \$value);\n";
                $method .= "        return \$this->builder->whereBetween('{$fieldName}', \$dates);\n";
                $method .= "    }\n";
                $method .= "    return \$this->builder->whereDate('{$fieldName}', \$value);\n";
                break;
            case 'boolean':
                $method .= "    \$boolValue = filter_var(\$value, FILTER_VALIDATE_BOOLEAN);\n";
                $method .= "    return \$this->builder->where('{$fieldName}', \$boolValue);\n";
                break;
            default:
                $method .= "    return \$this->builder->where('{$fieldName}', \$value);\n";
        }
        
        $method .= "}";
        
        return $method;
    }
}