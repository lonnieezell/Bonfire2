# Filters

Every collection of items needs to be filtered at some point. Unless you're building a photo gallery or an ecommerce
site this might be when presenting posts, users, pages, etc in the admin area. Filtering allows you to specify which
columns can be filtered and then pass your model to a view cell that takes care of handling the display of the filters
for you. This ensures a consistent look and functionality in the UI while providing the least amount of work when you
need to create a UI in a hurry.

## Specifying Filterable Columns

You specify the available filters within your Model, using a class property named `filters`.

```php
protected $filters = [
    'column_name' => [
        'title' => 'Column Title',
        'type' => 'radio',
        'options' => ['one' => 'Option 1', 'two' => 'Option 2']
    ],
    'column_two' => [
        'title' => 'Column Title',
        'options' => 'methodName'
    ]
];
```

The key of each array element is the name of the `column` that you wish to filter on. The column must be within the
table for the model (see below for other options). It is then described by an array with the keys `title`, `options`, and 
optional key `type`. 
Title is the display title for that group of options. The `options` value is typically an array of keys and their display
values that the column should be filtered by. The keys must match valid values within the database. Key `type` signifies if
the filter should be composed of checkboxes (default) or radio's. If it is omitted, the default value `checkbox` would be 
assumed. In case `type` => `radio` is specified, the last option in the `options` array will be pre-selected in the filter
UI, so it should probably contain value representing all entries.

Sometimes you need to pull values from a config file, or another table, etc, in order to provide the list of options.
In this case, you can use the string `methodName` where `methodName` is a method within the model to call
that returns the list of options.

### Implementing the Filters

If all the columns match directly to columns within the model's database table, then you can make use of the `Filterable`
trait, which implements a basic `filter()` method for you.

Since model's can get crowded with test data, filter data, custom methods, events, and more, you can split out the
filter functionality into a new class that extends the model, placing your custom filter logic within that class.

```php
use App\UserModel;
use Bonfire\Traits\Filterable;

class UserFilter extends UserModel
{
    use Filterable;

    public $filters = [
        ...
    ];

    /**
     * A computed options function
     */
    protected function computeFilterA()
    {
        //
    }
}
```

### Complex Filter Columns

There are many times that you'll need to show options that don't directly relate to the model at hand. In this case
you must override the  `filter()` method on the model. Within that method you must handle all of the cases for the
options you provided manually, returning the query object.

```php
public function filter(array $params)
{
    $query = $this->builder();

    if (isset($params['foo']) && ! empty($params['foo'])) {
        $query->whereIn('foo', $params['foo']);
    }

    return $query;
}
```

## Displaying Filters in the Admin

There are 4 parts that are needed to get a working, filterable list of resources in the admin area.

### The Route

The main route for your resource list page must support both GET and POST methods.

```php
$routes->match(['get', 'post'], 'users', 'UserController::list', ['as' => 'user-list']);
```

The controller should then choose between sending either a full HTML page for a GET request, or just
sending back the table for POST requests. You might do it something like:

```php
public function list()
{
    $userModel = model('UserFilter');

    // Performs the actual filtering of the results.
    $userModel->filter($this->request->getPost('filters'));

    $view = $this->request->getMethod() == 'post'
        ? $this->viewPrefix .'_table'
        : $this->viewPrefix .'list';

    return $this->render($view, [
        'headers' => [
            'email' => 'Email',
            'name' => 'Name',
            'groups' => 'Groups',
            'last_login' => 'Last Login'
        ],
        'showSelectAll' => true,
        'users' => $userModel->paginate(setting('Site.perPage')),
        'pager' => $userModel->pager,
    ]);
}
```

### The View

Within your view there are 3 things to do.

First, ensure that the table with your resource list is wrapped in a div that uses some [Alpine.js](https://alpinejs.dev/)
to attach a `filtered` status of `false` to the div. This creates a variable that determines whether the filters list
is shown or not.

Second, insert the `x-filter-link` component into the top of that div. This provides the link that will show/hide the
list of filters. This component toggles the `filtered` value above between `true` and `false`.

Finally, call the `renderList` view cell which inserts the HTML for the list of options, all wrapped in a form
that calls your controller method via AJAX and updates the list of items without a page refresh, using some
[htmx](https://htmx.org/) magic. When calling this, you must supply the ID of the div that surrounds the table of
results as the `target`.

Here's how this is used for the list of users within Bonfire.

```html
<div x-data="{filtered: false}">
    <x-filter-link />

    <div class="row">
        <!-- List Users -->
        <div class="col" id="user-list">
            <?= $this->include('Bonfire\Users\Views\_table') ?>
        </div>

        <!-- Filters -->
        <div class="col-auto" x-show="filtered" x-transition.duration.240ms>
            <?= view_cell('Bonfire\Libraries\Cells\Filters::renderList', 'model=UserFilter target=#user-list') ?>
        </div>
    </div>
</div>
```
