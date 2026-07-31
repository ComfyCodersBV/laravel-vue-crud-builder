<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Commands;

use Illuminate\Console\Command;
use TranquilTools\CrudBuilder\Concerns\DetectsPagesDirectory;

class InstallCommand extends Command
{
    use DetectsPagesDirectory;

    protected $signature = 'crud-builder:install';

    protected $description = 'Install the CRUD Builder package (publishes config, updates vite config, app.css and the Inertia layout setup)';

    protected array $vendorSources = [
        'laravel-vue-crud-builder',
        'laravel-vue-form-builder',
        'laravel-vue-table-builder',
    ];

    protected array $viteAliases = [
        '@form-builder' => 'vendor/tranquil-tools/laravel-vue-form-builder/resources/js',
        '@table-builder' => 'vendor/tranquil-tools/laravel-vue-table-builder/resources/js',
    ];

    public function handle(): int
    {
        $this->publishConfig();
        $this->updateViteConfig();
        $this->updateAppCss();
        $this->setupInertiaLayout();

        $this->newLine();
        $this->components->info('CRUD Builder installed successfully.');

        $pagesDir = $this->detectPagesDir();

        if (! file_exists(resource_path("js/{$pagesDir}/Crud/Index.vue"))) {
            $this->components->info('To use shared Crud/ pages run: `php artisan vendor:publish --tag=crud-builder-pages` and run `npm run build`.');
        }

        return self::SUCCESS;
    }

    protected function setupInertiaLayout(): void
    {
        $candidates = [
            'resources/js/inertia.ts',
            'resources/js/inertia.js',
            'resources/js/app.ts',
            'resources/js/app.js',
        ];

        $file = null;
        foreach ($candidates as $candidate) {
            if (file_exists(base_path($candidate))) {
                $file = base_path($candidate);
                break;
            }
        }

        if (is_null($file)) {
            $this->components->warn('Could not find inertia entry file. Add persistent layout setup manually (see docs).');

            return;
        }

        $content = file_get_contents($file);
        $pagesDir = $this->detectPagesDir();
        $layoutPath = $this->detectLayoutPath();
        $withLayout = ! is_null($layoutPath);

        $hasLayout = str_contains($content, 'page.default.layout');
        $hasImport = str_contains($content, 'import AppLayout from');

        if ($hasLayout && $hasImport) {
            $this->components->info('Inertia layout already configured in '.basename($file).'.');

            return;
        }

        if ($hasLayout) {
            if (is_null($layoutPath)) {
                $this->components->warn('Inertia resolve configured but no layout component found. Add import manually.');

                return;
            }

            file_put_contents($file, $this->ensureAppLayoutImport($content, $layoutPath));
            $this->components->info('Inertia layout import added to '.basename($file).'.');

            return;
        }

        $updated = $this->injectIntoExistingResolve($content, $pagesDir, $withLayout)
            ?? $this->injectResolveIntoCreateInertiaApp($content, $pagesDir, $withLayout);

        if (is_null($updated)) {
            $this->components->warn('Could not auto-inject layout setup into '.basename($file).'. Add manually (see docs).');

            return;
        }

        if ($withLayout) {
            $updated = $this->ensureAppLayoutImport($updated, $layoutPath);
        }

        file_put_contents($file, $updated);

        $msg = $withLayout
            ? 'Inertia layout configured in '.basename($file).'.'
            : 'Inertia resolve configured in '.basename($file).'. No layout component found - add `page.default.layout ??= AppLayout` manually if needed.';

        $this->components->info($msg);
    }

    protected function injectIntoExistingResolve(string $content, string $pagesDir, bool $withLayout): ?string
    {
        $layoutLine = $withLayout ? "\n        page.default.layout ??= AppLayout;" : '';

        $updated = preg_replace(
            '/resolve:\s*\(?name\)?\s*=>\s*(resolvePageComponent\(`[^`]+`,\s*import\.meta\.glob[^)]+\)\s*\))/s',
            "resolve: async (name) => {\n        const page = await $1;{$layoutLine}\n        return page;\n    }",
            $content,
        );

        return ($updated !== null && $updated !== $content) ? $updated : null;
    }

    protected function injectResolveIntoCreateInertiaApp(string $content, string $pagesDir, bool $withLayout): ?string
    {
        $layoutLine = $withLayout ? "\n        page.default.layout ??= AppLayout;" : '';

        $resolveBlock =
            "resolve: async (name) => {\n"
            ."        const pages = import.meta.glob('./{$pagesDir}/**/*.vue');\n"
            ."        const page = (await pages[`./{$pagesDir}/\${name}.vue`]()) as any;{$layoutLine}\n"
            ."        return page;\n"
            .'    },';

        $updated = preg_replace(
            '/createInertiaApp\(\{/',
            "createInertiaApp({\n    {$resolveBlock}",
            $content,
            limit: 1,
        );

        return ($updated !== null && $updated !== $content) ? $updated : null;
    }

    protected function ensureAppLayoutImport(string $content, string $layoutPath): string
    {
        if (str_contains($content, 'import AppLayout from')) {
            return $content;
        }

        $import = "import AppLayout from '{$layoutPath}';";

        $updated = preg_replace(
            "/(import[^;]+from\s*['\"]@inertiajs\/vue3['\"];)/",
            "$1\n{$import}",
            $content,
        );

        return $updated ?? $content."\n{$import}\n";
    }

