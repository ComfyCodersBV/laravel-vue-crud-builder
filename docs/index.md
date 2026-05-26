# Laravel Vue CRUD Builder

Full CRUD scaffolding for Laravel + Vue 3 + Inertia.js.

Point it at a model and it auto-generates form fields and table columns from your database schema. Or wire in explicit
form and table classes for full control. Either way, one command gives you a working index, create, edit, and delete
flow.

## What it generates

```
app/Http/Controllers/UserController.php
resources/js/pages/Users/Index.vue
resources/js/pages/Users/Form.vue
```

The controller extends `CrudController`, which handles all six CRUD actions automatically. The Vue pages use
the [Form Builder](https://github.com/ComfyCodersBV/laravel-vue-form-builder)
and [Table Builder](https://github.com/ComfyCodersBV/laravel-vue-table-builder) components.

## Dependencies

- Laravel 11+
- Inertia.js v3
- Vue 3
- Tailwind CSS v4
- [`tranquil-tools/laravel-vue-form-builder`](https://github.com/ComfyCodersBV/laravel-vue-form-builder)
- [`tranquil-tools/laravel-vue-table-builder`](https://github.com/ComfyCodersBV/laravel-vue-table-builder)

Both form and table builder are pulled in automatically as Composer dependencies — no need to require them separately.
