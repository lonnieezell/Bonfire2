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
use Bonfire\Resources\Resource;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
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

        if (! auth()->user()->can($resource->permission . '.list')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $model   = $resource->query(model($resource->model, false));
        $columns = $resource->columns();

        // Only the table's own columns can be sorted. The request's word for it is never put in a query.
        $tableColumns = $model->db->getFieldNames($model->table);
        $sortable     = array_values(array_filter(
            array_map(static fn (Field $column): string => $column->handle, $columns),
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
            'rows'     => array_map(fn ($record): array => ['record' => $record, 'cells' => $this->cells($columns, $record)], $records),
            'pager'    => $model->pager,
            'sort'     => $sort,
            'dir'      => $dir,
            'sortUrls' => $this->sortUrls($sortable, $sort, $dir),
        ];

        if ($this->request->hasHeader('HX-Request')) {
            return $this->render($this->viewPrefix . '_table', $data);
        }

        $data['total'] = $model->pager->getTotal();
        $override      = $this->fileBeside($resource, 'list');

        // A file outside a Views folder has no view name, so a package view includes it.
        return $override === null
            ? $this->render($this->viewPrefix . 'list', $data)
            : $this->render($this->viewPrefix . '_override', $data + ['resourceViewFile' => $override]);
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
     * The view file that replaces the package's: the one named for the page beside the resource's class.
     */
    private function fileBeside(Resource $resource, string $page): ?string
    {
        $file = dirname((string) (new ReflectionClass($resource))->getFileName()) . '/' . $page . '.php';

        return is_file($file) ? $file : null;
    }

    /**
     * What each column shows for a record, as text.
     *
     * @param list<Field> $columns
     *
     * @return list<string>
     */
    private function cells(array $columns, mixed $record): array
    {
        return array_map(static function (Field $column) use ($record): string {
            $stored = is_array($record) ? ($record[$column->handle] ?? null) : ($record->{$column->handle} ?? null);

            return FieldTypes::get($column->type)->fromStored($stored, $column);
        }, $columns);
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
