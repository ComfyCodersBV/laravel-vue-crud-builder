<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\PendingCommand;
use TranquilTools\CrudBuilder\Tests\Support\ProbeArticle;

beforeEach(function () {
    Schema::create('probe_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
    });
});

afterEach(function () {
    Schema::dropIfExists('probe_articles');

    foreach (generatedWidgetPaths() as $path) {
        File::delete($path);
    }
});

function generatedWidgetPaths(): array
{
    return [
        app_path('Http/Controllers/ProbeWidgetController.php'),
        app_path('Forms/ProbeWidgetForm.php'),
        app_path('Tables/ProbeWidgetTable.php'),
        app_path('Http/Requests/ProbeWidgetRequest.php'),
    ];
}

function runMakeCrud(array $extra = []): PendingCommand
{
    return test()->artisan('make:crud', array_merge([
        'name' => 'ProbeWidget',
        '--model' => ProbeArticle::class,
        '--shared' => true,
        '--destroy' => true,
    ], $extra));
}

it('exits successfully when the resource is generated', function () {
    runMakeCrud()->assertSuccessful();

    expect(app_path('Http/Controllers/ProbeWidgetController.php'))->toBeFile();
});

it('exits with a failure code when the controller already exists', function () {
    runMakeCrud()->assertSuccessful();

    runMakeCrud()->assertFailed();
});

it('exits successfully when overwriting with --force', function () {
    runMakeCrud()->assertSuccessful();

    runMakeCrud(['--force' => true])->assertSuccessful();
});
