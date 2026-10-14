# Changelog

All notable changes to `laravel-vue-crud-builder` will be documented in this file.

## 1.7.0 - 2026-10-13
* The Vite and `tsconfig.json` patching moved to laravel-vue-form-builder, so other packages share it:
  `crud-builder:install` now uses its `InstallsFrontendAliases` trait, and the install command, `make:crud` and the
  service provider take `DetectsPagesDirectory` from there. The output and the files written are unchanged.
* `TranquilTools\CrudBuilder\Installer\ViteConfigPatcher`, `TranquilTools\CrudBuilder\Installer\TsConfigPatcher` and
  `TranquilTools\CrudBuilder\Concerns\DetectsPagesDirectory` are deprecated: they are aliases of the same names under
  `TranquilTools\VueFormBuilder\Support\Installer` and are removed in the next minor. Use the new names. The aliases
  resolve through PSR-4 only, so an app that dumps with `--classmap-authoritative` cannot load the old names.
* Requires laravel-vue-form-builder 1.10 or newer.

## 1.6.0 - 2026-10-12
* Require laravel-vue-form-builder 1.9 and laravel-vue-table-builder 1.8. A crud-builder install now pulls in the
  table builder's row key (`rowKey`, the model's key name by default, so uuid-keyed models get working selection, bulk
  actions and reordering), a selection held by row key that survives paging and clears when a filter or the search
  changes, and the posted table state for tables built on a Spatie query builder, together with the form builder 1.9
  changes: Day.js date format tokens, form columns from the schema and the `@form-builder/types/...` imports.

## 1.5.0 - 2026-10-12
* Require laravel-vue-form-builder 1.8 and laravel-vue-table-builder 1.7. A crud-builder install now pulls in the
  table builder's "Select all N results" that runs a bulk action over every filtered row instead of the current page,
  the confirmation of bulk actions in the Vue component, chunking that no longer skips rows of a sorted table, and
  `withoutColumnToggle()` to hide the "Columns" menu, together with the form builder 1.8 changes.

## 1.4.0 - 2026-10-11
* Require laravel-vue-form-builder 1.7 and laravel-vue-table-builder 1.6. A crud-builder install now pulls in the
  table builder's totals row, CSV export, download row actions, date filter timezones, overlap mode and presets,
  tie-safe reordering and expiring signed routes with authorization hooks, and the form builder's new field types.
* Both builders import their icons from `@lucide/vue`, so `crud-builder:install` no longer aliases `lucide-vue-next` to
  `@lucide/vue` in Vite. It warns when `@lucide/vue` is missing from `package.json`. An alias added by 1.3 is harmless
  and stays untouched; the installer still only adds what is missing.
* The package config defaults `pages` to `'ask'` instead of reading `env('CRUD_BUILDER_PAGES')`, which Larastan flags
  outside the application's config directory.

### Upgrade notes
* Apps that only have `lucide-vue-next` need `npm install @lucide/vue`.
* `CRUD_BUILDER_PAGES` is only read when your published `config/vue-crud-builder.php` reads it. Published configs keep
  their `env()` call; without a published config, set `pages` in a published copy.

## 1.3.1 - 2026-10-11
* Require laravel-vue-form-builder 1.6 and laravel-vue-table-builder 1.5, so a crud-builder install pulls in the
  row actions, empty states, reorder persistence, date filters and configurable query keys of the table builder, and
  the timezone-aware date fields, per-use form overrides, translatable built-in texts, file previews, sections and
  method spoofing for uploads of the form builder.

## 1.3.0 - 2026-10-11

Makes `crud-builder:install` safe on Laravel's current Vue starter kit (Inertia 3, `vite-plus`, `@lucide/vue`).

* Stop rewriting the Inertia entry file. The installer no longer injects a `resolve` with `as any` and
  `page.default.layout ??= AppLayout`, which overrode the starter kit's `layout` callback and put the app layout on the
  welcome page. It now only reports whether a layout setup exists.
* Resolve the Vite aliases with `fileURLToPath(new URL(…, import.meta.url))` from `node:url` instead of
  `path.resolve(__dirname, …)`, which ES module configs do not define. Existing `__dirname` aliases get a warning.
* Add the builder `types` and `composables` paths to `tsconfig.json`, so `vue-tsc` passes on generated and published
  pages. Comments in the file are preserved.
* Alias `lucide-vue-next` to `@lucide/vue` in Vite when the app only has `@lucide/vue`.
* Running the installer twice changes nothing; it only adds what is missing.

### Upgrade notes
* Projects installed with an earlier version keep their `path.resolve(__dirname, …)` aliases. Switch them to the
  `fileURLToPath` form shown in the installation docs if your Vite config runs as an ES module.

## 1.2.0 - 2026-08-14

Released together with form-builder 1.2.0 and table-builder 1.2.0. **Read the form-builder upgrade
note:** WYSIWYG editors are now opt-in, so a project using `->editor('quill')` registers the adapter
in its app entrypoint or gets a textarea.

* Require form-builder and table-builder at `^1.2`. The 1.1 line of form-builder still carries the
  PHP that has since moved to `tranquil-tools/laravel-form-builder`, and its `make:form-request`
  command is broken on Laravel 13.24 and later, so `crud:make` fails against it.
* Add a `package.json` declaring `@inertiajs/vue3` as this package's only direct frontend peer.
* `export-ignore` the manifest, lockfile and CI config, keeping dist installs to `resources/`, `src/`,
  `config/`, `lang/` and `stubs/`.

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
