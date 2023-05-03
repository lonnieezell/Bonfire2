# The Recycler

The Recycler provides an area where users can browse objects that have been deleted and either restore or purge them. 
The models for these resources must have **soft deletes** enabled. By default, only users are displayed here. 

```php
// In the model:
protected $useSoftDeletes = true;
```

## Registering A Resource

The Recycler must be told which models it should display. You do not need to do anything special with a model
before registering. You can specify models within ``app/Config/Recycler.php``: 

```php
public $resources = [
    'users' => [
        'label' => 'Users',
        'model' => App\Models\UserModel::class,
        'columns' => [
            'username', 'first_name', 'last_name', 'email'
        ]
    ],
];
```

The key of the array is the alias that you would use to specify the default resource to show when visiting the
Recycler. Each entry must contain an array that gives additional information that allows it to be displayed. 

**label** is the name as it should be displayed to the users. 

**model** is the fully qualified classname of the model to get this resource's data from.

**columns** is an array of database column names for this resource that will be displayed on the Recycler table. 
This information should only be what is needed to allow someone to find the correct record. 

## Localization of Column names in Recycler

To localize the column names, an array `recycler` should be created in the module's language file, named after the `$resource['label']` value (with the `.php` extension (in case of label 'Users' – Users.php). The array should have sub-keys `label` and `columns`, with each column name corresponding `$resource['columns']` array value. Example for module Users:

```php
    'recycler' => [
        'label'     => 'Users',
        'columns'   => [
            'id'           => 'ID',
            'username'     => 'User Name',
            'first_name'   => 'Name',
            'last_name'    => 'Surname',
            'email'        => 'Email Addr',  
        ],
    ],
```

If Recycler finds the localized strings, it will use them. Otherwise the original DB column names (first letter capitalized, undescore replaced with space) will be used.

## Modifying the Recycler Query

When the Recycler displays its records, it does a paginated search of deleted resources on the table specified
within the model. The results are ordered by the deteted at date and returned as arrays. 

There will be times when you need to modify this query. While the basics mentioned above cannot be changed without
breaking functionality, you can add requirements, or specify the fields to select, etc. This can be achieved by
creating a new method on the model, `setupRecycler`. This doesn't take any arguments, and should return the modified
model. 

Here is an example from the `UserModel` that pulls in the `email` field from the `auth_identities` table.  

```php
public function setupRecycler()
{
    return $this->select("users.*, 
        (SELECT secret 
            from auth_identities 
            where user_id = users.id
                and type = 'email_password'
            order by last_used_at desc 
            limit 1
        ) as email
   ");
}
```

## Overriding the Restore

You may override the default handling of the restore process if you have needs outside simply setting the 
`deleted_at` date to `null`. You can do this by providing a method named `recyclerRestore()` to the model. 
This method only accepts a single argument: the record primary key. 

```php
public function recyclerRestore(int $id) 
{
    return $this->where('id', $id)
        ->set('deleted_at', null)
        ->update();
}
```

## Overriding the Purge

You may override the default handling of the purge process if you have needs outside simply setting the
deleting the record. You can do this by providing a method named `recyclerPurge()` to the model.
This method only accepts a single argument: the record primary key.

```php
public function recyclerPurge(int $id) 
{
    return $this->delete($id, true);
}
```
