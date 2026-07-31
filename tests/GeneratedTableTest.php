<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TranquilTools\CrudBuilder\Commands\CrudMakeCommand;
use TranquilTools\CrudBuilder\Tests\Support\ProbeArticle;
use TranquilTools\TableBuilder\TableBuilder;

beforeEach(function () {
    Schema::create('probe_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('body')->nullable();
        $table->boolean('is_published')->default(false);
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('probe_articles');
});

it('types for() as Builder and returns a real query when a model is given', function () {
    [, $forType, $forReturn] = tableContentFor(ProbeArticle::class);

    expect($forType)->toBe('Builder');
    expect($forReturn)->toBe('ProbeArticle::query()');
});

it('types for() as array and drops the Builder import when no model is given', function () {
    [$imports, $forType, $forReturn] = tableContentFor('');

    expect($forType)->toBe('array');
    expect($forReturn)->toBe('[]');
    expect($imports)->not->toContain('use Illuminate\Database\Eloquent\Builder;');
});

it('generates a Table whose for() actually returns its declared type in both paths', function () {
    $cases = [
        'WithModel' => ProbeArticle::class,
        'WithoutModel' => '',
    ];

    foreach ($cases as $suffix => $model) {
        $resource = 'Probe'.$suffix.bin2hex(random_bytes(4));
        $path = generateTableClassFor($resource, $model);

        expect(file_get_contents($path))->not->toContain('{{');

        require $path;

        File::delete($path);

        $fqcn = 'App\\Tables\\'.$resource.'Table';
        $table = new $fqcn;

        $returnType = (new ReflectionMethod($fqcn, 'for'))->getReturnType();

        expect($returnType)->not->toBeNull();

        $declared = $returnType->getName();
        $actual = $table->for();

        expect($declared === 'array' ? is_array($actual) : $actual instanceof $declared)->toBeTrue();
    }
});

it('generates a configure() that a real TableBuilder accepts in both paths', function () {
    $cases = [
        'Runnable'.bin2hex(random_bytes(4)) => ProbeArticle::class,
        'RunnableEmpty'.bin2hex(random_bytes(4)) => '',
    ];

    foreach ($cases as $resource => $model) {
        $path = generateTableClassFor($resource, $model);

        require $path;

        File::delete($path);

        $fqcn = 'App\\Tables\\'.$resource.'Table';

        expect((new $fqcn)->make())->toBeInstanceOf(TableBuilder::class);
    }
});

function crudMakeCommandFor(string $modelFqn, string $resource = 'Probe'): CrudMakeCommand
{
    $command = new CrudMakeCommand(app('files'));
    $command->setLaravel(app());

    $reflection = new ReflectionObject($command);

    $components = $reflection->getProperty('components');
    $components->setAccessible(true);
    $components->setValue($command, new Factory(
        new OutputStyle(
            new ArrayInput([]),
            new BufferedOutput,
        ),
    ));

    $input = $reflection->getProperty('input');
    $input->setAccessible(true);
    $input->setValue($command, new ArrayInput(['name' => $resource], $command->getDefinition()));

    $modelProperty = $reflection->getProperty('modelFqn');
    $modelProperty->setAccessible(true);
    $modelProperty->setValue($command, $modelFqn);

    return $command;
}

function tableContentFor(string $modelFqn): array
{
    $command = crudMakeCommandFor($modelFqn);

    return Closure::bind(fn () => $this->buildTableContent(), $command, CrudMakeCommand::class)();
}

function generateTableClassFor(string $resource, string $modelFqn): string
{
    $command = crudMakeCommandFor($modelFqn, $resource);

    Closure::bind(fn () => $this->generateTableClass(), $command, CrudMakeCommand::class)();

    return app_path('Tables/'.$resource.'Table.php');
}
