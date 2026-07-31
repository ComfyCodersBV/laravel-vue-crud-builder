# Installation

## Composer

```bash
composer require tranquil-tools/laravel-vue-crud-builder
```

## npm packages

The Vue components rendered by the Form- and TableBuilder need two runtime packages:

```bash
npm install reka-ui lucide-vue-next
```

## Installer

```bash
php artisan crud-builder:install
```

The installer:

1. Publishes `config/vue-crud-builder.php`
2. Adds the `@form-builder` and `@table-builder` Vite aliases (and `import path from 'path'` when missing)
3. Adds the `@source` directives for the three builder packages to `resources/css/app.css`
4. Wires up the persistent Inertia layout in your Inertia entry file

Every step is skipped with a warning when the target file cannot be found or cannot be parsed - the installer never
rewrites a file it does not understand, so it is safe to re-run.

## Vite aliases

The installer looks for `vite.config.ts`, `vite.config.js`, `vite.config.mjs` or `vite.config.mts` in the project root
(first match wins) and makes sure both aliases exist:

```ts
'@form-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js'),
'@table-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js'),
```

These are the only two aliases needed. The CRUD Builder's own Vue pages are published into your `resources/js`, so
there is no `@crud-builder` alias.

If the config already has a `resolve.alias` block, the missing aliases are inserted into it. If there is no `resolve`
block at all, a complete one is injected into `defineConfig({`. Anything else (for example a `resolve` block without an
`alias` key, or a config that does not use `defineConfig`) is left untouched with a warning so you can add the two lines
by hand.

An `import path from 'path'` statement is added after the last top-level import when the file has no `path` import yet;
an existing `from 'path'` or `from 'node:path'` import is reused.

### Vite config (after install)

```ts
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@form-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js'),
            '@table-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js'),
        },
    },
});
```

Only the two `@…-builder` lines and the `path` import come from the installer. Aliases like `'@': '/resources/js'` are
your own application config and are left exactly as they are.

## Tailwind sources

Tailwind only scans files it is told about, so the installer adds one `@source` line per builder package to
`resources/css/app.css`. Each line is inserted after the last existing `@source` line, or appended to the file when
there is none.

### app.css (after install)

```css
@source '../../vendor/tranquil-tools/laravel-vue-crud-builder/resources/js/**/*.vue';
@source '../../vendor/tranquil-tools/laravel-vue-form-builder/resources/js/**/*.vue';
@source '../../vendor/tranquil-tools/laravel-vue-table-builder/resources/js/**/*.vue';
```

## Inertia layout setup

The generated and published CRUD pages render bare content, so they expect a persistent layout. The installer
configures that for you.

It looks for the first of these Inertia entry files:

```txt
resources/js/inertia.ts
resources/js/inertia.js
resources/js/app.ts
resources/js/app.js
```

Then it rewrites `createInertiaApp`'s `resolve` into its async form and assigns the layout:

```ts
import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from './layouts/AppLayout.vue';

createInertiaApp({
    resolve: async (name) => {
        const page = await resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'));
        page.default.layout ??= AppLayout;
        return page;
    },
    // …
});
```

When there is no `resolve` yet, a complete one is injected into `createInertiaApp({` using `import.meta.glob` over the
detected pages directory. The `import AppLayout from …` statement is added right after the `@inertiajs/vue3` import.

### Layout detection

The layout component is auto-detected, first match wins:

| File                                  | Import path                  |
|---------------------------------------|------------------------------|
| `resources/js/Layout.vue`             | `./Layout.vue`               |
| `resources/js/Layouts/AppLayout.vue`  | `./Layouts/AppLayout.vue`    |
| `resources/js/layouts/AppLayout.vue`  | `./layouts/AppLayout.vue`    |
| `resources/js/Layouts/Layout.vue`     | `./Layouts/Layout.vue`       |

When none of these exist, the `resolve` is still converted but the `page.default.layout ??= AppLayout` line and the
import are left out - the installer warns and you add your own layout line. When both the layout line and the import
are already there, the file is left alone.

### Pages directory detection

Laravel's Inertia starter kits use `resources/js/pages`, older projects use `resources/js/Pages`. The installer (and
the `crud-builder-pages` publish tag) detect which one you use:

1. `resources/js/pages` exists → `pages`
2. `resources/js/Pages` exists → `Pages`
3. Neither exists → `resources/views/app.blade.php` or `root.blade.php` is checked for `$page['component']`, which
   indicates the newer convention → `pages`
4. Otherwise → `Pages`

## Publish tags

| Tag                         | Publishes                                   | Destination                            |
|-----------------------------|---------------------------------------------|----------------------------------------|
| `crud-builder-config`       | `config/vue-crud-builder.php`               | `config/vue-crud-builder.php`          |
| `crud-builder-pages`        | Shared `Crud/Index.vue`, `Form.vue`, `Show.vue` | `resources/js/{pages,Pages}/Crud/` |
| `crud-builder-stubs`        | The generator stubs                         | `stubs/`                               |
| `crud-builder-translations` | The `crud.php` translation files            | `lang/vendor/vue-crud-builder/`        |

```bash
php artisan vendor:publish --tag=crud-builder-config
php artisan vendor:publish --tag=crud-builder-pages
php artisan vendor:publish --tag=crud-builder-stubs
php artisan vendor:publish --tag=crud-builder-translations
```

The installer runs the `crud-builder-config` tag for you; the other three are opt-in.

Translations are shared with Inertia automatically as `vue_crud_builder_translations`, so publishing them is only
needed when you want to change the wording or add a locale.

After publishing pages or stubs, run `npm run build` (or `npm run dev`) to pick up the new Vue files.
