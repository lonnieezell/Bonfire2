<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Form;
use Bonfire\Resources\Column;
use Bonfire\Resources\Resource;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Model;
use ReflectionClass;

/**
 * The admin pages of every registered resource. The routes from resource_routes()
 * hand it the resource's slug.
 */
class ResourceController extends AdminController
{
    protected $viewPrefix = 'Bonfire\Resources\Views\\';

    /**
     * The sorted, paginated list. An htmx request, from a column heading or the pager, gets only the table.
     *
     * @return RedirectResponse|string
     */
    public function list(string $slug)
    {
        $resource = $this->resource($slug, 'list');

        if (! $resource->can('list')) {
            return $this->refused();
        }

        $model   = $resource->query(model($resource->model, false));
        $columns = $resource->columns();

        // Only the table's own columns can be sorted. The request's word for it is never put in a query.
        $tableColumns = $model->db->getFieldNames($model->table);
        $sortable     = array_values(array_filter(
            array_map(static fn (Column|Field $column): string => $column->handle, $columns),
            static fn (string $handle): bool => in_array($handle, $tableColumns, true),
        ));
        $sort = (string) $this->request->getGet('sort');
        $dir  = strtolower((string) $this->request->getGet('dir')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, $sortable, true)) {
            $sort = '';
            $dir  = 'asc';
        }

        // The primary key last, so that records with equal values keep their place from page to page.
        if ($sort !== '') {
            $model->orderBy($model->table . '.' . $sort, $dir);
        }
        $model->orderBy($model->table . '.' . $model->primaryKey, 'asc');

        $records = $model->paginate(setting('Site.perPage'));

        $data = [
            'resource' => $resource,
            'columns'  => $columns,
            'rows'     => array_map(fn ($record): array => [
                'record'  => $record,
                'cells'   => $this->cells($columns, $record),
                'editUrl' => $resource->allows('edit') && $resource->can('edit', $record) ? url_to($resource->slug . '-edit', $this->idOf($model, $record)) : null,
            ], $records),
            'pager'    => $model->pager,
            'sort'     => $sort,
            'dir'      => $dir,
            'sortUrls' => $this->sortUrls($sortable, $sort, $dir),
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->render($this->viewPrefix . '_table', $data);
        }

        $data['total']     = $model->pager->getTotal();
        $data['canCreate'] = $resource->allows('create') && $resource->can('create');

