# Auto Schema Detection

When you run `make:crud` with a model, the generated `UserForm` and `UserTable` are pre-populated with fields and
columns from the database schema. The detected columns are printed before generation starts.

Without a model - or when the class does not exist, or the schema cannot be read - an empty `Form` and a `Table` with a
single `id` column are generated instead, and the command warns about it.

## Column type mapping

Rules are applied top to bottom; the first match wins:

| Column                                                  | Form field | Table column        |
|---------------------------------------------------------|------------|---------------------|
| name ends with `_id` on an integer type                 | `Select`   | sortable            |
| name contains `email`                                   | `Email`    | sortable            |
| name contains `password`                                | `Password` | see below           |
| name contains `color` or `colour`                       | `Color`    | sortable            |
| `boolean`, `tinyint` / name starts with `is_` or `has_`  | `Toggle`   | sortable            |
| `text`, `tinytext`, `mediumtext`, `longtext`            | `Textarea` | not sortable        |
| `integer`, `bigint`, `smallint`, `mediumint`            | `Number`   | sortable            |
| `decimal`, `float`, `double`                            | `Number`   | sortable            |
| `date`                                                  | `Date`     | sortable            |
| `datetime`, `timestamp`                                 | `DateTime` | sortable            |
| `varchar`, `char`, `string`                             | `Text`     | sortable            |
| any other type                                          | skipped    | not sortable        |

Types not in this list (for example `json`, `uuid` or `binary`) get no form field at all - the column is skipped and you
add the field by hand. They are still added as a non-sortable table column.

Nullable columns are generated as-is; non-nullable columns get `->required()`. Labels are the headline form of the
column name (`published_at` becomes `Published At`).

## Relation selects

A column ending in `_id` on an integer type becomes a `Select` instead of a `Number`. The related model is resolved
through the `namespaces.models` config (`user_id` → `App\Models\User`) and, when that class exists, the options are
filled from it:

```php
Select::make('user_id')
    ->label('User')
    ->options(\App\Models\User::query()->pluck('name', 'id')->toArray())
    ->required(),
```

The label column is the first of `name`, `title`, `label`, `email`, `username`, `slug` present on the related table,
falling back to `id`. When the related model class does not exist, an empty options array with a `// TODO` comment is
generated. Nullable foreign keys get `->nullable()` instead of `->required()`.

## Global search

If the table has any of `name`, `title`, `email`, `username`, `slug`, the generated `Table` enables global search on
exactly those columns:

```php
$table
    ->withGlobalSearch(columns: ['name', 'email'])
    ->column('id', 'ID', sortable: true)
    // …
    ->paginate(25);
```

## Excluded columns

Excluded from generated forms:

`id, password, remember_token, email_verified_at, created_at, updated_at, deleted_at`

Excluded from generated tables - by exact column name, not by a name match, so a column such as `password_hint` is
still added as a table column:

`password, remember_token`
