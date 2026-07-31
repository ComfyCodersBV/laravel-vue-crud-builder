# Customising Stubs

Publish stubs to override the generated output:

```bash
php artisan vendor:publish --tag=crud-builder-stubs
```

This copies the stubs to your project's `stubs/` directory:

```txt
stubs/
  crud-controller.stub  # The controller class
  crud-form.stub        # The Form class
  crud-table.stub       # The Table class
  index-page.stub       # Index.vue Inertia page
  form-page.stub        # Form.vue Inertia page
  show-page.stub        # Show.vue Inertia page
```

Each stub is looked up at `stubs/<name>.stub` in your project root first and falls back to the package default, so you
can publish all of them and delete the ones you do not want to override.

## Available placeholders

### `crud-controller.stub`

| Placeholder           | Replaced with                                                                    |
|-----------------------|----------------------------------------------------------------------------------|
| `{{ namespace }}`     | Controller namespace (e.g. `App\Http\Controllers`)                               |
| `{{ class }}`         | Controller class name (e.g. `UserController`)                                    |
| `{{ imports }}`       | Sorted `use` statements for the model, form, table and request classes           |
| `{{ modelClass }}`    | Model class basename (e.g. `User`)                                               |
| `{{ modelParam }}`    | Route-bound variable name (e.g. `user`)                                          |
| `{{ formClass }}`     | Form class basename (e.g. `UserForm`)                                            |
| `{{ tableClass }}`    | Table class basename (e.g. `UserTable`)                                          |
| `{{ requestClass }}`  | Request class basename (e.g. `UserRequest`)                                      |
| `{{ routePrefix }}`   | Kebab-case plural route name prefix (e.g. `users`)                               |
| `{{ indexPage }}`     | Inertia component for index (`Users/Index` or `Crud/Index`)                      |
| `{{ showPage }}`      | Inertia component for show (`Users/Show` or `Crud/Show`)                         |
| `{{ formPage }}`      | Inertia component for create/edit (`Users/Form` or `Crud/Form`)                  |
| `{{ title }}`         | Headline plural (e.g. `Users`)                                                   |
| `{{ singularTitle }}` | Headline singular (e.g. `User`)                                                  |
| `{{ destroyRoute }}`  | The `'destroyRoute' => route(…)` line for `edit()`, or empty without `--destroy`  |

### `crud-form.stub`

| Placeholder       | Replaced with                                         |
|-------------------|-------------------------------------------------------|
| `{{ namespace }}` | Form namespace (e.g. `App\Forms`)                     |
| `{{ class }}`     | Form class name (e.g. `UserForm`)                     |
| `{{ imports }}`   | Sorted `use` statements for all field classes         |
| `{{ fields }}`    | Pre-populated field definitions (empty when no model) |

### `crud-table.stub`

| Placeholder       | Replaced with                                                        |
|-------------------|----------------------------------------------------------------------|
| `{{ namespace }}` | Table namespace (e.g. `App\Tables`)                                  |
| `{{ class }}`     | Table class name (e.g. `UserTable`)                                  |
| `{{ imports }}`   | Sorted `use` statements including the model                          |
| `{{ forType }}`    | `Builder` with a model, `array` without one                          |
| `{{ forReturn }}`  | `User::query()` or `[]` when no model                                |
| `{{ columns }}`    | Pre-populated `->withGlobalSearch(…)` and `->column(…)` calls        |
| `{{ pagination }}` | `->paginate(25)` with a model, empty without one                     |

`{{ forType }}` and `{{ forReturn }}` always agree: with a model the generated `for()` is typed `Builder` and returns a
real query, without one it is typed `array` and returns `[]`. `AbstractTable::for()` is untyped and documents
`Builder|Relation|Model|Collection|array|string|LengthAwarePaginator`, so both are valid overrides - keep them in sync
if you override this stub, or the no-model path will not type-check.

`{{ pagination }}` exists for the same reason. `TableBuilder::for()` only returns a paginatable `QueryBuilder` when the
resource is a query builder, relation or model; for an `array` resource you get a plain `TableBuilder`, whose
`paginate()` always throws a `PaginationException`. So the no-model scaffold omits the call and leaves a one-line
comment telling you to add `->paginate(25)` once `for()` returns a query builder. If you hardcode `->paginate(25)` in
your own stub, a Table generated without a model will throw the first time it renders.

### `index-page.stub` / `form-page.stub` / `show-page.stub`

The Vue page stubs only get these two replacements:

| Placeholder         | Replaced with                     |
|---------------------|-----------------------------------|
| `{{ resource }}`    | Studly-case plural (e.g. `Users`) |
| `{{ routePrefix }}` | Kebab-case plural (e.g. `users`)  |

Everything else stays untouched, so Vue interpolations survive as-is. The shipped stubs contain `{{ title }}`, which is
the Inertia prop rendered by Vue - not a generator placeholder. The two placeholders above are available for your own
stubs, but the default stubs read everything they need from props and do not use them.

The Vue page stubs are only used for the per-resource page style. With `pages => 'shared'` (or `--shared`) no Vue files
are generated at all - the published `Crud/*.vue` pages are used instead.