        return $this->renderPage($resource, 'list', $data);
    }

    /**
     * The form for a new record.
     *
     * @return RedirectResponse|string
     */
    public function create(string $slug)
    {
        $resource = $this->resource($slug, 'create');

        if (! $resource->can('create')) {
            return $this->refused();
        }

        $form = $resource->form();

        return $this->renderForm($resource, $form, null, $this->valuesOf($form, null));
    }

    /**
     * The form for a record that exists.
     *
     * @return RedirectResponse|string
     */
    public function edit(string $slug, string $id)
    {
        $resource = $this->resource($slug, 'edit');
        $record   = $this->find($resource, $id);

        if (! $resource->can('edit', $record)) {
            return $this->refused();
        }

        $form = $resource->form($this->idOf(model($resource->model, false), $record));

        return $this->renderForm($resource, $form, $record, $this->valuesOf($form, $record));
    }

    /**
     * Saves a new record, or the one with the ID. The field rules run first, then the model saves with its own,
     * and when either fails the form comes back with the errors of both.
     *
     * @return RedirectResponse|string
     */
    public function save(string $slug, ?string $id = null)
    {
        $page     = $id === null ? 'create' : 'edit';
        $resource = $this->resource($slug, $page);
        $record   = $id === null ? null : $this->find($resource, $id);

        if (! $resource->can($page, $record)) {
            return $this->refused();
        }

        $model = model($resource->model, false);
        $key   = $record === null ? [] : [$model->primaryKey => $this->idOf($model, $record)];
        $form  = $resource->form($key[$model->primaryKey] ?? '');

        $values = $form->prepare($this->request->getPost());
        $errors = $form->validate($values);
        $data   = $resource->beforeSave($this->stored($form, $values), $record);

        // Field errors don't stop the model's rules from running, so that one post shows every error.
        if ($errors !== []) {
            $model->validate($key + $data);
            [$errors, $notices] = $this->merge($form, $errors, $model->errors());
        } elseif (! $this->persist($model, $record, $data)) {
            [$errors, $notices] = $this->merge($form, $errors, $model->errors());
        } else {
            $saved = $model->find($record === null ? $model->getInsertID() : $key[$model->primaryKey]);
            $resource->afterSave($saved);

            return redirect()->to(url_to($resource->slug . '-edit', $this->idOf($model, $saved)))->with('message', lang('Resources.saved'));
        }

        // What the post didn't hold, such as a field the form can't change, is shown as it was.
        return $this->renderForm($resource, $form, $record, $values + $this->valuesOf($form, $record), $errors, $notices);
    }

    /**
     * @throws PageNotFoundException when no registered resource has the slug, or it has no such page
     */
    protected function resource(string $slug, string $page): Resource
    {
        $resource = service('resources')->get($slug);

        if ($resource === null || ! $resource->allows($page)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $resource;
    }

    /**
     * Renders the page with the package's view, or with the one beside the resource's class that has the page's name.
     *
     * @param array<string, mixed> $data
     */
    private function renderPage(Resource $resource, string $page, array $data): string
    {
        $file = dirname((string) (new ReflectionClass($resource))->getFileName()) . '/' . $page . '.php';

        // A file outside a Views folder has no view name, so a package view includes it.
        return is_file($file)
            ? $this->render($this->viewPrefix . '_override', $data + ['resourceViewFile' => $file])
            : $this->render($this->viewPrefix . $page, $data);
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     * @param list<string>          $notices errors of the model that no input has
     */
    private function renderForm(Resource $resource, Form $form, mixed $record, array $values, array $errors = [], array $notices = []): string
    {
        $model = model($resource->model, false);

        return $this->renderPage($resource, 'form', [
            'resource' => $resource,
            'record'   => $record,
            'action'   => $record === null ? url_to($resource->slug . '-create') : url_to($resource->slug . '-update', $this->idOf($model, $record)),
            'backUrl'  => $resource->allows('list') ? url_to($resource->slug . '-list') : site_url(ADMIN_AREA),
            'sections' => $form->sections($values, $errors),
            'notices'  => $notices,
        ]);
    }

    private function refused(): RedirectResponse
    {
        return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
    }

    /**
     * @throws PageNotFoundException when the model has no such record
     */
    private function find(Resource $resource, string $id): mixed
    {
        return model($resource->model, false)->find($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    private function idOf(Model $model, mixed $record): string
    {
        return (string) $this->read($record, $model->primaryKey);
    }

    /**
     * A column of a record, which is an entity, an object or an array.
     */
    private function read(mixed $record, string $handle): mixed
    {
        return is_array($record) ? ($record[$handle] ?? null) : ($record->{$handle} ?? null);
    }

    /**
     * What the form shows for the values of a record, or for a new one.
     *
     * @return array<string, string> by handle
     */
    private function valuesOf(Form $form, mixed $record): array
    {
        $values = [];

        foreach ($form->fields() as [$field, $type]) {
            $values[$field->handle] = $type->fromStored($record === null ? null : $this->read($record, $field->handle), $field);
        }

        return $values;
    }

    /**
     * What the model gets for the form's values: each field's stored value.
     *
     * @param array<string, string> $values as the form prepared them
     *
     * @return array<string, mixed> by handle
     */
    private function stored(Form $form, array $values): array
    {
        $data = [];

        foreach ($form->fields() as [$field, $type]) {
            if (isset($values[$field->handle])) {
                $data[$field->handle] = $type->toStored($values[$field->handle], $field);
            }
        }

        return $data;
    }

    /**
     * Saves through the model: an entity when the model returns entities, otherwise an array.
     *
     * @param array<string, mixed> $data
     */
    private function persist(Model $model, mixed $record, array $data): bool
    {
        if (is_a($model->returnType, Entity::class, true)) {
            $entity = $record ?? new ($model->returnType)();
            $entity->fill($data);

            // The model refuses an update with nothing to change.
            return ($record !== null && ! $entity->hasChanged()) || $model->save($entity);
        }

        return $model->save($record === null ? $data : [$model->primaryKey => $this->idOf($model, $record)] + $data);
    }

    /**
     * Adds the model's errors to the fields': an input shows its field's error, or else the model's. An error
     * of a handle that no input has can only be shown above the form.
     *
     * @param array<string, string> $errors      the fields' errors by handle
     * @param array<string, string> $modelErrors
     *
     * @return array{array<string, string>, list<string>} the errors by handle, and the ones without an input
     */
    private function merge(Form $form, array $errors, array $modelErrors): array
    {
        $handles = array_map(static fn (array $pair): string => $pair[0]->handle, $form->fields());
        $notices = [];

        foreach ($modelErrors as $handle => $message) {
            if (in_array($handle, $handles, true)) {
                $errors[$handle] ??= $message;
            } else {
                $notices[] = $message;
            }
        }

        return [$errors, $notices];
    }

    /**
     * What each column shows for a record, as text.
     *
     * @param list<Column|Field> $columns
     *
     * @return list<string>
     */
    private function cells(array $columns, mixed $record): array
    {
        return array_map(fn (Column|Field $column): string => $column instanceof Column
            ? $column->valueOf($record)
            : FieldTypes::get($column->type)->fromStored($this->read($record, $column->handle), $column), $columns);
    }

    /**
     * The link of each sortable column: the order that a click gives it, starting from the page one.
     *
     * @param list<string> $sortable
     *
     * @return array<string, string> by handle
     */
    private function sortUrls(array $sortable, string $sort, string $dir): array
    {
        $urls = [];

        foreach ($sortable as $handle) {
            $query = ['sort' => $handle, 'dir' => $handle === $sort && $dir === 'asc' ? 'desc' : 'asc'];

            $urls[$handle] = current_url() . '?' . http_build_query($query);
        }

        return $urls;
    }
}
