# Customising Stubs

Publish stubs to override the generated output:

```bash
php artisan vendor:publish --tag=crud-builder-stubs
```

This copies the stubs to your project's `stubs/` directory:

```
stubs/
  crud-controller.stub  # The controller class
  crud-form.stub        # The Form class
  crud-table.stub       # The Table class
  index-page.stub       # Index.vue Inertia page
  form-page.stub        # Form.vue Inertia page
```

Stubs in `stubs/` take precedence over the package defaults.

## Available placeholders

### `crud-controller.stub`

| Placeholder       | Replaced with                                      |
|-------------------|----------------------------------------------------|
| `{{ namespace }}` | Controller namespace (e.g. `App\Http\Controllers`) |
| `{{ class }}`     | Controller class name (e.g. `UserController`)      |

### `crud-form.stub`

| Placeholder       | Replaced with                                         |
|-------------------|-------------------------------------------------------|
| `{{ namespace }}` | Form namespace (e.g. `App\Forms`)                     |
| `{{ class }}`     | Form class name (e.g. `UserForm`)                     |
| `{{ imports }}`   | Sorted `use` statements for all field classes         |
| `{{ fields }}`    | Pre-populated field definitions (empty when no model) |

### `crud-table.stub`

| Placeholder       | Replaced with                               |
|-------------------|---------------------------------------------|
| `{{ namespace }}` | Table namespace (e.g. `App\Tables`)         |
| `{{ class }}`     | Table class name (e.g. `UserTable`)         |
| `{{ imports }}`   | Sorted `use` statements including the model |
| `{{ forReturn }}` | `User::query()` or `[]` when no model       |
| `{{ columns }}`   | Pre-populated column definitions            |

### `index-page.stub` / `form-page.stub`

| Placeholder         | Replaced with                     |
|---------------------|-----------------------------------|
| `{{ resource }}`    | Studly-case plural (e.g. `Users`) |
| `{{ routePrefix }}` | Kebab-case plural (e.g. `users`)  |
