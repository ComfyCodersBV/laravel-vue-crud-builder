# Auto Schema Detection

When you run `make:crud` with a model, the generated `UserForm` and `UserTable` are pre-populated with fields and
columns from the database schema.

## Column type mapping

| Database type                                           | Form field | Table column        |
|---------------------------------------------------------|------------|---------------------|
| `varchar`, `char`                                       | `Text`     | sortable            |
| `text`, `longtext`                                      | `Textarea` | -                   |
| `integer`, `bigint`, `decimal`                          | `Number`   | sortable            |
| `boolean`, `tinyint` / name starts with `is_` or `has_` | `Toggle`   | sortable            |
| `date`                                                  | `Date`     | sortable            |
| `datetime`, `timestamp`                                 | `DateTime` | sortable            |
| name contains `email`                                   | `Email`    | sortable            |
| name contains `password`                                | `Password` | excluded from table |
| name contains `color`                                   | `Color`    | sortable            |

## Excluded columns

These columns are excluded from forms by default:

```
id, password, remember_token, email_verified_at,
created_at, updated_at, deleted_at
```

And from table columns:

```
password, remember_token
```

