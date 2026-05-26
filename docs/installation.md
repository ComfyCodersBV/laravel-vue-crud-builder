# Installation

## Composer

```bash
composer require tranquil-tools/laravel-vue-crud-builder
```

## Installer

Run the installer to publish the config and patch `vite.config.ts` and `resources/css/app.css`:

```bash
php artisan crud-builder:install
```

The installer:

- Adds `import path from 'path'` to `vite.config.ts` if missing
- Adds `@crud-builder`, `@form-builder`, and `@table-builder` Vite aliases
- Adds `@source` directives for all three packages in `resources/css/app.css`

### Vite config (after install)

```ts
import path from 'path'

export default defineConfig({
    resolve: {
        alias: {
            '@': '/resources/js',
            '@crud-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-crud-builder/resources/js'),
            '@form-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js'),
            '@table-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js'),
        },
    },
})
```

### app.css (after install)

```css
@source '../../vendor/tranquil-tools/laravel-vue-crud-builder/resources/js/**/*.vue';
@source '../../vendor/tranquil-tools/laravel-vue-form-builder/resources/js/**/*.vue';
@source '../../vendor/tranquil-tools/laravel-vue-table-builder/resources/js/**/*.vue';
```
