<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('never asks through a native browser dialog in the shipped pages and stubs', function (string $directory) {
    $files = collect(File::allFiles(__DIR__.'/../'.$directory))
        ->filter(fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['vue', 'ts', 'stub'], true))
        ->reject(fn (SplFileInfo $file): bool => str_starts_with($file->getFilename(), 'crud-'));

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect(File::get($file->getPathname()))
            ->not->toMatch('/(?<![\w.])(window\.)?(confirm|alert|prompt)\s*\(/');
    }
})->with([
    'resources/js',
    'stubs',
]);
