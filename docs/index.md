# Laravel Vue CRUD Builder

Full CRUD scaffolding for Laravel + Vue 3 + Inertia.js.

Point it at a model and it generates form fields and table columns from your database schema. Or wire in explicit
form and table classes for full control. Either way, one command gives you a working index, show, create, edit and
delete flow.

## Who it is for

The CRUD Builder is for Laravel developers who build admin panels, back offices and internal tools with Inertia and
Vue, and who are tired of writing the same controller, validation, list page and form for every model. It does not
hide anything behind a runtime admin framework: it writes ordinary Laravel and Vue files into your project once, and
from then on they are your code to change.

## How it works

The package combines two others:

- The [FormBuilder](https://docs.comfycoders.nl/laravel-vue-form-builder) renders forms defined in a PHP form class
  and validates them with the rules on its fields.
- The [TableBuilder](https://docs.comfycoders.nl/laravel-vue-table-builder) renders sortable, searchable, paginated
  tables defined in a PHP table class.

`make:crud` generates a form class, a table class, a FormRequest, a resource controller and the Vue pages that tie
them together. When you pass a model, it reads the model's database columns and turns them into form fields and table
columns, including relation selects for foreign keys and global search on columns such as `name` and `email`. See
[Auto Schema Detection](auto-schema.md) for the full mapping.

## Quick start

```bash
composer require tranquil-tools/laravel-vue-crud-builder
npm install reka-ui @lucide/vue
php artisan crud-builder:install
php artisan make:crud User --model=App\\Models\\User
```

Register the resource route:

```php
Route::resource('users', \App\Http\Controllers\UserController::class);
```

Open `/users` and you have a paginated list with search and sorting, a detail page, and a create and edit form with
server-side validation. [Installation](installation.md) explains each step and what the installer changes in your
Vite config, `tsconfig.json` and `app.css`.

## What it generates

```txt
app/Http/Controllers/UserController.php
app/Forms/UserForm.php
app/Tables/UserTable.php
app/Http/Requests/UserRequest.php
resources/js/pages/Users/Index.vue
resources/js/pages/Users/Show.vue
resources/js/pages/Users/Form.vue
```

The controller extends Laravel's `Controller` and includes all seven CRUD actions explicitly. The request returns
`UserForm::rules()`, so the validation rules live in the form class. The Vue pages use the FormBuilder and TableBuilder
components. Instead of three pages per resource you can also use one set of shared `Crud/*.vue` pages for every
resource, see [Vue Pages](vue-pages.md).

Existing files are never overwritten unless you pass `--force`. [Scaffolding a Resource](usage.md) lists every option
of `make:crud`.

## When to use it

Use it for resources that need the classic list, detail and form screens. For a single form or table that is not part
of a CRUD flow, such as a contact form or a report, use the FormBuilder's `make:form` or the TableBuilder's
`make:table` directly.

When your project has its own conventions, publish and edit the stubs so every generated resource follows them, see
[Customising Stubs](customising.md). The namespaces for models, forms, tables and requests are set in the
[Configuration](configuration.md).

## Dependencies

- PHP 8.4+
- Laravel 11+
- Inertia.js v2 or v3 (`inertiajs/inertia-laravel: ^2.0|^3.0`)
- Vue 3
- Tailwind CSS v4
- `reka-ui` and `@lucide/vue` (npm)
- [`tranquil-tools/laravel-vue-form-builder`](https://github.com/ComfyCodersBV/laravel-vue-form-builder)*
- [`tranquil-tools/laravel-vue-table-builder`](https://github.com/ComfyCodersBV/laravel-vue-table-builder)*

*) Both form and table builder are pulled in automatically as Composer dependencies, no need to require them separately.
