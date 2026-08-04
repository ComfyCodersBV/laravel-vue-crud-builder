# Changelog

All notable changes to `laravel-vue-crud-builder` will be documented in this file.

## 1.2.0 - unreleased

Released together with form-builder 1.2.0 and table-builder 1.2.0. **Read the form-builder upgrade
note**: WYSIWYG editors are now opt-in, so a project using `->editor('quill')` registers the adapter
in its app entrypoint or gets a textarea.

* Add a `package.json` declaring `@inertiajs/vue3` as this package's only direct frontend peer. The
  components it publishes reach everything else through form-builder and table-builder, whose own
  manifests now declare their ranges.
* `export-ignore` the manifest, its lockfile and the CI configuration, so `--prefer-dist` installs
  carry only `resources/`, `src/`, `config/`, `lang/` and `stubs/`.

## 1.1.0 - 2026-07-31
* `crud-builder:install` now patches the Vite aliases, adds this package's own `@source`, and register the missing `crud-builder-stubs` publish tag.
* Fix generated output: per-resource `Form.vue` supports the delete button, model-less `Table` classes type-check and no longer call `->paginate()`, and pages land in `js/Pages` when that is the project's convention
* Rewrite the installation and customizing docs to match what the code actually does.
* Fix `composer analyse` and add Pest testsuite

### Upgrade notes
* Republish your stubs if you published them (`--tag=crud-builder-stubs --force`); old copies silently miss the fixes above.
* Projects on `resources/js/Pages` get newly generated pages there instead of in `resources/js/pages`. Move the old ones so the two do not diverge.

## 1.0.3 - 2026-06-15
* Cleanup & translations

## 1.0.2 - 2026-06-15
* Add support for an optional delete button on the edit form which is prompted during scaffolding, skippable with `--destroy`

## 1.0.1 - 2026-06-15
* Generated `Table` classes use return type `Illuminate\Database\Eloquent\Builder` for the `for()` method by default
* Action-required messages in `crud:make` output now use `WARN` instead of `INFO` for better visibility

## 1.0.0 - 2026-05-26
* Initial release
