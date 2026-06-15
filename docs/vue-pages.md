# Vue Pages

## Per-resource pages (default)

`make:crud` generates `Index.vue` and `Form.vue` inside a folder named after the resource:

```
resources/js/pages/Users/Index.vue
resources/js/pages/Users/Form.vue
```

```vue
<!-- Users/Index.vue -->
<script setup lang="ts">
    import TableBuilder from '@table-builder/components/TableBuilder.vue'
    import type {TableData} from '@table-builder/types/table-builder'

    defineProps<{ table: TableData; title: string; createRoute: string }>()
</script>

<template>
    <TableBuilder :table="table"/>
</template>
```

```vue
<!-- Users/Form.vue -->
<script setup lang="ts">
    import Form from '@form-builder/components/Form.vue'
    import type {FormSchema} from '@form-builder/types/form-builder'

    defineProps<{ form: FormSchema; title: string }>()
</script>

<template>
    <Form :schema="form"/>
</template>
```

## Shared pages

Instead of generating per-resource pages, publish a single pair of `Crud/Index.vue` and `Crud/Form.vue` pages and reuse
them across all CRUD resources.

Publish the shared pages once:

```bash
php artisan vendor:publish --tag=crud-builder-pages
```

This copies:

```
resources/js/pages/Crud/Index.vue
resources/js/pages/Crud/Form.vue
```

Then set the config so `make:crud` uses them by default:

```php
// config/vue-crud-builder.php
'pages' => 'shared',
```

Or pass `--shared` per command:

```bash
php artisan make:crud Product --shared
```

## Choosing the page style

The `pages` config key controls the default behaviour:

| Value          | Behaviour                             |
|----------------|---------------------------------------|
| `per-resource` | Always generate per-resource pages    |
| `shared`       | Always use the published shared pages |
| `ask`          | Prompt during `make:crud` (default)   |

Pass `--pages` or `--shared` to override the config for a single run.

## Delete button on the edit form

Both the shared `Crud/Form.vue` and generated per-resource `Form.vue` pages support an optional delete button via the
`destroyRoute` prop. Pass it from the controller's `edit()` method:

```php
return Inertia::render('Products/Form', [
    'form' => $form,
    'title' => 'Edit Product',
    'destroyRoute' => route('products.destroy', $product),
]);
```

The button is only rendered when `destroyRoute` is present. Two additional optional props control the copy:

| Prop             | Default                                        |
|------------------|------------------------------------------------|
| `destroyLabel`   | `Remove`                                       |
| `destroyConfirm` | `Are you sure you want to delete this record?` |

`make:crud` prompts whether to include this automatically, or pass `--destroy` to skip the prompt.
