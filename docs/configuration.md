# Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=crud-builder-config
```

## `config/vue-crud-builder.php`

```php
return [
    // 'per-resource' — generate Index.vue + Form.vue per resource
    // 'shared'       — use published Crud/Index.vue + Crud/Form.vue
    // 'ask'          — prompt during make:crud (default)
    'pages' => env('CRUD_BUILDER_PAGES', 'ask'),

    // Namespaces used for convention-based class resolution and code generation.
    // UserController resolves App\Forms\UserForm, App\Tables\UserTable, etc. automatically.
    'namespaces' => [
        'models'   => 'App\\Models',
        'forms'    => 'App\\Forms',
        'tables'   => 'App\\Tables',
        'requests' => 'App\\Http\\Requests',
    ],
];
```

Set the pages style via environment variable:

```env
CRUD_BUILDER_PAGES=per-resource
```
