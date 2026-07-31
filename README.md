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

Then run the installer, which publishes the config, adds the `@form-builder` / `@table-builder` aliases to your Vite
config, adds the Tailwind `@source` directives to `resources/css/app.css`, and wires up the persistent Inertia layout:

```bash
php artisan crud-builder:install
```

See [docs/installation.md](docs/installation.md) for exactly what is written where, the publish tags, and what to do
when a step cannot be applied automatically.

## Usage

### Scaffold a CRUD resource

```bash
php artisan make:crud User
```

You will be prompted for the model class (pre-filled guess based on the resource name). Press Enter to accept, or clear
to skip schema detection and generate empty Form/Table classes.

This generates:

- `app/Http/Controllers/UserController.php` - explicit controller with all CRUD methods
- `app/Forms/UserForm.php` - form class with schema-detected fields
- `app/Tables/UserTable.php` - table class with schema-detected columns
- `app/Http/Requests/UserRequest.php` - form request backed by `UserForm::rules()`
- `resources/js/pages/Users/Index.vue`, `Show.vue` and `Form.vue` - Inertia pages

Then register the route in `routes/web.php`:

```php
Route::resource('users', \App\Http\Controllers\UserController::class);
```

The generated controller extends Laravel's `Controller` and includes all CRUD methods explicitly, using model route
binding and the generated form, table, and request classes:

```php
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'table' => UserTable::build()->rowLink(fn ($row) => route('users.show', $row)),
            'title' => 'Users',
            'createRoute' => route('users.create'),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    // edit, update, destroy …
}
```

All methods are written out explicitly, so you can customize the controller directly.

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
| `text`, `longtext`                     | `Textarea` | -            |
| `integer`, `bigint`, `decimal`         | `Number`   | sortable     |
| `boolean`, `tinyint` / `is_*`, `has_*` | `Toggle`   | sortable     |
| `date`                                 | `Date`     | sortable     |
| `datetime`, `timestamp`                | `DateTime` | sortable     |
| name contains `email`                  | `Email`    | sortable     |
| name contains `password`               | `Password` | -            |
| name contains `color`                  | `Color`    | sortable     |
| name ends with `_id` (integer)         | `Select`   | sortable     |

Columns `id`, `password`, `remember_token`, `email_verified_at`, `created_at`, `updated_at`, `deleted_at` are excluded
from forms by default; `password` and `remember_token` are excluded from tables. See
[docs/auto-schema.md](docs/auto-schema.md) for the full mapping, relation selects and global search.

### Vue pages

The generated `Index.vue`, `Show.vue` and `Form.vue` render the TableBuilder and FormBuilder components from the
`@table-builder` / `@form-builder` aliases and receive everything they need as Inertia props (`table`, `form`,
`record`, `title` and the route props). See [docs/vue-pages.md](docs/vue-pages.md) for the full page contents and the
props each page accepts.

### Shared Vue pages

Instead of generating per-resource pages, publish the shared `Crud/Index.vue`, `Crud/Show.vue` and `Crud/Form.vue`
pages once and reuse them across all CRUD resources:

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
  show-page.stub        # the Show Vue page
```

See [docs/customising.md](docs/customising.md) for the placeholders each stub supports.

## Configuration

```php
// config/vue-crud-builder.php

return [
    // 'per-resource' - generate Index.vue + Show.vue + Form.vue per resource
    // 'shared'       - use the published Crud/ pages
    // 'ask'          - prompt during make:crud (default)
    'pages' => env('CRUD_BUILDER_PAGES', 'ask'),

    // Namespaces used during code generation
    'namespaces' => [
        'models' => 'App\\Models',
        'forms' => 'App\\Forms',
        'tables' => 'App\\Tables',
        'requests' => 'App\\Http\\Requests',
    ],
];
```

See [docs/configuration.md](docs/configuration.md) for what each namespace is used for.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [ComfyCoders B.V.](https://comfycoders.nl)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
