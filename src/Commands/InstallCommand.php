<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'crud-builder:install';

    protected $description = 'Install the CRUD Builder package (publishes config, updates vite.config.ts and app.css)';

    protected array $vendorSources = [
        'laravel-vue-form-builder',
        'laravel-vue-table-builder',
    ];

    public function handle(): int
    {
        $this->publishConfig();
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

        if ($hasLayout && ! $hasImport) {
            if ($withLayout) {
                $updated = $this->ensureAppLayoutImport($content, $layoutPath);
                file_put_contents($file, $updated);
                $this->components->info('Inertia layout import added to '.basename($file).'.');
            } else {
                $this->components->warn('Inertia resolve configured but no layout component found. Add import manually.');
            }
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
            : 'Inertia resolve configured in '.basename($file).'. No layout component found — add `page.default.layout ??= AppLayout` manually if needed.';

        $this->components->info($msg);
    }

    protected function detectPagesDir(): string
    {
        if (file_exists(resource_path('js/pages'))) {
            return 'pages';
        }

        if (file_exists(resource_path('js/Pages'))) {
            return 'Pages';
        }

        // Fresh project with no pages dir yet: detect from blade convention
        foreach (['resources/views/app.blade.php', 'resources/views/root.blade.php'] as $blade) {
            if (file_exists(base_path($blade))) {
                $content = file_get_contents(base_path($blade));
                if (str_contains($content, "\$page['component']") || str_contains($content, "\$page[\"component\"]")) {
                    return 'pages'; // New Laravel 13+ convention
                }
            }
        }

        return 'Pages';
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
            ."    },";

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
            'resources/js/Layout.vue'              => './Layout.vue',
            'resources/js/Layouts/AppLayout.vue'   => './Layouts/AppLayout.vue',
            'resources/js/layouts/AppLayout.vue'   => './layouts/AppLayout.vue',
            'resources/js/Layouts/Layout.vue'      => './Layouts/Layout.vue',
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

    protected function updateAppCss(): void
    {
        $path = base_path('resources/css/app.css');

        if (! file_exists($path)) {
            $this->components->warn('resources/css/app.css not found — skipping @source setup.');

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
