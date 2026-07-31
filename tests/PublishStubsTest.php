<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use TranquilTools\CrudBuilder\CrudBuilderServiceProvider;

function crudBuilderStubFiles(): array
{
    return [
        'crud-controller.stub',
        'crud-form.stub',
        'crud-table.stub',
        'index-page.stub',
        'form-page.stub',
        'show-page.stub',
    ];
}

beforeEach(function () {
    $this->stubsExisted = is_dir(base_path('stubs'));
});

afterEach(function () {
    foreach (crudBuilderStubFiles() as $stub) {
        File::delete(base_path('stubs/'.$stub));
    }

    if (! $this->stubsExisted) {
        File::deleteDirectory(base_path('stubs'));
    }
});

it('registers the crud-builder-stubs publish tag pointing at the project stubs directory', function () {
    $paths = ServiceProvider::pathsToPublish(CrudBuilderServiceProvider::class, 'crud-builder-stubs');

    expect($paths)->toHaveCount(1);
    expect(realpath(array_key_first($paths)))->toBe(realpath(__DIR__.'/../stubs'));
    expect(reset($paths))->toBe(base_path('stubs'));
});

it('publishes every stub so make:crud picks it up as an override', function () {
    $this->artisan('vendor:publish', ['--tag' => 'crud-builder-stubs', '--force' => true])->assertSuccessful();

    foreach (crudBuilderStubFiles() as $stub) {
        expect(base_path('stubs/'.$stub))->toBeFile();
    }
});
