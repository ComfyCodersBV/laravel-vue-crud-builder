# Laravel Vue CRUD Builder

Full CRUD scaffolding for Laravel + Vue 3 + Inertia.js, built on top
of [laravel-vue-form-builder](https://github.com/ComfyCodersBV/laravel-vue-form-builder)
and [laravel-vue-table-builder](https://github.com/ComfyCodersBV/laravel-vue-table-builder).

One command scaffolds a complete CRUD resource: controller, form class, table class, form request, and Vue pages. Point
it at a model and fields/columns are generated from your database schema automatically.

## Installation

```bash
composer require tranquil-tools/laravel-vue-crud-builder
```

Install the required npm packages:

```bash
npm install reka-ui lucide-vue-next
```

Then run the installer, which publishes the config and patches `vite.config.ts` and `resources/css/app.css`:

```bash
php artisan crud-builder:install
```

## Usage

### Scaffold a CRUD resource

```bash
php artisan make:crud User
```

You will be prompted for the model class (pre-filled guess based on the resource name). Press Enter to accept, or clear
to skip schema detection and generate empty Form/Table classes.

This generates:

- `app/Http/Controllers/UserController.php` — thin controller, extends `CrudController`
- `app/Forms/UserForm.php` — form class with schema-detected fields
- `app/Tables/UserTable.php` — table class with schema-detected columns
- `app/Http/Requests/UserRequest.php` — form request backed by `UserForm::rules()`
- `resources/js/pages/Users/Index.vue` and `Form.vue` — Inertia pages

Then register the route in `routes/web.php`:

```php
Route::resource('users', \App\Http\Controllers\UserController::class);
```

The generated controller is thin — it relies on convention to find the right classes:

```php
class UserController extends CrudController
{
    //
}
```

`CrudController` handles `index`, `create`, `store`, `edit`, `update`, and `destroy` automatically.

### Convention-based resolution

`CrudController` resolves classes by convention at runtime. For `UserController`:

| Class                           | Resolved                         |
|---------------------------------|----------------------------------|
| `App\Forms\UserForm`            | Form fields and validation rules |
| `App\Tables\UserTable`          | Table columns and query          |
| `App\Http\Requests\UserRequest` | FormRequest for store and update |
| `App\Models\User`               | Eloquent model                   |

All four paths are configurable via `config/vue-crud-builder.php`. Override any of them with explicit properties on the
controller:

```php
class UserController extends CrudController
{
    protected string $form  = UserForm::class;
    protected string $table = UserTable::class;
}
```

For separate FormRequests per operation:

```php
class UserController extends CrudController
{
    protected string $storeRequest  = CreateUserRequest::class;
    protected string $updateRequest = UpdateUserRequest::class;
}
```

Available request properties: `$request` (both store and update), `$storeRequest`, `$updateRequest`, `$destroyRequest`.

### Auto schema detection

When a model is provided during `make:crud`, the generated `UserForm` and `UserTable` are pre-populated with fields and
columns derived from the database schema:

```bash
php artisan make:crud User --model=App\\Models\\User
```

Column type mapping:

| Database type                          | Form field | Table column |
|----------------------------------------|------------|--------------|
| `varchar`, `char`                      | `Text`     | sortable     |
| `text`, `longtext`                     | `Textarea` | —            |
| `integer`, `bigint`, `decimal`         | `Number`   | sortable     |
| `boolean`, `tinyint` / `is_*`, `has_*` | `Toggle`   | sortable     |
| `date`                                 | `Date`     | sortable     |
| `datetime`, `timestamp`                | `DateTime` | sortable     |
| name contains `email`                  | `Email`    | sortable     |
| name contains `password`               | `Password` | excluded     |
| name contains `color`                  | `Color`    | sortable     |

Columns `id`, `password`, `remember_token`, `email_verified_at`, `created_at`, `updated_at`, `deleted_at` are excluded
from forms by default.

### Vue pages

The generated `Index.vue` and `Form.vue` use the table and form builder components:

```vue
<!-- Users/Index.vue -->
<script setup lang="ts">
    import TableBuilder from '@table-builder/components/TableBuilder.vue'
    import type {TableData} from '@table-builder/types/table-builder'

    defineProps<{ table: TableData; title: string; createRoute: string }>()
</script>

<template>
    <TableBuilder :table="table"/>
</template>
```

```vue
<!-- Users/Form.vue -->
<script setup lang="ts">
    import Form from '@form-builder/components/Form.vue'
    import type {FormSchema} from '@form-builder/types/form-builder'

    defineProps<{ form: FormSchema; title: string }>()
</script>

<template>
    <Form :schema="form"/>
</template>
```

### Shared Vue pages

Instead of generating per-resource pages, publish shared `Crud/Index.vue` and `Crud/Form.vue` pages once and reuse them
across all CRUD resources:

```bash
php artisan vendor:publish --tag=crud-builder-pages
```

Set in `config/vue-crud-builder.php`:

```php
'pages' => 'shared',   // 'per-resource' | 'shared' | 'ask' (default)
```

Or pass `--shared` / `--pages` to `make:crud` to override per-command.

## Customising stubs

Publish stubs to the project's `stubs/` directory to override the generated output:

```bash
php artisan vendor:publish --tag=crud-builder-stubs
```

```
stubs/
  crud-controller.stub  # the controller
  crud-form.stub        # the Form class
  crud-table.stub       # the Table class
  index-page.stub       # the Index Vue page
  form-page.stub        # the Form Vue page
```

## Configuration

```php
// config/vue-crud-builder.php

return [
    // 'per-resource' — generate Index.vue + Form.vue per resource (default)
    // 'shared'       — use published Crud/Index.vue + Crud/Form.vue
    // 'ask'          — prompt during make:crud
    'pages' => env('CRUD_BUILDER_PAGES', 'ask'),

    // Namespaces for convention-based class resolution and code generation
    'namespaces' => [
        'models'   => 'App\\Models',
        'forms'    => 'App\\Forms',
        'tables'   => 'App\\Tables',
        'requests' => 'App\\Http\\Requests',
    ],
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [ComfyCoders B.V.](https://comfycoders.nl)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
