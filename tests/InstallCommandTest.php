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
        ->toContain("import laravel from 'laravel-vite-plugin';\nimport { fileURLToPath } from 'node:url';")
        ->toContain(<<<'TS'
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
            '@': '/resources/js',
TS)
        ->not->toContain('__dirname')
        ->not->toContain('path.resolve')
        ->not->toContain("from 'path'");
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
        ->toContain("export default defineConfig({\n    resolve: {\n        alias: {\n            '@form-builder': fileURLToPath(")
        ->toContain("'./vendor/tranquil-tools/laravel-vue-table-builder/resources/js',")
        ->toContain("import { fileURLToPath } from 'node:url';");
});

it('adds fileURLToPath to an existing node:url import instead of importing it twice', function () {
    $path = $this->sandbox.'/vite.config.ts';

    file_put_contents($path, <<<'TS'
import { URL } from 'node:url';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [],
});
TS);

    $this->artisan('crud-builder:install')->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)->toStartWith("import { fileURLToPath, URL } from 'node:url';\nimport { defineConfig } from 'vite';")
        ->and(substr_count($content, 'node:url'))->toBe(1);
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

    $this->artisan('crud-builder:install')
        ->expectsOutputToContain('__dirname')
        ->assertSuccessful();

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

it('adds no lucide alias now that the builders import @lucide/vue', function () {
    $path = $this->sandbox.'/vite.config.ts';

    file_put_contents($this->sandbox.'/package.json', json_encode(['dependencies' => ['@lucide/vue' => '^1.17.0']]));
    file_put_contents($path, "import { defineConfig } from 'vite';\n\nexport default defineConfig({\n    plugins: [],\n});\n");

    $this->artisan('crud-builder:install')
        ->doesntExpectOutputToContain('@lucide/vue, which is not in package.json')
        ->assertSuccessful();

    expect(file_get_contents($path))->not->toContain('lucide');
});

it('keeps the lucide alias of an earlier install untouched', function () {
    $path = $this->sandbox.'/vite.config.ts';
    $config = "import { defineConfig } from 'vite';\n\nexport default defineConfig({\n    resolve: {\n        alias: {\n            'lucide-vue-next': '@lucide/vue',\n        },\n    },\n    plugins: [],\n});\n";

    file_put_contents($this->sandbox.'/package.json', json_encode(['dependencies' => ['@lucide/vue' => '^1.17.0']]));
    file_put_contents($path, $config);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))->toContain("'lucide-vue-next': '@lucide/vue',");
});

it('asks for @lucide/vue when the app only has lucide-vue-next', function () {
    file_put_contents($this->sandbox.'/package.json', json_encode(['devDependencies' => ['lucide-vue-next' => '^1.0']]));
    file_put_contents($this->sandbox.'/vite.config.ts', "import { defineConfig } from 'vite';\n\nexport default defineConfig({\n    plugins: [],\n});\n");

    $this->artisan('crud-builder:install')
        ->expectsOutputToContain('npm install @lucide/vue')
        ->assertSuccessful();
});

it('adds the builder paths to an existing tsconfig paths block with comments', function () {
    $path = $this->sandbox.'/tsconfig.json';

    file_put_contents($path, <<<'JSON'
{
    "compilerOptions": {
        // "baseUrl": "./",                     /* Specify the base directory. */
        "paths": {
            /* Re-map imports. */ "@/*": [
                "./resources/js/*"
            ]
        },
        "strict": true
    },
    "include": ["resources/js/**/*.ts"]
}
JSON);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe(<<<'JSON'
{
    "compilerOptions": {
        // "baseUrl": "./",                     /* Specify the base directory. */
        "paths": {
            /* Re-map imports. */ "@/*": [
                "./resources/js/*"
            ],
            "@form-builder/types/*": [
                "./vendor/tranquil-tools/laravel-vue-form-builder/resources/js/types/*"
            ],
            "@form-builder/composables/*": [
                "./vendor/tranquil-tools/laravel-vue-form-builder/resources/js/composables/*"
            ],
            "@table-builder/types/*": [
                "./vendor/tranquil-tools/laravel-vue-table-builder/resources/js/types/*"
            ],
            "@table-builder/composables/*": [
                "./vendor/tranquil-tools/laravel-vue-table-builder/resources/js/composables/*"
            ]
        },
        "strict": true
    },
    "include": ["resources/js/**/*.ts"]
}
JSON);
});

