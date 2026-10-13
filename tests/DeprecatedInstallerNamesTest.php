<?php

declare(strict_types=1);

use TranquilTools\CrudBuilder\Concerns\DetectsPagesDirectory as OldDetectsPagesDirectory;
use TranquilTools\CrudBuilder\Installer\TsConfigPatcher as OldTsConfigPatcher;
use TranquilTools\CrudBuilder\Installer\ViteConfigPatcher as OldViteConfigPatcher;
use TranquilTools\VueFormBuilder\Support\Installer\TsConfigPatcher;
use TranquilTools\VueFormBuilder\Support\Installer\ViteConfigPatcher;

it('still resolves the patchers under their old crud-builder names', function () {
    expect(new OldViteConfigPatcher)->toBeInstanceOf(ViteConfigPatcher::class)
        ->and(new OldTsConfigPatcher)->toBeInstanceOf(TsConfigPatcher::class)
        ->and((new OldViteConfigPatcher)->hasAlias("'@form-builder': 'x'", '@form-builder'))->toBeTrue();
});

it('still offers the pages directory trait under its old crud-builder name', function () {
    $command = new class
    {
        use OldDetectsPagesDirectory;

        public function pagesDirectory(): string
        {
            return $this->detectPagesDir();
        }
    };

    expect($command->pagesDirectory())->toBeIn(['pages', 'Pages']);
});
