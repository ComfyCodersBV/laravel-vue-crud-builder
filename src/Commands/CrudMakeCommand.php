<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CrudMakeCommand extends GeneratorCommand
{
    protected $name = 'make:crud';

    protected $type = 'Controller';

    protected $description = 'Create a new CRUD resource with controller, form, table, and request classes';

    private string $modelFqn = '';

    private string $pageStyle = '';

    public function handle(): int
    {
        $this->modelFqn = $this->resolveModelFqn();
        $this->showDetectedColumns();
        $this->pageStyle = $this->resolvePageStyle();

        if (parent::handle() === false && ! $this->option('force')) {
            return self::FAILURE;
        }

        $this->generateFormClass();
        $this->generateTableClass();
        $this->generateFormRequest();
        $this->generateVuePages();
        $this->printRouteSnippet();

        return self::SUCCESS;
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $resource = $this->resourceName();
        $routePrefix = Str::kebab(Str::plural($resource));
        $modelParam = Str::camel(Str::singular($resource));

        $modelsNs = config('vue-crud-builder.namespaces.models', 'App\\Models');
        $formsNs = config('vue-crud-builder.namespaces.forms', 'App\\Forms');
        $tablesNs = config('vue-crud-builder.namespaces.tables', 'App\\Tables');
        $requestsNs = config('vue-crud-builder.namespaces.requests', 'App\\Http\\Requests');

        $modelFqn = $this->modelFqn ?: $modelsNs.'\\'.$resource;
        $formFqn = $formsNs.'\\'.$resource.'Form';
        $tableFqn = $tablesNs.'\\'.$resource.'Table';
        $requestFqn = $requestsNs.'\\'.$resource.'Request';

        $imports = $this->formatImports([
            $formFqn,
            $modelFqn,
            $requestFqn,
            $tableFqn,
            'Illuminate\\Http\\RedirectResponse',
            'Illuminate\\Routing\\Controller',
            'Inertia\\Inertia',
            'Inertia\\Response',
        ]);

        return str_replace(
            [
                '{{ imports }}',
                '{{ modelClass }}',
                '{{ modelParam }}',
                '{{ formClass }}',
                '{{ tableClass }}',
                '{{ requestClass }}',
                '{{ routePrefix }}',
                '{{ indexPage }}',
                '{{ showPage }}',
                '{{ formPage }}',
                '{{ title }}',
                '{{ singularTitle }}',
            ],
            [
                $imports,
                class_basename($modelFqn),
                $modelParam,
                class_basename($formFqn),
                class_basename($tableFqn),
                class_basename($requestFqn),
                $routePrefix,
                $this->pagePathFor('Index'),
                $this->pagePathFor('Show'),
                $this->pagePathFor('Form'),
                Str::headline(Str::plural($resource)),
                Str::headline($resource),
            ],
            $stub,
        );
    }

    protected function pagePathFor(string $page): string
    {
        if ($this->pageStyle === 'shared') {
            return "Crud/{$page}";
        }

        return Str::studly(Str::plural($this->resourceName()))."/{$page}";
    }

    protected function resolveModelFqn(): string
    {
        if ($model = $this->option('model')) {
            return $model;
        }

        $default = $this->laravel->getNamespace().'Models\\'.$this->resourceName();

        return text(
            label: 'Model class',
            default: $default,
            hint: 'Clear to skip schema detection and generate empty Form/Table.',
        );
    }

    protected function showDetectedColumns(): void
    {
        if (! $this->modelFqn || ! class_exists($this->modelFqn)) {
            if ($this->modelFqn) {
                $this->components->warn("Model [{$this->modelFqn}] not found — generating empty Form/Table.");
            }

            return;
        }

        try {
            $exclude = ['id', 'password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at'];
            $table = (new $this->modelFqn)->getTable();
            $columns = collect(Schema::getColumns($table))
                ->reject(fn ($col) => in_array($col['name'], $exclude, true))
                ->map(fn ($col) => $col['name'].' ('.$col['type_name'].($col['nullable'] ? ', nullable' : '').')')
                ->values()
                ->all();

            if (empty($columns)) {
                $this->components->warn("No columns detected on [{$table}].");

                return;
            }

            $this->components->info('Detected '.count($columns).' column(s) on ['.$table.']:');
            foreach ($columns as $col) {
                $this->line("    - {$col}");
            }
        } catch (\Exception $e) {
            $this->components->warn("Could not read schema for [{$this->modelFqn}]: {$e->getMessage()}");
        }
    }

    protected function getNameInput(): string
    {
        return $this->resourceName().'Controller';
    }

    protected function resourceName(): string
    {
        return Str::studly(Str::replaceLast('Controller', '', trim($this->argument('name'))));
    }

    protected function generateFormClass(): void
    {
        $resource = $this->resourceName();
        $namespace = config('vue-crud-builder.namespaces.forms', 'App\\Forms');
        $className = $resource.'Form';
        $path = $this->classToPath($namespace.'\\'.$className);

        if (file_exists($path) && ! $this->option('force')) {
            $this->components->warn("Form class already exists: [{$path}]");

            return;
        }

        [$imports, $fields] = $this->buildFormContent();

        $stub = file_get_contents($this->resolveStubPath('crud-form.stub'));
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ imports }}', '{{ fields }}'],
            [$namespace, $className, $imports, $fields],
            $stub,
        );

        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);
        $this->components->info("Form class [{$path}] created successfully.");
    }

    protected function generateTableClass(): void
    {
        $resource = $this->resourceName();
        $namespace = config('vue-crud-builder.namespaces.tables', 'App\\Tables');
        $className = $resource.'Table';
        $path = $this->classToPath($namespace.'\\'.$className);

        if (file_exists($path) && ! $this->option('force')) {
            $this->components->warn("Table class already exists: [{$path}]");

            return;
        }

        [$imports, $forReturn, $columns] = $this->buildTableContent();

        $stub = file_get_contents($this->resolveStubPath('crud-table.stub'));
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ imports }}', '{{ forReturn }}', '{{ columns }}'],
            [$namespace, $className, $imports, $forReturn, $columns],
            $stub,
        );

        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);
        $this->components->info("Table class [{$path}] created successfully.");
    }

    protected function generateFormRequest(): void
    {
        $resource = $this->resourceName();

        $this->call('make:form-request', [
            'name'   => $resource.'Request',
            '--form' => $resource.'Form',
        ]);
    }

    protected function buildFormContent(): array
    {
        $baseImports = [
            'TranquilTools\\FormBuilder\\AbstractForm',
            'TranquilTools\\FormBuilder\\Fields\\Submit',
            'TranquilTools\\FormBuilder\\FormConfig',
        ];

        if (! $this->modelFqn || ! class_exists($this->modelFqn)) {
            return [$this->formatImports($baseImports), ''];
        }

        try {
            return $this->schemaToFormContent($this->modelFqn, $baseImports);
        } catch (\Exception $e) {
            $this->components->warn("Schema detection failed ({$e->getMessage()}). Generating empty Form.");

            return [$this->formatImports($baseImports), ''];
        }
    }

    protected function schemaToFormContent(string $modelClass, array $baseImports): array
    {
        $exclude = ['id', 'password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at'];
        $table = (new $modelClass)->getTable();

        $allImports = $baseImports;
        $fieldLines = [];

        foreach (Schema::getColumns($table) as $column) {
            $name = $column['name'];
            $type = $column['type_name'];
            $nullable = (bool) $column['nullable'];

            if (in_array($name, $exclude, true)) {
                continue;
            }

            if (str_ends_with($name, '_id') && in_array($type, ['integer', 'bigint', 'smallint', 'mediumint'], true)) {
                [$line, $extraImports] = $this->buildRelationFieldLine($name, $nullable);
                $fieldLines[] = $line;
                $allImports = array_merge($allImports, $extraImports);
                continue;
            }

            [$fieldClass, $fqcn] = $this->fieldClassFor($name, $type);

            if (is_null($fieldClass)) {
                continue;
            }

            $allImports[] = $fqcn;
            $label = Str::headline($name);
            $req = $nullable ? '' : "\n                ->required()";
            $fieldLines[] = "            {$fieldClass}::make('{$name}')\n                ->label('{$label}'){$req},";
        }

        $imports = $this->formatImports(array_unique($allImports));
        $fields = $fieldLines ? implode("\n\n", $fieldLines)."\n\n" : '';

        return [$imports, $fields];
    }

    protected function fieldClassFor(string $name, string $type): array
    {
        $ns = 'TranquilTools\\FormBuilder\\Fields\\';

        if (str_contains($name, 'email')) {
            return ['Email', $ns.'Email'];
        }

        if (str_contains($name, 'password')) {
            return ['Password', $ns.'Password'];
        }

        if (str_contains($name, 'color') || str_contains($name, 'colour')) {
            return ['Color', $ns.'Color'];
        }

        return match (true) {
            in_array($type, ['boolean', 'tinyint'], true),
            str_starts_with($name, 'is_'),
            str_starts_with($name, 'has_') => ['Toggle', $ns.'Toggle'],

            in_array($type, ['text', 'mediumtext', 'longtext', 'tinytext'], true) => ['Textarea', $ns.'Textarea'],

            in_array($type, ['integer', 'bigint', 'smallint', 'mediumint', 'decimal', 'float', 'double'], true) => ['Number', $ns.'Number'],

            $type === 'date' => ['Date', $ns.'Date'],

            in_array($type, ['datetime', 'timestamp'], true) => ['DateTime', $ns.'DateTime'],

            in_array($type, ['varchar', 'char', 'string'], true) => ['Text', $ns.'Text'],

            default => [null, null],
        };
    }

    protected function buildRelationFieldLine(string $name, bool $nullable): array
    {
        $ns = 'TranquilTools\\FormBuilder\\Fields\\';
        $relationName = Str::studly(Str::replaceLast('_id', '', $name));
        $modelFqn = config('vue-crud-builder.namespaces.models', 'App\\Models').'\\'.$relationName;
        $label = Str::headline($relationName);
        $req = $nullable ? "\n                ->nullable()" : "\n                ->required()";
        $imports = [$ns.'Select'];

        if (class_exists($modelFqn)) {
            $labelCol = $this->detectLabelColumn($modelFqn);
            $imports[] = $modelFqn;
            $options = "\\{$modelFqn}::query()->pluck('{$labelCol}', 'id')->toArray()";
        } else {
            $options = "[] // TODO: add {$relationName} options";
        }

        $line = "            Select::make('{$name}')\n"
            ."                ->label('{$label}')\n"
            ."                ->options({$options}){$req},";

        return [$line, $imports];
    }

    protected function detectLabelColumn(string $modelFqn): string
    {
        try {
            $table = (new $modelFqn)->getTable();
            $columnNames = array_column(Schema::getColumns($table), 'name');
            $candidates = ['name', 'title', 'label', 'email', 'username', 'slug'];

            foreach ($candidates as $candidate) {
                if (in_array($candidate, $columnNames, true)) {
                    return $candidate;
                }
            }
        } catch (\Exception) {
        }

        return 'id';
    }

    protected function buildTableContent(): array
    {
        $baseImports = [
            'Illuminate\\Database\\Eloquent\\Builder',
            'TranquilTools\\TableBuilder\\AbstractTable',
            'TranquilTools\\TableBuilder\\TableBuilder',
        ];

        if (! $this->modelFqn || ! class_exists($this->modelFqn)) {
            return [
                $this->formatImports($baseImports),
                '[]',
                "\n            ->column('id', 'ID', sortable: true)",
            ];
        }

        try {
            return $this->schemaToTableContent($this->modelFqn, $baseImports);
        } catch (\Exception $e) {
            $this->components->warn("Schema detection failed ({$e->getMessage()}). Generating empty Table.");

            return [
                $this->formatImports($baseImports),
                '[]',
                "\n            ->column('id', 'ID', sortable: true)",
            ];
        }
    }

    protected function schemaToTableContent(string $modelClass, array $baseImports): array
    {
        $exclude = ['password', 'remember_token'];
        $basename = class_basename($modelClass);
        $table = (new $modelClass)->getTable();

        $allImports = array_merge($baseImports, ['Illuminate\\Database\\Eloquent\\Builder', $modelClass]);
        $searchCandidates = ['name', 'title', 'email', 'username', 'slug'];
        $searchCols = [];
        $columnLines = [];

        foreach (Schema::getColumns($table) as $column) {
            $name = $column['name'];

            if (in_array($name, $exclude, true)) {
                continue;
            }

            $label = Str::headline($name);
            $sortable = $this->isSortableType($column['type_name']);
            $sortableStr = $sortable ? ', sortable: true' : '';
            $columnLines[] = "            ->column('{$name}', '{$label}'{$sortableStr})";

            if (in_array($name, $searchCandidates, true)) {
                $searchCols[] = "'{$name}'";
            }
        }

        $imports = $this->formatImports(array_unique($allImports));
        $forReturn = $basename.'::query()';
        $searchLine = $searchCols
            ? "            ->withGlobalSearch(columns: [".implode(', ', $searchCols)."])\n"
            : '';
        $columns = "\n".$searchLine.implode("\n", $columnLines);

        return [$imports, $forReturn, $columns];
    }

    protected function isSortableType(string $type): bool
    {
        return in_array($type, [
            'varchar', 'char', 'string',
            'integer', 'bigint', 'smallint', 'mediumint',
            'decimal', 'float', 'double',
            'date', 'datetime', 'timestamp',
            'boolean', 'tinyint',
        ], true);
    }

    protected function formatImports(array $fqcns): string
    {
        sort($fqcns);

        return implode("\n", array_map(fn ($i) => "use {$i};", $fqcns));
    }

    protected function classToPath(string $fqcn): string
    {
        $relative = Str::replaceFirst($this->laravel->getNamespace(), '', $fqcn);

        return app_path(str_replace('\\', DIRECTORY_SEPARATOR, $relative).'.php');
    }

    protected function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function generateVuePages(): void
    {
        if ($this->pageStyle === 'shared') {
            if (! file_exists(resource_path('js/Pages/Crud/Index.vue'))) {
                $this->components->warn('Using shared Crud/ Vue pages. Run [php artisan vendor:publish --tag=crud-builder-pages] to publish them.');
            }

            return;
        }

        $resource = Str::studly(Str::plural($this->resourceName()));
        $basePath = resource_path("js/pages/{$resource}");

        $this->generateVuePage('index-page', "{$basePath}/Index.vue", $resource);
        $this->generateVuePage('show-page', "{$basePath}/Show.vue", $resource);
        $this->generateVuePage('form-page', "{$basePath}/Form.vue", $resource);
    }

    protected function resolvePageStyle(): string
    {
        if ($this->option('shared')) {
            return 'shared';
        }

        if ($this->option('pages')) {
            return 'per-resource';
        }

        $configured = config('vue-crud-builder.pages', 'ask');

        if ($configured !== 'ask') {
            return $configured;
        }

        return select(
            label: 'Which Vue page style?',
            options: [
                'per-resource' => 'Per-resource — generate Index.vue + Form.vue for this resource',
                'shared' => 'Shared — use the published Crud/Index.vue and Crud/Form.vue',
            ],
            default: 'per-resource',
        );
    }

    protected function generateVuePage(string $stub, string $destination, string $resource): void
    {
        if (file_exists($destination) && ! $this->option('force')) {
            $this->components->warn("Vue page already exists: {$destination}");

            return;
        }

        $routePrefix = Str::kebab(Str::plural($this->resourceName()));
        $stubContent = file_get_contents($this->resolveStubPath("{$stub}.stub"));

        $content = str_replace(
            ['{{ resource }}', '{{ routePrefix }}'],
            [$resource, $routePrefix],
            $stubContent,
        );

        $this->ensureDirectory(dirname($destination));
        file_put_contents($destination, $content);
        $this->components->info("Vue page [{$destination}] created successfully.");
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = base_path("stubs/{$stub}"))
            ? $customPath
            : __DIR__.'/../../stubs/'.$stub;
    }

    protected function printRouteSnippet(): void
    {
        $fqcn = $this->qualifyClass($this->getNameInput());
        $routePrefix = Str::kebab(Str::plural($this->resourceName()));

        $this->newLine();
        $this->components->warn('Add to routes/web.php:');
        $this->line("    Route::resource('{$routePrefix}', \\{$fqcn}::class);");
        $this->newLine();
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('crud-controller.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Http\\Controllers';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model FQCN (skips the model prompt)'],
            ['shared', null, InputOption::VALUE_NONE, 'Use shared Crud/ Vue pages'],
            ['pages', null, InputOption::VALUE_NONE, 'Generate per-resource Vue pages'],
        ];
    }
}
