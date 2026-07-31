<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder;

use Inertia\Inertia;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TranquilTools\CrudBuilder\Commands\CrudMakeCommand;
use TranquilTools\CrudBuilder\Commands\InstallCommand;
use TranquilTools\CrudBuilder\Concerns\DetectsPagesDirectory;

class CrudBuilderServiceProvider extends PackageServiceProvider
{
    use DetectsPagesDirectory;

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

    public function packageBooted(): void
    {
        if (class_exists(Inertia::class)) {
            Inertia::share('vue_crud_builder_translations', fn () => trans('vue-crud-builder::crud'));
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vue-crud-builder.php' => config_path('vue-crud-builder.php'),
            ], 'crud-builder-config');

            $this->publishes([
                __DIR__.'/../resources/js/pages' => resource_path('js/'.$this->detectPagesDir()),
            ], 'crud-builder-pages');

            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/vue-crud-builder'),
            ], 'crud-builder-translations');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs'),
            ], 'crud-builder-stubs');
        }
    }
}
