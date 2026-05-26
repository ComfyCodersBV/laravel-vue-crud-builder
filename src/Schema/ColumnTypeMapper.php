<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Schema;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TranquilTools\FormBuilder\Fields\BaseField;
use TranquilTools\FormBuilder\Fields\Color;
use TranquilTools\FormBuilder\Fields\Date;
use TranquilTools\FormBuilder\Fields\DateTime;
use TranquilTools\FormBuilder\Fields\Email;
use TranquilTools\FormBuilder\Fields\Number;
use TranquilTools\FormBuilder\Fields\Password;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\Fields\Textarea;
use TranquilTools\FormBuilder\Fields\Toggle;

class ColumnTypeMapper
{
    public static function formFields(string $model, array $exclude = []): array
    {
        $table = (new $model)->getTable();
        $fields = [];

        foreach (Schema::getColumns($table) as $column) {
            if (in_array($column['name'], $exclude, true)) {
                continue;
            }

            $field = self::columnToFormField($column['name'], $column['type_name'], (bool) $column['nullable']);

            if (! is_null($field)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public static function tableColumns(string $model, array $exclude = []): array
    {
        $table = (new $model)->getTable();
        $result = [];

        foreach (Schema::getColumns($table) as $column) {
            if (in_array($column['name'], $exclude, true)) {
                continue;
            }

            $result[] = [
                'key' => $column['name'],
                'label' => Str::headline($column['name']),
                'sortable' => self::isSortable($column['type_name']),
            ];
        }

        return $result;
    }

    protected static function columnToFormField(string $name, string $type, bool $nullable): ?BaseField
    {
        if (str_contains($name, 'email')) {
            return Email::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable);
        }

        if (str_contains($name, 'password')) {
            return Password::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable);
        }

        if (str_contains($name, 'color') || str_contains($name, 'colour')) {
            return Color::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable);
        }

        return match (true) {
            in_array($type, ['boolean', 'tinyint'], true),
            str_starts_with($name, 'is_'),
            str_starts_with($name, 'has_') => Toggle::make($name)
                ->label(Str::headline($name)),

            in_array($type, ['text', 'mediumtext', 'longtext', 'tinytext'], true) => Textarea::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable),

            in_array($type, ['integer', 'bigint', 'smallint', 'mediumint', 'decimal', 'float', 'double'], true) => Number::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable),

            $type === 'date' => Date::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable),

            in_array($type, ['datetime', 'timestamp'], true) => DateTime::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable),

            in_array($type, ['varchar', 'char', 'string'], true) => Text::make($name)
                ->label(Str::headline($name))
                ->required(! $nullable),

            default => null,
        };
    }

    protected static function isSortable(string $type): bool
    {
        return in_array($type, [
            'varchar', 'char', 'string',
            'integer', 'bigint', 'smallint', 'mediumint',
            'decimal', 'float', 'double',
            'date', 'datetime', 'timestamp',
            'boolean', 'tinyint',
        ], true);
    }
}
