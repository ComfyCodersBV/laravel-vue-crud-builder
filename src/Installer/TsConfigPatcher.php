<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Installer;

final class TsConfigPatcher
{
    /**
     * @param  array<string, string>  $paths
     * @return array<string, string>
     */
    public function missingPaths(string $content, array $paths): array
    {
        $code = $this->withoutComments($content);

        return array_filter(
            $paths,
            fn (string $target, string $key): bool => ! $this->coversPath($code, $key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param  array<string, string>  $paths
     */
    public function addPaths(string $content, array $paths): ?string
    {
        $code = $this->withoutComments($content);

        if (preg_match('/"paths"\s*:\s*\{/', $code, $matches, PREG_OFFSET_CAPTURE) === 1) {
            return $this->injectIntoPaths($content, $code, $matches[0][1] + strlen($matches[0][0]) - 1, $paths);
        }

        if (preg_match('/"compilerOptions"\s*:\s*\{/', $code, $matches, PREG_OFFSET_CAPTURE) === 1) {
            return $this->injectPathsBlock($content, $code, $matches[0][1] + strlen($matches[0][0]) - 1, $paths);
        }

        return null;
    }

    private function coversPath(string $code, string $key): bool
    {
        $wildcard = strtok($key, '/').'/*';

        return str_contains($code, "\"{$key}\"")
            || str_contains($code, "\"{$wildcard}\"");
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function injectIntoPaths(string $content, string $code, int $open, array $paths): ?string
    {
        $close = $this->matchingBrace($code, $open);

        if (is_null($close)) {
            return null;
        }

        $indent = $this->lineIndent($code, $open);
        $unit = $this->indentUnit($code);
        $entryIndent = $indent.$unit;
        $last = $this->lastSignificantOffset($code, $close);

        if ($last === $open) {
            $block = '{'.$this->entries($paths, $entryIndent, $unit)."\n{$indent}}";

            return substr($content, 0, $open).$block.substr($content, $close + 1);
        }

        $separator = $code[$last] === ',' ? '' : ',';

        return substr($content, 0, $last + 1)
            .$separator
            .$this->entries($paths, $entryIndent, $unit)
            .substr($content, $last + 1);
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function injectPathsBlock(string $content, string $code, int $open, array $paths): string
    {
        $unit = $this->indentUnit($code);
        $indent = $this->lineIndent($code, $open).$unit;

        $block = "\n{$indent}\"paths\": {"
            .$this->entries($paths, $indent.$unit, $unit)
            ."\n{$indent}},";

        return substr($content, 0, $open + 1).$block.substr($content, $open + 1);
    }

    /**
     * @param  array<string, string>  $paths
     */
    private function entries(array $paths, string $indent, string $unit): string
    {
        $entries = [];

        foreach ($paths as $key => $target) {
            $entries[] = "\n{$indent}\"{$key}\": [\n{$indent}{$unit}\"{$target}\"\n{$indent}]";
        }

        return implode(',', $entries);
    }

    private function matchingBrace(string $code, int $open): ?int
    {
        $depth = 0;
        $inString = false;
        $length = strlen($code);

        for ($i = $open; $i < $length; $i++) {
            $char = $code[$i];

            if ($inString) {
                $inString = ! ($char === '"' && $code[$i - 1] !== '\\');

                continue;
            }

            $inString = $char === '"';
            $depth += match ($char) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if ($depth === 0) {
                return $i;
            }
        }

        return null;
    }

    private function lastSignificantOffset(string $code, int $before): int
    {
        $i = $before - 1;

        while ($i > 0 && ctype_space($code[$i])) {
            $i--;
        }

        return $i;
    }

    private function lineIndent(string $code, int $offset): string
    {
        $lineStart = strrpos(substr($code, 0, $offset), "\n");
        $line = substr($code, $lineStart === false ? 0 : $lineStart + 1);

        preg_match('/^[ \t]*/', $line, $matches);

        return $matches[0];
    }

    private function indentUnit(string $code): string
    {
        if (preg_match('/^([ \t]+)\S/m', $code, $matches) === 1) {
            return $matches[1];
        }

        return '    ';
    }

    private function withoutComments(string $content): string
    {
        $result = '';
        $length = strlen($content);
        $state = 'code';

        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];
            $next = $content[$i + 1] ?? '';

            [$state, $kept] = match ($state) {
                'string' => [$this->stringStateAfter($content, $i), $char],
                'line' => [$char === "\n" ? 'code' : 'line', $char === "\n" ? $char : ' '],
                'block' => $this->blockCommentStep($char, $content[$i - 1]),
                default => $this->codeStep($char, $next),
            };

            $result .= $kept;
        }

        return $result;
    }

    private function stringStateAfter(string $content, int $i): string
    {
        return ($content[$i] === '"' && $content[$i - 1] !== '\\') ? 'code' : 'string';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function blockCommentStep(string $char, string $previous): array
    {
        if ($char === '/' && $previous === '*') {
            return ['code', ' '];
        }

        return ['block', $char === "\n" ? $char : ' '];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function codeStep(string $char, string $next): array
    {
        return match (true) {
            $char === '"' => ['string', $char],
            $char === '/' && $next === '/' => ['line', ' '],
            $char === '/' && $next === '*' => ['block', ' '],
            default => ['code', $char],
        };
    }
}
