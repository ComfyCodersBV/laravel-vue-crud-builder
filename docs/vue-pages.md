# Vue Pages

## Per-resource pages (default)

`make:crud` generates `Index.vue`, `Show.vue` and `Form.vue` inside a folder named after the resource:

```txt
resources/js/pages/Users/Index.vue
resources/js/pages/Users/Show.vue
resources/js/pages/Users/Form.vue
```

The directory follows your project's convention - `resources/js/pages/` or `resources/js/Pages/`, detected the same way
as for the shared pages (see [Installation](installation.md#pages-directory-detection)). Existing files are never
overwritten unless you pass `--force`.

```vue
<!-- Users/Index.vue -->
<script setup lang="ts">
import TableBuilder from '@table-builder/components/TableBuilder.vue'
import type { TableData } from '@table-builder/types/table-builder'
import { useTranslations } from '@table-builder/composables/useTranslations'
import { Link } from '@inertiajs/vue3'

defineProps<{
    table: TableData
    title: string
    createRoute: string
}>()

const { t } = useTranslations('vue_crud_builder_translations')
</script>
```

```vue
<!-- Users/Form.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import Form from '@form-builder/components/Form.vue'
import type { FormSchema } from '@form-builder/types/form-builder'
import { useTranslations } from '@table-builder/composables/useTranslations'

const props = defineProps<{
    form: FormSchema
    title: string
    destroyRoute?: string
    destroyLabel?: string
    destroyConfirm?: string
}>()

const { t } = useTranslations('vue_crud_builder_translations')
</script>
```

```vue
<!-- Users/Show.vue -->
<script setup lang="ts">
import { useTranslations } from '@table-builder/composables/useTranslations'
import { Link } from '@inertiajs/vue3'

defineProps<{
    record: Record<string, unknown>
    title: string
    editRoute: string
    indexRoute: string
}>()

const { t } = useTranslations('vue_crud_builder_translations')
</script>
```

The `@table-builder` and `@form-builder` imports rely on the Vite aliases the installer adds - see
[Installation](installation.md#vite-aliases). Labels such as the create/edit/back buttons come from the package
translations shared with Inertia as `vue_crud_builder_translations`.

## Shared pages

Instead of generating per-resource pages, publish a single set of `Crud/*.vue` pages and reuse them across all CRUD
resources.

Publish the shared pages once:

```bash
php artisan vendor:publish --tag=crud-builder-pages
```

This copies:

```txt
resources/js/pages/Crud/Index.vue
resources/js/pages/Crud/Show.vue
resources/js/pages/Crud/Form.vue
```

The destination follows your project's convention: `resources/js/pages/Crud/` or `resources/js/Pages/Crud/`, whichever
directory exists (see [Installation](installation.md#pages-directory-detection)). Run `npm run build` afterwards.

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

The `ask` prompt itself defaults to `per-resource`.

Pass `--pages` or `--shared` to override the config for a single run.

## Delete button on the edit form

Both the shared `Crud/Form.vue` and the generated per-resource `Form.vue` render an optional delete button when they
receive a `destroyRoute` prop. The controller's `edit()` method passes it:

```php
return Inertia::render('Products/Form', [
    'form' => $form,
    'title' => 'Edit Product',
    'destroyRoute' => route('products.destroy', $product),
]);
```

The button is only rendered when `destroyRoute` is present, and it asks for confirmation before issuing
`router.delete()`. Two additional optional props override the copy, which otherwise comes from the package
translations:

| Prop             | Falls back to translation key | English default                                |
|------------------|-------------------------------|------------------------------------------------|
| `destroyLabel`   | `delete`                      | `Delete`                                       |
| `destroyConfirm` | `delete_confirm`              | `Are you sure you want to delete this record?` |

`make:crud` prompts whether to include this automatically, or pass `--destroy` to skip the prompt.

`destroyRoute` is optional on both pages, so a Form page generated without `--destroy` renders and type-checks with no
delete button at all.
