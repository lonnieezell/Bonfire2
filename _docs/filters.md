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
        'options' => ['one' => 'Option 1', 'two' => 'Option 2']
    ],
    'column_two' => [
        'title' => 'Column Title',
        'options' => 'methodName'
    ]
];
```

The key of each array element is the name of the `column` that you wish to filter on. The column must be within the 
table for the model (see below for other options). It is then described by an array with the keys `title`, and `options`. 
Title is the display title for that group of options. The `options` value is typically an array of keys and their display
values that the column should be filtered by. The keys must match valid values within the database.

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

