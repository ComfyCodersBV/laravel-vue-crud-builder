<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TranquilTools\CrudBuilder\Commands\CrudMakeCommand;
use TranquilTools\CrudBuilder\Commands\InstallCommand;

class CrudBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-vue-crud-builder')
            ->hasCommands([
                CrudMakeCommand::class,
                InstallCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vue-crud-builder.php', 'vue-crud-builder');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'vue-crud-builder');
    }

    protected function detectJsPagesDir(): string
    {
        if (file_exists(resource_path('js/pages'))) {
            return 'pages';
        }

        if (file_exists(resource_path('js/Pages'))) {
            return 'Pages';
        }

        foreach (['resources/views/app.blade.php', 'resources/views/root.blade.php'] as $blade) {
            if (file_exists(base_path($blade))) {
                $content = file_get_contents(base_path($blade));
                if (str_contains($content, '$page[\'component\']') || str_contains($content, '$page["component"]')) {
                    return 'pages';
                }
            }
        }

        return 'Pages';
    }

    public function packageBooted(): void
    {
        if (class_exists(\Inertia\Inertia::class)) {
            \Inertia\Inertia::share('vue_crud_builder_translations', fn () =>
                trans('vue-crud-builder::crud')
            );
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vue-crud-builder.php' => config_path('vue-crud-builder.php'),
            ], 'crud-builder-config');

            $this->publishes([
                __DIR__.'/../resources/js/pages' => resource_path('js/'.$this->detectJsPagesDir()),
            ], 'crud-builder-pages');

            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/vue-crud-builder'),
            ], 'crud-builder-translations');
        }
    }
}
