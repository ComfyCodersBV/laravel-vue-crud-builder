<?php

declare(strict_types=1);

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TranquilTools\CrudBuilder\Commands\CrudMakeCommand;

beforeEach(function () {
    $this->sandbox = sys_get_temp_dir().'/crud-builder-pages-'.uniqid();

    File::ensureDirectoryExists($this->sandbox);

    $this->app->setBasePath($this->sandbox);
});

afterEach(function () {
    File::deleteDirectory($this->sandbox);
});

it('writes per-resource pages into the lowercase pages directory when the project uses it', function () {
    File::ensureDirectoryExists($this->sandbox.'/resources/js/pages');

    generateVuePagesFor('Product');

    expect($this->sandbox.'/resources/js/pages/Products/Index.vue')->toBeFile()
        ->and($this->sandbox.'/resources/js/pages/Products/Show.vue')->toBeFile()
        ->and($this->sandbox.'/resources/js/pages/Products/Form.vue')->toBeFile();

    expect(is_dir($this->sandbox.'/resources/js/Pages'))->toBeFalse();
});

it('writes per-resource pages into the capitalised Pages directory when the project uses it', function () {
    File::ensureDirectoryExists($this->sandbox.'/resources/js/Pages');

    generateVuePagesFor('Product');

    expect($this->sandbox.'/resources/js/Pages/Products/Index.vue')->toBeFile()
        ->and($this->sandbox.'/resources/js/Pages/Products/Show.vue')->toBeFile()
        ->and($this->sandbox.'/resources/js/Pages/Products/Form.vue')->toBeFile();

    expect(is_dir($this->sandbox.'/resources/js/pages'))->toBeFalse();
});

it('looks for the published shared pages in the detected pages directory', function () {
    File::ensureDirectoryExists($this->sandbox.'/resources/js/pages/Crud');
    File::put($this->sandbox.'/resources/js/pages/Crud/Index.vue', '<template><div /></template>');

    expect(generateVuePagesFor('Product', 'shared'))->not->toContain('vendor:publish');
});

it('warns when the shared pages have not been published yet', function () {
    File::ensureDirectoryExists($this->sandbox.'/resources/js/pages');

    expect(generateVuePagesFor('Product', 'shared'))->toContain('vendor:publish');
});

it('declares every prop the generated controller can send to the Form page', function () {
    $stub = file_get_contents(__DIR__.'/../stubs/form-page.stub');

    foreach (['form: FormSchema', 'title: string', 'destroyRoute?: string'] as $prop) {
        expect($stub)->toContain($prop);
    }
});

it('wires the Form page delete action through the shared translations', function () {
    $stub = file_get_contents(__DIR__.'/../stubs/form-page.stub');
    $shared = file_get_contents(__DIR__.'/../resources/js/pages/Crud/Form.vue');

    foreach ([$stub, $shared] as $page) {
        expect($page)
            ->toContain("useTranslations('vue_crud_builder_translations')")
            ->toContain("t('delete_confirm')")
            ->toContain("t('delete')")
            ->toContain('router.delete(props.destroyRoute!)')
            ->toContain('v-if="destroyRoute"');
    }
});

it('declares every prop the generated controller sends to the Index and Show pages', function () {
    $controller = file_get_contents(__DIR__.'/../stubs/crud-controller.stub');

    $pages = [
        'indexPage' => 'index-page.stub',
        'showPage' => 'show-page.stub',
    ];

    foreach ($pages as $placeholder => $stub) {
        preg_match(
            '/Inertia::render\(\'\{\{ '.$placeholder.' \}\}\', \[(.*?)\]\);/s',
            $controller,
            $matches,
        );

        expect($matches)->not->toBeEmpty();

        preg_match_all("/'([a-zA-Z]+)' =>/", $matches[1], $props);

        $page = file_get_contents(__DIR__.'/../stubs/'.$stub);

        foreach ($props[1] as $prop) {
            expect($page)->toContain($prop.':');
        }
    }
});

function generateVuePagesFor(string $resource, string $pageStyle = 'per-resource'): string
{
    $output = new BufferedOutput;

    $command = new CrudMakeCommand(app('files'));
    $command->setLaravel(app());

    $reflection = new ReflectionObject($command);

    $components = $reflection->getProperty('components');
    $components->setAccessible(true);
    $components->setValue($command, new Factory(
        new OutputStyle(new ArrayInput([]), $output),
    ));

    $input = $reflection->getProperty('input');
    $input->setAccessible(true);
    $input->setValue($command, new ArrayInput(['name' => $resource], $command->getDefinition()));

    $style = $reflection->getProperty('pageStyle');
    $style->setAccessible(true);
    $style->setValue($command, $pageStyle);

    Closure::bind(fn () => $this->generateVuePages(), $command, CrudMakeCommand::class)();

    return $output->fetch();
}
