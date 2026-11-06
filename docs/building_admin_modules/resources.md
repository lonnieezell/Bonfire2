# Resources

A resource describes a model for the admin in one class. Register it and declare its routes, and Bonfire adds the
sidebar item, the list page (a paginated, sortable table) and the create and edit forms, with no controller or view of
yours. Resources sit beside hand-written modules. [Generating a module](generating_a_module.md) is still the way to get
plain controllers and views.

The list, create and edit pages exist so far.

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
- **`$pages`** names the pages the resource has: `list`, `create` and `edit`. A page that isn't named gets no route,
  so its URL is a 404.
- **`fields()`** lists the fields of a record: [builders](#fields) such as `Text::make('name')`, or the same
  `Bonfire\Fields\Field` objects that blueprints use.
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

And in the module's `Config/Routes.php`, which is where the routes have to be declared, since routing runs before
`initAdmin()`:

```php
resource_routes($routes, CategoryResource::class);
```

`resource_routes()` declares the routes that `$pages` allows, named `{slug}-list`, `{slug}-new` and `{slug}-create` (the
form and its post), and `{slug}-edit` and `{slug}-update`. Register after the routes exist: the sidebar item looks its
link up by route name.

A resource that has a `create` or `edit` page saves what its fields hold, so every field has to be in the model's
`$allowedFields`, or the model would drop the value without a word. `register()` throws a `ResourceException` that names
the missing fields. In production it logs the same message and registers the resource anyway.

## Fields

A field is written in PHP with a builder from `Bonfire\Resources\Fields`. The methods have the names of the keys of a
[blueprint field](../content/blueprints.md#field-keys), and each builder is one of the [field types](../content/field_types.md):

| Builder | Type | Methods of its own |
|---|---|---|
| `Text` | `text` | |
| `Textarea` | `textarea` | `rows()` |
| `Markdown` | `markdown` | `rows()` |
| `Toggle` | `toggle` | |
| `Select` | `select` | `options()`, `multiple()` |
| `Integer` | `integer` | `min()`, `max()` |
| `Date` | `date` | `time()` |
| `Datetime` | `datetime` | |
| `Decimal` | `decimal` | `places()`, `min()`, `step()` |
| `BelongsTo` | `belongsTo` | `model()`, `title()` |

Every builder has `label()`, `validate()`, `instructions()` and `width('full')`, and `with([...])` sets any key of the
type. `Field::make('slug', type: 'text')` takes the type by name, which is how a [custom field
type](../content/custom_field_types.md) is used. The label is the handle in words unless you set one.

```php
use Bonfire\Resources\Fields\BelongsTo;
use Bonfire\Resources\Fields\Decimal;
use Bonfire\Resources\Fields\Section;
use Bonfire\Resources\Fields\Select;
use Bonfire\Resources\Fields\Text;

public function fields(): array
{
    return [
        Section::make('Basics', [
            Text::make('name')->validate('required'),
            Text::make('sku')->label('SKU')->validate('required|is_unique[products.sku,id,{id}]'),
        ])->columns(2),
        Section::make('Pricing', [
            Decimal::make('price')->places(2)->min(0),
            BelongsTo::make('category_id')->label('Category')->model(CategoryModel::class)->title('name'),
            Select::make('status')->options(['draft' => 'Draft', 'active' => 'Active']),
        ])->instructions('What it costs.'),
    ];
}
```

`Section::make($title, $fields)` groups fields under a title in one to three columns with `columns()`, and takes
`instructions()`, like a blueprint's `sections`. A field outside every section goes in a group with no title. The
form shows the fields and sections in the order `fields()` lists them.

## The forms

The create and edit pages render the fields with `Bonfire\Fields\Form`, the form that blueprints use, and a save bar. The
list links the first column of each row to the edit page, and has a button for the new record page. Both need
`{permission}.create` and `{permission}.edit`. A user without them is sent back to the dashboard with an error, and
doesn't see the link.

Override `can()` for rules that depend on the record. It runs for the page and the save, and for the list's links:

```php
public function can(string $verb, mixed $entity = null): bool
{
    // $verb is list, create or edit. $entity is the record, or null when there isn't one yet.
    if ($verb === 'edit' && $entity?->status === 'archived') {
        return false;
    }

    return parent::can($verb, $entity);
}
```

The edit page shows the tabs that other code adds with [resource tabs](resource_tabs.md), under the resource's slug:
`service('resourceTabs')->addTabFor('products', new ResourceTab([...]))`.

### Validation

A save runs the field rules (the `validate()` of each field and whatever its type needs), and then the model saves with
its own `$validationRules`. When the field rules fail, the model's rules still run, and their errors are added, so one
post shows every error under its input. An input shows its field's error before the model's. A model error for a name
that is no field is shown above the form.

`{id}` in a field's rules is the ID of the record being edited, and empty for a new one.
`is_unique[products.sku,id,{id}]` lets a product keep its own SKU and refuses another's.

### Saving

A save goes through `$model->save()`. Records are entities when the model's `$returnType` is an entity class, and arrays
otherwise. Two methods of the resource hook in:

```php
public function beforeSave(array $data, mixed $record): array
{
    // $data holds the stored value of each field, by handle. $record is the one being edited, or null.
    $data['slug'] = url_title($data['name'], '-', true);
    unset($data['featured']);

    return $data;
}

public function afterSave(mixed $record): void
{
    // The saved record, read back from the model.
}
```

`beforeSave()` runs on every post, even one with errors, so the model's rules check the values it would save. What it
returns is what the model gets: add values, or drop them. `afterSave()` runs only after the model saved. Then the user
lands on the record's edit page.

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

### Relation and computed columns

Return `Bonfire\Resources\Column` objects from `columns()`, beside or instead of fields. A dotted handle such as
`category.name` reads `name` from the record's `category`, or else the record's `category_name`, which a join in
`query()` selects. That gives the list the category's name without a query for each row. A column that calls `using()`
computes its cell from the record:

```php
public function query(Model $model): Model
{
    return $model->select('products.*, categories.name AS category_name')
        ->join('categories', 'categories.id = products.category_id', 'left');
}

public function columns(): array
{
    return [
        Column::make('name'),
        Column::make('category.name', 'Category'),
        Column::make('stock_value', 'Stock value')->using(fn ($product) => $product->price * $product->stock),
    ];
}
```

Only a handle that is a column of the model's table can be sorted, so these two can't.

## Replacing the views

The views are part of the package, so they aren't copied into your Admin theme and updating Bonfire updates them. To
replace the create and edit form for one resource, put a `form.php` in the same folder as the resource class; it gets
`$resource`, `$record` (null for a new one), `$action`, `$backUrl`, `$sections` and `$notices`, and the package's
`form.php` is the place to start from. To replace the list, put a `list.php` there. It is an ordinary
[admin view](controllers_and_views.md) and has these variables:

| Variable    | What it is                                                                       |
|-------------|----------------------------------------------------------------------------------|
| `$resource` | the resource                                                                     |
| `$columns`  | the columns, as `Field` or `Column` objects                                      |
| `$rows`     | one array for each record: `record`, `cells` (the text of each column) and `editUrl` (where the first cell links to, or null) |
| `$pager`    | the pager                                                                        |
| `$total`    | the number of records                                                            |
| `$canCreate`| whether to show the button for a new record                                      |
| `$sort`     | the handle of the sorted column, or an empty string                              |
| `$dir`      | `asc` or `desc`                                                                  |
| `$sortUrls` | the link of each sortable column, by handle                                      |

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
