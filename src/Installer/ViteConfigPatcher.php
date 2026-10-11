<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Installer;

final class ViteConfigPatcher
{
    public function hasAlias(string $content, string $alias): bool
    {
        return str_contains($content, "'{$alias}'")
            || str_contains($content, "\"{$alias}\"");
    }

    /**
     * @param  array<string, string>  $aliases
     */
    public function addAliases(string $content, array $aliases): ?string
    {
        $updated = $this->injectIntoAliasBlock($content, $aliases);

        if (is_null($updated) && preg_match('/\bresolve:\s*\{/', $content) !== 1) {
            $updated = $this->injectResolveIntoDefineConfig($content, $aliases);
        }

        if (is_null($updated)) {
            return null;
        }

        if (! $this->pointsIntoProject($aliases)) {
            return $updated;
        }

        return $this->ensureFileUrlImport($updated);
    }

    /**
     * @param  array<string, string>  $aliases
     */
    private function injectIntoAliasBlock(string $content, array $aliases): ?string
    {
        $updated = preg_replace_callback(
            '/\bresolve:\s*\{\s*\n([ \t]*)alias:\s*\{/',
            fn (array $matches): string => $matches[0].$this->aliasLines($aliases, $matches[1].'    '),
            $content,
            limit: 1,
        );

        return ($updated !== null && $updated !== $content) ? $updated : null;
    }

    /**
     * @param  array<string, string>  $aliases
     */
    private function injectResolveIntoDefineConfig(string $content, array $aliases): ?string
    {
        $resolveBlock = "resolve: {\n"
            .'        alias: {'
            .$this->aliasLines($aliases, '            ')."\n"
            ."        },\n"
            .'    },';

        $updated = preg_replace(
            '/defineConfig\(\{/',
            "defineConfig({\n    {$resolveBlock}",
            $content,
            limit: 1,
        );

        return ($updated !== null && $updated !== $content) ? $updated : null;
    }

    /**
     * @param  array<string, string>  $aliases
     */
    private function aliasLines(array $aliases, string $indent): string
    {
        $lines = '';

        foreach ($aliases as $alias => $target) {
            $lines .= "\n{$indent}'{$alias}': ".$this->aliasTarget($target, $indent).',';
        }

        return $lines;
    }

    private function aliasTarget(string $target, string $indent): string
    {
        if (! str_starts_with($target, './')) {
            return "'{$target}'";
        }

        $inner = $indent.'    ';

        return "fileURLToPath(\n"
            ."{$inner}new URL(\n"
            ."{$inner}    '{$target}',\n"
            ."{$inner}    import.meta.url,\n"
            ."{$inner}),\n"
            ."{$indent})";
    }

    /**
     * @param  array<string, string>  $aliases
     */
    private function pointsIntoProject(array $aliases): bool
    {
        foreach ($aliases as $target) {
            if (str_starts_with($target, './')) {
                return true;
            }
        }

        return false;
    }

    private function ensureFileUrlImport(string $content): string
    {
        $urlImport = '/import\s*\{([^}]*)\}\s*from\s*([\'"])((?:node:)?url)\2;?/';

        if (preg_match($urlImport, $content, $matches) === 1) {
            if (preg_match('/\bfileURLToPath\b/', $matches[1]) === 1) {
                return $content;
            }

            $names = trim($matches[1]);
            $quote = $matches[2];

            return str_replace(
                $matches[0],
                "import { fileURLToPath, {$names} } from {$quote}{$matches[3]}{$quote};",
                $content,
            );
        }

        return $this->insertAfterLastImport($content, "import { fileURLToPath } from 'node:url';");
    }

    private function insertAfterLastImport(string $content, string $import): string
    {
        if (! preg_match_all('/^import\b[\s\S]*?[\'"][^\'"\n]+[\'"];?[ \t]*\n/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $import."\n".$content;
        }

        $last = end($matches[0]);
        $offset = $last[1] + strlen($last[0]);

        return substr($content, 0, $offset).$import."\n".substr($content, $offset);
    }
}
