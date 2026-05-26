# Scaffolding a CRUD Resource

```bash
php artisan make:crud User
```

You will be prompted for the model class, pre-filled with a guess based on the resource name (e.g. `App\Models\User`).
Press Enter to accept, or clear the field to skip schema detection and generate empty Form/Table stubs.

Pass `--model` to skip the prompt entirely:

```bash
php artisan make:crud User --model=App\\Domain\\Auth\\Models\\User
```

## What gets generated

| File                                      | Description                               |
|-------------------------------------------|-------------------------------------------|
| `app/Http/Controllers/UserController.php` | Explicit controller with all CRUD methods |
| `app/Forms/UserForm.php`                  | Form class with schema-detected fields    |
| `app/Tables/UserTable.php`                | Table class with schema-detected columns  |
| `app/Http/Requests/UserRequest.php`       | FormRequest backed by `UserForm::rules()` |
| `resources/js/pages/Users/Index.vue`      | Inertia index page                        |
| `resources/js/pages/Users/Form.vue`       | Inertia create/edit page                  |

Then register the route:

```php
Route::resource('users', \App\Http\Controllers\UserController::class);
```

## The generated controller

The controller extends Laravel's `Controller` and includes all CRUD methods explicitly, using model route binding and
the generated form, table, and request classes:

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

    // show, create, edit, update, destroy …
}
```

All methods are written out explicitly — customise the controller directly.

## Available options

| Option         | Description                                   |
|----------------|-----------------------------------------------|
| `--model=FQCN` | Model FQCN — skips the interactive prompt     |
| `--shared`     | Use shared `Crud/Index.vue` + `Crud/Form.vue` |
| `--pages`      | Generate per-resource Vue pages               |
| `--force`      | Overwrite existing files                      |
