# Changelog

All notable changes to `laravel-vue-crud-builder` will be documented in this file.

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