    protected function detectLayoutPath(): ?string
    {
        $candidates = [
            'resources/js/Layout.vue' => './Layout.vue',
            'resources/js/Layouts/AppLayout.vue' => './Layouts/AppLayout.vue',
            'resources/js/layouts/AppLayout.vue' => './layouts/AppLayout.vue',
            'resources/js/Layouts/Layout.vue' => './Layouts/Layout.vue',
        ];

        foreach ($candidates as $absolute => $relative) {
            if (file_exists(base_path($absolute))) {
                return $relative;
            }
        }

        return null;
    }

    protected function publishConfig(): void
    {
        $this->callSilently('vendor:publish', ['--tag' => 'crud-builder-config']);
        $this->components->info('Config published → config/vue-crud-builder.php');
    }

    protected function updateViteConfig(): void
    {
        $path = $this->locateViteConfig();

        if (is_null($path)) {
            $this->components->warn('No vite config (vite.config.ts|js|mjs|mts) found - skipping Vite alias setup.');

            return;
        }

        $content = file_get_contents($path);
        $missing = [];

        foreach ($this->viteAliases as $alias => $target) {
            if ($this->hasViteAlias($content, $alias)) {
                $this->components->info(basename($path)." already has the {$alias} alias.");

                continue;
            }

            $missing[$alias] = $target;
        }

        if ($missing === []) {
            return;
        }

        $updated = $this->injectViteAliases($content, $missing);

        if (is_null($updated)) {
            $this->components->warn('Could not auto-inject Vite aliases into '.basename($path).'. Add them manually (see docs).');

            return;
        }

        file_put_contents($path, $this->ensurePathImport($updated));

        $this->components->info('Vite aliases added to '.basename($path).': '.implode(', ', array_keys($missing)).'.');
    }

    protected function locateViteConfig(): ?string
    {
        foreach (['vite.config.ts', 'vite.config.js', 'vite.config.mjs', 'vite.config.mts'] as $candidate) {
            if (file_exists(base_path($candidate))) {
                return base_path($candidate);
            }
        }

        return null;
    }

    protected function hasViteAlias(string $content, string $alias): bool
    {
        return str_contains($content, "'{$alias}'")
            || str_contains($content, "\"{$alias}\"");
    }

    protected function injectViteAliases(string $content, array $aliases): ?string
    {
        $updated = $this->injectIntoExistingAliasBlock($content, $aliases);

        if (! is_null($updated)) {
            return $updated;
        }

        if (preg_match('/resolve:\s*\{/', $content) === 1) {
            return null;
        }

        return $this->injectResolveIntoDefineConfig($content, $aliases);
    }

    protected function injectIntoExistingAliasBlock(string $content, array $aliases): ?string
    {
        $updated = preg_replace_callback(
            '/resolve:\s*\{\s*\n([ \t]*)alias:\s*\{/',
            fn (array $matches): string => $matches[0].$this->viteAliasLines($aliases, $matches[1].'    '),
            $content,
            limit: 1,
        );

        return ($updated !== null && $updated !== $content) ? $updated : null;
    }

    protected function injectResolveIntoDefineConfig(string $content, array $aliases): ?string
    {
        $resolveBlock = "resolve: {\n"
            .'        alias: {'
            .$this->viteAliasLines($aliases, '            ')."\n"
            ."        },\n"
            .'    },';

        $updated = preg_replace(
            '/defineConfig\(\{/',
            "defineConfig({\n    {$resolveBlock}",
            $content,
            limit: 1,
        );

        return ($updated !== null && $updated !== $content) ? $updated : null;
    }

    protected function viteAliasLines(array $aliases, string $indent): string
    {
        $lines = '';

        foreach ($aliases as $alias => $target) {
            $lines .= "\n{$indent}'{$alias}': path.resolve(__dirname, '{$target}'),";
        }

        return $lines;
    }

    protected function ensurePathImport(string $content): string
    {
        if (preg_match('/import\s+[^;\n]*from\s*[\'"](?:node:)?path[\'"]/', $content) === 1) {
            return $content;
        }

        $import = "import path from 'path';";

        if (! preg_match_all('/^import[^\n]*\n/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $import."\n".$content;
        }

        $last = end($matches[0]);
        $offset = $last[1] + strlen($last[0]);

        return substr($content, 0, $offset).$import."\n".substr($content, $offset);
    }

    protected function updateAppCss(): void
    {
        $path = base_path('resources/css/app.css');

        if (! file_exists($path)) {
            $this->components->warn('resources/css/app.css not found - skipping @source setup.');

            return;
        }

        $content = file_get_contents($path);
        $added = false;

        foreach ($this->vendorSources as $package) {
            $source = "@source '../../vendor/tranquil-tools/{$package}/resources/js/**/*.vue';";

            if (str_contains($content, $package)) {
                $this->components->info("app.css already has @source for {$package}.");

                continue;
            }

            // Insert after the last existing @source line
            $updated = preg_replace(
                '/(@source[^\n]+\n)(?!@source)/',
                "$1{$source}\n",
                $content,
                limit: 1,
            );

            $content = ($updated !== null && $updated !== $content)
                ? $updated
                : $content."\n{$source}\n";

            $added = true;
        }

        if ($added) {
            file_put_contents($path, $content);
        }
    }
}
