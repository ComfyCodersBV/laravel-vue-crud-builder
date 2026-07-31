<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->sandbox = sys_get_temp_dir().'/crud-builder-install-'.uniqid();

    File::ensureDirectoryExists($this->sandbox.'/resources/css');

    $this->app->setBasePath($this->sandbox);
});

afterEach(function () {
    File::deleteDirectory($this->sandbox);
});

it('adds the form-builder and table-builder aliases to an existing resolve.alias block', function () {
    $path = $this->sandbox.'/vite.config.ts';

    file_put_contents($path, <<<'TS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel()],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
TS);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))
        ->toContain("import path from 'path';")
        ->toContain("'@form-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js'),")
        ->toContain("'@table-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js'),")
        ->toContain("'@': '/resources/js',");
});

it('injects a resolve block when the vite config has none', function () {
    $path = $this->sandbox.'/vite.config.ts';

    file_put_contents($path, <<<'TS'
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [],
});
TS);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))
        ->toContain('resolve: {')
        ->toContain('alias: {')
        ->toContain("'@form-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js'),")
        ->toContain("'@table-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js'),");
});

it('leaves the vite config untouched when both aliases are already present', function () {
    $path = $this->sandbox.'/vite.config.js';

    $original = <<<'TS'
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    resolve: {
        alias: {
            '@form-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js'),
            '@table-builder': path.resolve(__dirname, 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js'),
        },
    },
});
TS;

    file_put_contents($path, $original);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe($original);
});

it('does not touch the vite config when it cannot be parsed', function () {
    $path = $this->sandbox.'/vite.config.ts';
    $original = "export default {\n    plugins: [],\n};\n";

    file_put_contents($path, $original);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe($original);
});

it('adds a source directive for every builder package to app.css and stays idempotent', function () {
    $path = $this->sandbox.'/resources/css/app.css';

    file_put_contents($path, "@import 'tailwindcss';\n");

    $this->artisan('crud-builder:install')->assertSuccessful();

    $afterFirstRun = file_get_contents($path);

    foreach (['crud', 'form', 'table'] as $package) {
        expect($afterFirstRun)->toContain("@source '../../vendor/tranquil-tools/laravel-vue-{$package}-builder/resources/js/**/*.vue';");
    }

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe($afterFirstRun);
});
