# Resources

A resource describes a model for the admin in one class. Register it and declare its routes, and Bonfire adds the
sidebar item and the list page: a paginated, sortable table, with no controller or view of yours. Resources sit beside
hand-written modules. [Generating a module](generating_a_module.md) is still the way to get plain controllers and views.

Only the list page exists so far.

## A resource class

```php
namespace App\Modules\Categories;

use App\Models\CategoryModel;
use Bonfire\Fields\Field;
use Bonfire\Resources\Resource;

class CategoryResource extends Resource
{
    public string $model      = CategoryModel::class;
    public string $slug       = 'categories';
    public string $permission = 'categories';
    public array $pages       = ['list'];

    public function fields(): array
    {
        return [new Field('name', 'text', 'Name', 'required', null, [])];
    }
}
```

- **`$model`** is the model that holds the records. It may return entities or arrays.
- **`$slug`** is the URL after the admin area (`admin/categories`), and the prefix of the route names (`categories-list`).
- **`$permission`** is the prefix of the permissions that guard the pages. Add `categories.list` to your
  `Config\AuthGroups` permissions and matrix, as for any [module permission](permissions.md).
- **`$pages`** names the pages the resource has. `['list']` is the only page there is for now. A page that isn't named
  gets no route, so its URL is a 404.
- **`$search`** names the columns of the model's table that the search looks in. See [Search](#search).
- **`fields()`** lists the fields of a record, as the same `Bonfire\Fields\Field` objects that blueprints use.
- **`filters()`** describes the filters of the list. See [Filters](#filters).
- **`title()`** is the name in the sidebar and above the list. It is the slug with a capital by default; override it to
  change that.

## Registering it

Two lines wire the resource into the admin. In the module's `Module.php`:

```php
public function initAdmin()
{
    service('resources')->register(CategoryResource::class);
}
```

`register()` adds a sidebar item for those who have `{permission}.list`. It goes in the `content` collection with a
table icon unless you pass `collection:` and `icon:`:

```php
service('resources')->register(CategoryResource::class, collection: 'tools', icon: 'fas fa-tags');
```

It also wires the resource into the rest of the admin:

- **The Recycler** lists the resource's soft-deleted records, and restores or purges them, if the model has
  `$useSoftDeletes`. The Recycler's alias is the slug, and the columns it shows are the `$search` columns, or the
  primary key when there are none. See [The Recycler](recycler.md): the model must have `deleted_at` in its
  `$allowedFields` for a restore to work. A model that deletes for good is left out of the Recycler.
- **The admin's global search** looks in the `$search` columns, if there are any, for those who have `{permission}.list`.
  See [Search](#search).

And in the module's `Config/Routes.php`, which is where the routes have to be declared, since routing runs before
`initAdmin()`:

```php
resource_routes($routes, CategoryResource::class);
```

`resource_routes()` declares the routes that `$pages` allows. Register after the routes exist: the sidebar item looks
its link up by route name.

## The list

A user without `{permission}.list` is sent back to the dashboard with an error, and doesn't see the sidebar item.

The columns are the resource's `columns()`. By default they are the `fields()` except `textarea` and `markdown` fields
(and any type that extends the textarea type), which are too long for a table cell. Override `columns()` to choose
your own; it returns a list of `Field` objects too:

```php
public function columns(): array
{
    return [new Field('name', 'text', 'Name', '', null, [])];
}
```

A column that is a column of the model's table can be sorted: its heading is a link that sorts by it, and a second click
reverses the order. The list is in primary key order until a heading is clicked. Anything else in `?sort=` is ignored.

Paging and sorting work as plain links, so they work without JavaScript. With htmx on the page, the same links load
only the table into the list.

To add joins or eager loads before the list adds its sorting and paging, override `query()`:

```php
public function query(Model $model): Model
{
    return $model->where('archived', 0);
}
```

### Search

Name the columns to search in `$search`, and the list gets a search box:

```php
public array $search = ['name', 'description'];
```

Typing in the box narrows the list to the records where *any* of those columns contains what was typed, as you type
(`%` and `_` count as themselves). The search is `?search=` in the URL, so a link can carry it. A resource with an empty
`$search` has no search box.

### Filters

`filters()` returns the same shape as a model's `$filters` (see [Filters](filters.md)): the key is a column of the
model's table, and the value has a `title`, the `options`, and optionally a `type` of `radio`. If `options` is a string,
it names a method of the resource that returns them.

```php
public function filters(): array
{
    return [
        'status' => ['title' => 'Status', 'options' => ['live' => 'Live', 'draft' => 'Draft']],
        'category_id' => ['title' => 'Category', 'options' => 'categoryOptions'],
    ];
}

public function categoryOptions(): array
{
    return array_column(model(CategoryModel::class)->asArray()->findAll(), 'name', 'id');
}
```

The filters show in a panel that the toolbar's filter link opens, rendered by the `Bonfire\Core\Cells\Filters` view cell.
A record matches a filter when its column holds one of the chosen options, and a filter with nothing chosen doesn't
narrow the list. Only the columns that `filters()` names can be filtered.

Search, filters, sorting and paging combine. The links of the column headings and the pager keep the search and the
filters, and with htmx on the page the search box and the filter panel send their choices along with every refresh of the
table.

### Global search

A resource with `$search` also shows up when someone uses the search box in the admin header: its section lists the
records that match, and each one links to the resource's list narrowed to it. Users without `{permission}.list` don't
see it. To search differently, write a [search provider](search.md) of your own.

## Replacing the view

The views are part of the package, so they aren't copied into your Admin theme and updating Bonfire updates them. To
replace the list for one resource, put a `list.php` in the same folder as the resource class. It is an ordinary
[admin view](controllers_and_views.md) and has these variables:

| Variable    | What it is                                                                       |
|-------------|----------------------------------------------------------------------------------|
| `$resource` | the resource                                                                     |
| `$columns`  | the columns, as `Field` objects                                                  |
| `$rows`     | one array for each record: `record`, and `cells`, the text of each column        |
| `$pager`    | the pager                                                                        |
| `$total`    | the number of records                                                            |
| `$sort`     | the handle of the sorted column, or an empty string                              |
| `$dir`      | `asc` or `desc`                                                                  |
| `$sortUrls` | the link of each sortable column, by handle                                      |
| `$search`   | what was typed in the search box, or an empty string                             |
| `$narrowed` | whether a search or a filter is narrowing the list                               |

```php
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<h1><?= esc($resource->title()) ?></h1>
<div id="content-list" hx-target="#content-list">
    <?= $this->include('Bonfire\Resources\Views\_table') ?>
</div>
<?php $this->endSection() ?>
```

Sorting and paging refresh the element with the id `content-list`, and an htmx request gets only the package's
`_table` view, so keep the id and include `_table` as above to keep the refresh.

The package's `list.php` also holds the search box and the filter panel. A `list.php` of yours has them only if you copy
them from `src/Resources/Views/list.php`.
