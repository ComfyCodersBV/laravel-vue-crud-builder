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
| `app/Http/Controllers/UserController.php` | Thin controller, extends `CrudController` |
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

The controller is intentionally thin — it finds the right classes by convention:

```php
class UserController extends CrudController
{
    //
}
```

`CrudController` handles `index`, `create`, `store`, `edit`, `update`, and `destroy` automatically.

## Convention-based resolution

For `UserController`, these classes are resolved automatically at runtime:

| Convention | Class                           |
|------------|---------------------------------|
| Form       | `App\Forms\UserForm`            |
| Table      | `App\Tables\UserTable`          |
| Request    | `App\Http\Requests\UserRequest` |
| Model      | `App\Models\User`               |

Override any of them with explicit properties on the controller:

```php
class UserController extends CrudController
{
    protected string $model = User::class;
    protected string $form  = UserForm::class;
    protected string $table = UserTable::class;
}
```

## FormRequest properties

By default, `UserRequest` is used for both store and update. Override per-operation:

```php
class UserController extends CrudController
{
    protected string $request        = UserRequest::class;       // both store + update
    protected string $storeRequest   = CreateUserRequest::class;
    protected string $updateRequest  = UpdateUserRequest::class;
    protected string $destroyRequest = DeleteUserRequest::class;
}
```

Specific properties take precedence over `$request`.

## Available options

| Option         | Description                                   |
|----------------|-----------------------------------------------|
| `--model=FQCN` | Model FQCN — skips the interactive prompt     |
| `--shared`     | Use shared `Crud/Index.vue` + `Crud/Form.vue` |
| `--pages`      | Generate per-resource Vue pages               |
| `--force`      | Overwrite existing files                      |