it('creates a paths block when the only paths entry is commented out', function () {
    $path = $this->sandbox.'/tsconfig.json';

    file_put_contents($path, <<<'JSON'
{
  "compilerOptions": {
    // "paths": {},
    "strict": true
  }
}
JSON);

    $this->artisan('crud-builder:install')->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->toContain("  \"compilerOptions\": {\n    \"paths\": {\n      \"@form-builder/types/*\": [\n        \"./vendor/tranquil-tools/laravel-vue-form-builder/resources/js/types/*\"\n      ],")
        ->toContain("    // \"paths\": {},\n    \"strict\": true")
        ->and(json_decode(preg_replace('#^\s*//.*$#m', '', $content), true))->toBeArray();
});

it('fills an empty paths object', function () {
    $path = $this->sandbox.'/tsconfig.json';

    file_put_contents($path, "{\n    \"compilerOptions\": {\n        \"paths\": {}\n    }\n}\n");

    $this->artisan('crud-builder:install')->assertSuccessful();

    $config = json_decode(file_get_contents($path), true);

    expect(array_keys($config['compilerOptions']['paths']))->toBe([
        '@form-builder/types/*',
        '@form-builder/composables/*',
        '@table-builder/types/*',
        '@table-builder/composables/*',
    ]);
});

it('treats a wildcard builder path as covering the narrower ones', function () {
    $path = $this->sandbox.'/tsconfig.json';

    file_put_contents($path, <<<'JSON'
{
    "compilerOptions": {
        "paths": {
            "@form-builder/*": ["./vendor/tranquil-tools/laravel-vue-form-builder/resources/js/*"]
        }
    }
}
JSON);

    $this->artisan('crud-builder:install')->assertSuccessful();

    $paths = array_keys(json_decode(file_get_contents($path), true)['compilerOptions']['paths']);

    expect($paths)->toBe([
        '@form-builder/*',
        '@table-builder/types/*',
        '@table-builder/composables/*',
    ]);
});

it('never rewrites the inertia entry file', function (string $entry) {
    $path = $this->sandbox.'/resources/js/app.ts';

    File::ensureDirectoryExists(dirname($path));
    file_put_contents($path, $entry);

    $this->artisan('crud-builder:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe($entry);
})->with([
    'layout callback' => [<<<'TS'
import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

void createInertiaApp({
    layout: (name) => (name === 'Welcome' ? null : AppLayout),
});
TS],
    'no layout at all' => [<<<'TS'
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
});
TS],
]);

it('warns when no inertia layout is configured', function () {
    File::ensureDirectoryExists($this->sandbox.'/resources/js');
    file_put_contents($this->sandbox.'/resources/js/app.ts', "createInertiaApp({});\n");

    $this->artisan('crud-builder:install')
        ->expectsOutputToContain('No Inertia layout is configured in app.ts')
        ->assertSuccessful();
});

it('changes nothing when run a second time on a starter kit', function () {
    File::ensureDirectoryExists($this->sandbox.'/resources/js');

    $files = [
        'package.json' => json_encode(['type' => 'module', 'dependencies' => ['@lucide/vue' => '^1.17.0']]),
        'vite.config.ts' => "import vue from '@vitejs/plugin-vue';\nimport { defineConfig, lazyPlugins } from 'vite-plus';\n\nexport default defineConfig({\n    plugins: lazyPlugins(() => [vue()]),\n});\n",
        'tsconfig.json' => "{\n    \"compilerOptions\": {\n        \"paths\": {\n            \"@/*\": [\"./resources/js/*\"]\n        }\n    }\n}\n",
        'resources/css/app.css' => "@import 'tailwindcss';\n\n@source '../../storage/framework/views/*.php';\n",
        'resources/js/app.ts' => "void createInertiaApp({\n    layout: () => null,\n});\n",
    ];

    foreach ($files as $file => $content) {
        file_put_contents($this->sandbox.'/'.$file, $content);
    }

    $this->artisan('crud-builder:install')->assertSuccessful();

    $afterFirstRun = collect($files)->keys()->mapWithKeys(fn (string $file): array => [
        $file => file_get_contents($this->sandbox.'/'.$file),
    ]);

    expect($afterFirstRun['vite.config.ts'])->not->toBe($files['vite.config.ts'])
        ->and($afterFirstRun['tsconfig.json'])->not->toBe($files['tsconfig.json'])
        ->and($afterFirstRun['resources/js/app.ts'])->toBe($files['resources/js/app.ts']);

    $this->artisan('crud-builder:install')->assertSuccessful();

    foreach ($afterFirstRun as $file => $content) {
        expect(file_get_contents($this->sandbox.'/'.$file))->toBe($content);
    }
});
