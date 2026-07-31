<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Concerns;

trait DetectsPagesDirectory
{
    protected function detectPagesDir(): string
    {
        if (file_exists(resource_path('js/pages'))) {
            return 'pages';
        }

        if (file_exists(resource_path('js/Pages'))) {
            return 'Pages';
        }

        foreach (['resources/views/app.blade.php', 'resources/views/root.blade.php'] as $blade) {
            if (! file_exists(base_path($blade))) {
                continue;
            }

            $content = file_get_contents(base_path($blade));

            if (str_contains($content, "\$page['component']") || str_contains($content, '$page["component"]')) {
                return 'pages';
            }
        }

        return 'Pages';
    }
}
