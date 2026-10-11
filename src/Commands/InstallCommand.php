<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Commands;

use Illuminate\Console\Command;
use TranquilTools\CrudBuilder\Concerns\DetectsPagesDirectory;
use TranquilTools\CrudBuilder\Installer\TsConfigPatcher;
use TranquilTools\CrudBuilder\Installer\ViteConfigPatcher;

class InstallCommand extends Command
{
    use DetectsPagesDirectory;

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

    public function handle(ViteConfigPatcher $vite, TsConfigPatcher $tsConfig): int
    {
        $this->publishConfig();
        $this->updateViteConfig($vite);
        $this->updateTsConfig($tsConfig);
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

    protected function updateViteConfig(ViteConfigPatcher $vite): void
    {
        $path = $this->firstExisting(['vite.config.ts', 'vite.config.js', 'vite.config.mjs', 'vite.config.mts']);

        if (is_null($path)) {
            $this->components->warn('No vite config (vite.config.ts|js|mjs|mts) found - skipping Vite alias setup.');

            return;
        }

        $content = file_get_contents($path);
        $wanted = [
            ...$this->viteAliases,
            ...$this->iconPackageAlias(),
        ];

        $missing = array_filter(
            $wanted,
            fn (string $alias): bool => ! $vite->hasAlias($content, $alias),
            ARRAY_FILTER_USE_KEY,
        );

        $this->warnAboutDirname($content, basename($path));

        if ($missing === []) {
            $this->components->info(basename($path).' already has the Vite aliases.');

            return;
        }

        $updated = $vite->addAliases($content, $missing);

        if (is_null($updated)) {
            $this->components->warn('Could not auto-inject Vite aliases into '.basename($path).'. Add them manually (see docs).');

            return;
        }

        file_put_contents($path, $updated);

        $this->components->info('Vite aliases added to '.basename($path).': '.implode(', ', array_keys($missing)).'.');
    }

    /**
     * @return array<string, string>
     */
    protected function iconPackageAlias(): array
    {
        $dependencies = $this->npmDependencies();

        if (array_key_exists('lucide-vue-next', $dependencies)) {
            return [];
        }

        if (array_key_exists('@lucide/vue', $dependencies)) {
            return ['lucide-vue-next' => '@lucide/vue'];
        }

        $this->components->warn('No Lucide icon package found in package.json. Run `npm install @lucide/vue` and re-run this installer.');

        return [];
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

    protected function updateTsConfig(TsConfigPatcher $tsConfig): void
    {
        $path = base_path('tsconfig.json');

        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        $missing = $tsConfig->missingPaths($content, $this->tsConfigPaths);

        if ($missing === []) {
            $this->components->info('tsconfig.json already has the builder paths.');

            return;
        }

        $updated = $tsConfig->addPaths($content, $missing);

        if (is_null($updated)) {
            $this->components->warn('Could not add the builder paths to tsconfig.json. Add them manually (see docs).');

            return;
        }

        file_put_contents($path, $updated);

        $this->components->info('tsconfig.json paths added: '.implode(', ', array_keys($missing)).'.');
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
