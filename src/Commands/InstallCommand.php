<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Commands;

use Illuminate\Console\Command;
use TranquilTools\VueFormBuilder\Support\Installer\DetectsPagesDirectory;
use TranquilTools\VueFormBuilder\Support\Installer\InstallsFrontendAliases;

class InstallCommand extends Command
{
    use DetectsPagesDirectory;
    use InstallsFrontendAliases;

    protected $signature = 'crud-builder:install';

    protected $description = 'Install the CRUD Builder package (publishes config, updates vite config, tsconfig.json and app.css)';

    protected array $vendorSources = [
        'laravel-vue-crud-builder',
        'laravel-vue-form-builder',
        'laravel-vue-table-builder',
    ];

    protected array $viteAliases = [
        '@form-builder' => './vendor/tranquil-tools/laravel-vue-form-builder/resources/js',
        '@table-builder' => './vendor/tranquil-tools/laravel-vue-table-builder/resources/js',
    ];

    protected array $tsConfigPaths = [
        '@form-builder/types/*' => './vendor/tranquil-tools/laravel-vue-form-builder/resources/js/types/*',
        '@form-builder/composables/*' => './vendor/tranquil-tools/laravel-vue-form-builder/resources/js/composables/*',
        '@table-builder/types/*' => './vendor/tranquil-tools/laravel-vue-table-builder/resources/js/types/*',
        '@table-builder/composables/*' => './vendor/tranquil-tools/laravel-vue-table-builder/resources/js/composables/*',
    ];

    public function handle(): int
    {
        $this->publishConfig();
        $this->installAliases($this->viteAliases);
        $this->installTsPaths($this->tsConfigPaths);
        $this->updateAppCss();
        $this->reportInertiaLayout();

        $this->newLine();
        $this->components->info('CRUD Builder installed successfully.');

        $pagesDir = $this->detectPagesDir();

        if (! file_exists(resource_path("js/{$pagesDir}/Crud/Index.vue"))) {
            $this->components->info('To use shared Crud/ pages run: `php artisan vendor:publish --tag=crud-builder-pages` and run `npm run build`.');
        }

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $this->callSilently('vendor:publish', ['--tag' => 'crud-builder-config']);
        $this->components->info('Config published → config/vue-crud-builder.php');
    }

    protected function inspectViteConfig(string $content, string $file): void
    {
        $this->checkIconPackage();
        $this->warnAboutDirname($content, $file);
    }

    protected function tsPathsDescription(): string
    {
        return 'builder paths';
    }

    protected function checkIconPackage(): void
    {
        if (array_key_exists('@lucide/vue', $this->npmDependencies())) {
            return;
        }

        $this->components->warn('The builders import their icons from @lucide/vue, which is not in package.json. Run `npm install @lucide/vue`.');
    }

    /**
     * @return array<string, string>
     */
    protected function npmDependencies(): array
    {
        $path = base_path('package.json');

        if (! file_exists($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        if (! is_array($manifest)) {
            return [];
        }

        return [
            ...($manifest['dependencies'] ?? []),
            ...($manifest['devDependencies'] ?? []),
        ];
    }

    protected function warnAboutDirname(string $content, string $file): void
    {
        if (preg_match('/@(form|table)-builder[\'"]\s*:\s*path\.resolve\(\s*__dirname/', $content) !== 1) {
            return;
        }

        $this->components->warn("{$file} resolves the builder aliases with __dirname, which ES module configs do not define. Switch them to fileURLToPath(new URL('./vendor/…', import.meta.url)) (see docs).");
    }

    protected function reportInertiaLayout(): void
    {
        $path = $this->firstExisting([
            'resources/js/inertia.ts',
            'resources/js/inertia.js',
            'resources/js/app.ts',
            'resources/js/app.js',
        ]);

        if (is_null($path)) {
            $this->components->warn('Could not find the Inertia entry file. CRUD pages render without a layout until you give them one (see docs).');

            return;
        }

        $content = file_get_contents($path);

        if (preg_match('/\blayout\s*:|\.layout\s*\??=/', $content) === 1) {
            $this->components->info('Keeping the layout setup in '.basename($path).'; CRUD pages get the layout it assigns.');

            return;
        }

        $this->components->warn('No Inertia layout is configured in '.basename($path).'. CRUD pages render without one until you add it, e.g. `layout: () => AppLayout` in createInertiaApp (see docs).');
    }

    /**
     * @param  array<int, string>  $candidates
     */
    protected function firstExisting(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (file_exists(base_path($candidate))) {
                return base_path($candidate);
            }
        }

        return null;
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
