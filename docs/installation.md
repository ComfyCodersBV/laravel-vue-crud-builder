# Installation

## Composer

```bash
composer require tranquil-tools/laravel-vue-crud-builder
```

## npm packages

The Vue components rendered by the Form- and TableBuilder need two runtime packages:

```bash
npm install reka-ui @lucide/vue
```

The builder components import their icons from `@lucide/vue`, the maintained successor of `lucide-vue-next` that
Laravel's current starter kits ship. The installer warns when it is missing from `package.json`. A
`'lucide-vue-next': '@lucide/vue'` alias added by an earlier installer is harmless and may stay or go.

## Installer

```bash
php artisan crud-builder:install
```

The installer:

1. Publishes `config/vue-crud-builder.php`
2. Adds the `@form-builder` and `@table-builder` Vite aliases
3. Adds the matching `paths` to `tsconfig.json` so `vue-tsc` resolves the builder types
4. Adds the `@source` directives for the three builder packages to `resources/css/app.css`
5. Checks your Inertia entry file for a layout setup and tells you when there is none

Every step only adds what is missing, so running the installer twice changes nothing. A step is skipped with a warning
when the target file cannot be found or cannot be parsed - the installer never rewrites a file it does not understand.

## Vite aliases

The installer looks for `vite.config.ts`, `vite.config.js`, `vite.config.mjs` or `vite.config.mts` in the project root
(first match wins) and makes sure both aliases exist. Vite configs are ES modules, where `__dirname` does not exist, so
the paths are resolved from `import.meta.url`:

```ts
import { fileURLToPath } from 'node:url';

export default defineConfig({
    resolve: {
        alias: {
            '@form-builder': fileURLToPath(
                new URL(
                    './vendor/tranquil-tools/laravel-vue-form-builder/resources/js',
                    import.meta.url,
                ),
            ),
            '@table-builder': fileURLToPath(
                new URL(
                    './vendor/tranquil-tools/laravel-vue-table-builder/resources/js',
                    import.meta.url,
                ),
            ),
        },
    },
});
```

These are the only two aliases needed. The CRUD Builder's own Vue pages are published into your `resources/js`, so
there is no `@crud-builder` alias.

If the config already has a `resolve.alias` block, the missing aliases are inserted into it. If there is no `resolve`
block at all, a complete one is injected into `defineConfig({` (this works for `vite` and `vite-plus` alike). Anything
else (for example a `resolve` block without an `alias` key, or a config that does not use `defineConfig`) is left
untouched with a warning so you can add the lines by hand.

`fileURLToPath` is added to an existing `node:url` import, or imported after your last import. An alias that is already
there is never changed. Projects installed with 1.2 or older have `path.resolve(__dirname, …)` aliases; the installer
warns about those, and you switch them to the form above.

## TypeScript paths

The generated and published pages import types and composables from both builders. When the project has a
`tsconfig.json`, the installer adds these entries to `compilerOptions.paths`, creating the `paths` block when there is
none:

```json
"@form-builder/types/*": ["./vendor/tranquil-tools/laravel-vue-form-builder/resources/js/types/*"],
"@form-builder/composables/*": ["./vendor/tranquil-tools/laravel-vue-form-builder/resources/js/composables/*"],
"@table-builder/types/*": ["./vendor/tranquil-tools/laravel-vue-table-builder/resources/js/types/*"],
"@table-builder/composables/*": ["./vendor/tranquil-tools/laravel-vue-table-builder/resources/js/composables/*"]
```

Component imports such as `@table-builder/components/TableBuilder.vue` are deliberately left to your `*.vue` module
declaration, so `vue-tsc` checks your code and not the package sources. An entry that is already there, or a wildcard
such as `"@form-builder/*"`, is left as it is. Comments in `tsconfig.json` are preserved.

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

## Inertia layout

The generated and published CRUD pages render bare content, so they rely on the layout your app assigns. The installer
never rewrites your Inertia entry file: forcing a default layout would also wrap pages that deliberately have none, such
as a welcome or login page.

It looks for the first of `resources/js/inertia.ts`, `inertia.js`, `app.ts` or `app.js` and only reports what it finds.
When there is a layout setup (a `layout` option on `createInertiaApp`, or a `page.default.layout` assignment in
`resolve`), CRUD pages get the layout it picks for them. When there is none, it warns, and you add one yourself. On
Inertia 3, as in Laravel's starter kits:

```ts
createInertiaApp({
    layout: (name) => (name === 'Welcome' ? null : AppLayout),
    // …
});
```

## Pages directory detection

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
