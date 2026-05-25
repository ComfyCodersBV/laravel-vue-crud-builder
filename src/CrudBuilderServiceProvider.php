<?php

namespace TranquilTools\CrudBuilder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TranquilTools\CrudBuilder\Commands\CrudBuilderCommand;

class CrudBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-vue-crud-builder')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_vue_crud_builder_table')
            ->hasCommand(CrudBuilderCommand::class);
    }
}
