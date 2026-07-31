# Laravel Vue CRUD Builder

Full CRUD scaffolding for Laravel + Vue 3 + Inertia.js.

Point it at a model and it auto-generates form fields and table columns from your database schema. Or wire in explicit
form and table classes for full control. Either way, one command gives you a working index, create, edit, and delete
flow.

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

The controller extends Laravel's `Controller` and includes all seven CRUD actions explicitly. The Vue pages use
the [FormBuilder](https://github.com/ComfyCodersBV/laravel-vue-form-builder)
and [TableBuilder](https://github.com/ComfyCodersBV/laravel-vue-table-builder) components.

## Dependencies

- PHP 8.4+
- Laravel 11+
- Inertia.js v2 or v3 (`inertiajs/inertia-laravel: ^2.0|^3.0`)
- Vue 3
- Tailwind CSS v4
- `reka-ui` and `lucide-vue-next` (npm)
- [`tranquil-tools/laravel-vue-form-builder`](https://github.com/ComfyCodersBV/laravel-vue-form-builder)*
- [`tranquil-tools/laravel-vue-table-builder`](https://github.com/ComfyCodersBV/laravel-vue-table-builder)*

*) Both form and table builder are pulled in automatically as Composer dependencies, no need to require them separately.
