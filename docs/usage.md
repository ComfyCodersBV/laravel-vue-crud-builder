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

The command prompts up to three times: for the model class, for the [Vue page style](vue-pages.md) (only when the
`pages` config is `ask`) and for the delete button. `--model`, `--shared` / `--pages` and `--destroy` each skip the
matching prompt, so passing all of them makes the command fully non-interactive. When a model is given, its detected
columns are printed before generation starts.

## What gets generated

| File                                      | Description                               |
|-------------------------------------------|-------------------------------------------|
| `app/Http/Controllers/UserController.php` | Explicit controller with all CRUD methods |
| `app/Forms/UserForm.php`                  | Form class with schema-detected fields    |
| `app/Tables/UserTable.php`                | Table class with schema-detected columns  |
| `app/Http/Requests/UserRequest.php`       | FormRequest backed by `UserForm::rules()` |
| `resources/js/pages/Users/Index.vue`      | Inertia index page                        |
| `resources/js/pages/Users/Show.vue`       | Inertia detail page                       |
| `resources/js/pages/Users/Form.vue`       | Inertia create/edit page                  |

The three Vue pages are only generated for the per-resource page style. With the shared style the published
`Crud/*.vue` pages are reused instead - see [Vue Pages](vue-pages.md). The `pages` segment follows your project's
convention and becomes `Pages` on projects that use `resources/js/Pages/`.

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

All methods are written out explicitly - customise the controller directly.

## Available options

| Option              | Description                                                 |
|---------------------|-------------------------------------------------------------|
| `--model=FQCN`, `-m` | Model FQCN - skips the interactive prompt                   |
| `--shared`          | Use the shared `Crud/*.vue` pages                           |
| `--pages`           | Generate per-resource Vue pages                             |
| `--destroy`         | Include a delete button on the edit form (skips the prompt) |
| `--force`, `-f`     | Overwrite existing files                                    |

`--shared` wins over `--pages` when both are passed, and both win over the `pages` config value.

## Delete button

`make:crud` prompts whether to add a delete button to the edit form. When confirmed, the generated `edit()` method
includes a `destroyRoute` prop that `Form.vue` uses to render the button.

Pass `--destroy` to skip the prompt and always include it:

```bash
php artisan make:crud Product --destroy
```

The generated `edit()` method will contain:

```php
return Inertia::render('Products/Form', [
    'form' => $form,
    'title' => 'Edit Product',
    'destroyRoute' => route('products.destroy', $product),
]);
```
