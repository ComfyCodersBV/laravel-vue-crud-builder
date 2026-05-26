<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use TranquilTools\CrudBuilder\Schema\ColumnTypeMapper;
use TranquilTools\FormBuilder\FormConfig;
use TranquilTools\TableBuilder\TableBuilder;

abstract class AbstractCrud
{
    protected string $model = '';

    protected string $form = '';

    protected string $table = '';

    protected string $request = '';

    protected string $storeRequest = '';

    protected string $updateRequest = '';

    protected string $destroyRequest = '';

    protected string $pages = '';

    protected array $excludedFormColumns = [
        'id',
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $excludedTableColumns = [
        'password',
        'remember_token',
    ];

    public function title(): string
    {
        return Str::headline(Str::plural($this->modelBaseName()));
    }

    public function modelBaseName(): string
    {
        if ($this->model) {
            return class_basename($this->model);
        }

        return Str::replaceLast('Controller', '', class_basename(static::class));
    }

    public function routePrefix(): string
    {
        return Str::kebab(Str::plural($this->modelBaseName()));
    }

    public function routeParam(): string
    {
        return Str::snake(Str::singular($this->modelBaseName()));
    }

    public function hasModel(): bool
    {
        return ! is_null($this->resolveModelClass());
    }

    public function modelClass(): string
    {
        return $this->resolveModelClass()
            ?? throw new \RuntimeException(static::class.' cannot determine model class.');
    }

    public function indexPage(): string
    {
        if ($this->resolvePageStyle() === 'shared') {
            return 'Crud/Index';
        }

        return Str::studly(Str::plural($this->modelBaseName())).'/Index';
    }

    public function formPage(): string
    {
        if ($this->resolvePageStyle() === 'shared') {
            return 'Crud/Form';
        }

        return Str::studly(Str::plural($this->modelBaseName())).'/Form';
    }

    public function showPage(): string
    {
        if ($this->resolvePageStyle() === 'shared') {
            return 'Crud/Show';
        }

        return Str::studly(Str::plural($this->modelBaseName())).'/Show';
    }

    protected function resolvePageStyle(): string
    {
        if ($this->pages !== '') {
            return $this->pages;
        }

        $configured = config('vue-crud-builder.pages', 'ask');

        return $configured !== 'ask' ? $configured : 'per-resource';
    }

    public function buildForm(mixed $model = null): FormConfig
    {
        if ($this->form) {
            $form = $this->form::make();
        } elseif (class_exists($conventionalForm = $this->conventionalFormClass())) {
            $form = $conventionalForm::make();
        } elseif ($this->resolveModelClass()) {
            return $this->buildAutoForm($model);
        } else {
            throw new \RuntimeException(static::class.' must define $model or $form, or have a conventional Form class.');
        }

        if (! is_null($model)) {
            $form->fill($model);
        }

        return $form;
    }

    public function buildTable(): TableBuilder
    {
        $table = $this->resolveTable();

        if (is_null($table->rowLinkCallable) && Route::has($this->routePrefix().'.show')) {
            $routePrefix = $this->routePrefix();
            $table->rowLink(fn ($row) => route($routePrefix.'.show', $row));
        }

        return $table;
    }

    protected function resolveTable(): TableBuilder
    {
        if ($this->table) {
            return $this->table::build();
        }

        $conventionalTable = $this->conventionalTableClass();

        if (class_exists($conventionalTable)) {
            return $conventionalTable::build();
        }

        $modelClass = $this->resolveModelClass();

        if ($modelClass) {
            return $this->buildAutoTable();
        }

        throw new \RuntimeException(static::class.' must define $model or $table, or have a conventional Table class.');
    }

    public function resolveModel(int|string $id): Model
    {
        $modelClass = $this->resolveModelClass();

        if ($modelClass) {
            return $modelClass::findOrFail($id);
        }

        throw new \RuntimeException(static::class.' cannot resolve model: define $model or have a conventional Model class.');
    }

    public function validateCreate(Request $request): array
    {
        if ($class = $this->resolveRequestClass('store')) {
            return $request->validate(app($class)->rules());
        }

        return $this->buildForm()->validate($request);
    }

    public function validateUpdate(Request $request, Model $model): array
    {
        if ($class = $this->resolveRequestClass('update')) {
            return $request->validate(app($class)->rules());
        }

        return $this->buildForm($model)->validate($request);
    }

    protected function conventionalFormClass(): string
    {
        return config('vue-crud-builder.namespaces.forms', 'App\\Forms')
            .'\\'.$this->modelBaseName().'Form';
    }

    protected function conventionalTableClass(): string
    {
        return config('vue-crud-builder.namespaces.tables', 'App\\Tables')
            .'\\'.$this->modelBaseName().'Table';
    }

    protected function conventionalModelClass(): string
    {
        return config('vue-crud-builder.namespaces.models', 'App\\Models')
            .'\\'.$this->modelBaseName();
    }

    protected function conventionalRequestClass(): string
    {
        return config('vue-crud-builder.namespaces.requests', 'App\\Http\\Requests')
            .'\\'.$this->modelBaseName().'Request';
    }

    protected function resolveModelClass(): ?string
    {
        if ($this->model) {
            return $this->model;
        }

        $convention = $this->conventionalModelClass();

        return class_exists($convention) ? $convention : null;
    }

    protected function resolveRequestClass(string $operation = 'store'): ?string
    {
        $explicit = match ($operation) {
            'store'   => $this->storeRequest ?: $this->request,
            'update'  => $this->updateRequest ?: $this->request,
            'destroy' => $this->destroyRequest ?: $this->request,
            default   => $this->request,
        };

        if ($explicit) {
            return $explicit;
        }

        $convention = $this->conventionalRequestClass();

        return class_exists($convention) ? $convention : null;
    }

    protected function buildAutoForm(mixed $model = null): FormConfig
    {
        $modelClass = $this->resolveModelClass();

        $fields = ColumnTypeMapper::formFields(
            $modelClass,
            exclude: $this->excludedFormColumns,
        );

        $config = FormConfig::make($fields);

        if (! is_null($model)) {
            $config->fill($model);
        }

        return $config;
    }

    protected function buildAutoTable(): TableBuilder
    {
        $modelClass = $this->resolveModelClass();
        $table = TableBuilder::for($modelClass::query());

        foreach (ColumnTypeMapper::tableColumns($modelClass, exclude: $this->excludedTableColumns) as $column) {
            $table->column(...$column);
        }

        $table->paginate(25);

        return $table->beforeRender();
    }
}
