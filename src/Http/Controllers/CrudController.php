<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use TranquilTools\CrudBuilder\AbstractCrud;

abstract class CrudController extends AbstractCrud
{
    public function index(): Response
    {
        return Inertia::render($this->indexPage(), [
            'table' => $this->buildTable(),
            'title' => $this->title(),
            'createRoute' => route($this->routePrefix().'.create'),
        ]);
    }

    public function show(Request $request): Response
    {
        $model = $this->resolveModel($request->route($this->routeParam()));

        return Inertia::render($this->showPage(), [
            'record' => $model->toArray(),
            'title' => Str::singular($this->title()),
            'editRoute' => route($this->routePrefix().'.edit', $model),
            'indexRoute' => route($this->routePrefix().'.index'),
        ]);
    }

    public function create(): Response
    {
        $form = $this->buildForm()
            ->action(route($this->routePrefix().'.store'))
            ->method('POST');

        return Inertia::render($this->formPage(), [
            'form' => $form,
            'title' => trans('vue-crud-builder::crud.create_title', ['name' => Str::singular($this->title())]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCreate($request);

        ($this->modelClass())::create($data);

        return redirect()
            ->route($this->routePrefix().'.index')
            ->with('success', trans('vue-crud-builder::crud.created', ['name' => Str::singular($this->title())]));
    }

    public function edit(Request $request): Response
    {
        $model = $this->resolveModel($request->route($this->routeParam()));

        $form = $this->buildForm($model)
            ->action(route($this->routePrefix().'.update', $model))
            ->method('PATCH');

        return Inertia::render($this->formPage(), [
            'form' => $form,
            'title' => trans('vue-crud-builder::crud.edit_title', ['name' => Str::singular($this->title())]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $model = $this->resolveModel($request->route($this->routeParam()));
        $data = $this->validateUpdate($request, $model);
        $model->update($data);

        return redirect()
            ->route($this->routePrefix().'.index')
            ->with('success', trans('vue-crud-builder::crud.updated', ['name' => Str::singular($this->title())]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $model = $this->resolveModel($request->route($this->routeParam()));
        $model->delete();

        return redirect()
            ->route($this->routePrefix().'.index')
            ->with('success', trans('vue-crud-builder::crud.deleted', ['name' => Str::singular($this->title())]));
    }
}
