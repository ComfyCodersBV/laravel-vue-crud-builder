# Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=crud-builder-config
```

## `config/vue-crud-builder.php`

```php
return [
    // 'per-resource' - generate Index.vue + Show.vue + Form.vue per resource
    // 'shared'       - use the published Crud/ pages
    // 'ask'          - prompt during make:crud (default)
    'pages' => env('CRUD_BUILDER_PAGES', 'ask'),

    // Namespaces used during code generation.
    'namespaces' => [
        'models' => 'App\\Models',
        'forms' => 'App\\Forms',
        'tables' => 'App\\Tables',
        'requests' => 'App\\Http\\Requests',
    ],
];
```

Set the pages style via environment variable:

```env
CRUD_BUILDER_PAGES=per-resource
```

`crud-builder:install` publishes this file for you; the command above is only needed when you skipped the installer or
deleted the file.

## Namespaces

The `namespaces` values are used when generating code and when resolving related classes by convention:

| Key        | Used for                                                                                    |
|------------|---------------------------------------------------------------------------------------------|
| `models`   | The model fallback when the prompt is left empty, and the model behind a relation `Select`   |
| `forms`    | Namespace and path of the generated `…Form` class                                            |
| `tables`   | Namespace and path of the generated `…Table` class                                           |
| `requests` | The `…Request` namespace imported by the generated controller                                 |

The path of a generated Form or Table class is derived from its namespace relative to the application namespace, so
`App\Domain\Forms` writes to `app/Domain/Forms/`.

The FormRequest itself is created by the FormBuilder's `make:form-request` command, which decides its own location -
change `requests` only when that command writes somewhere else too, otherwise the generated controller imports a class
that does not exist.

The interactive model prompt is always pre-filled with `<AppNamespace>Models\<Resource>` and ignores `namespaces.models`;
pass `--model` when your models live elsewhere.
