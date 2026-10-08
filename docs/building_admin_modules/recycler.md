# The Recycler

The Recycler provides an area where users can browse objects that have been deleted and either restore or purge them. 
The models for these resources must have **soft deletes** enabled. By default, only users are displayed here. 

```php
// In the model:
protected $useSoftDeletes = true;
```

## Registering A Resource

The Recycler must be told which models it should display. You do not need to do anything special with a model
before registering. A module registers its resources in the `initAdmin()` method of its `Module.php`: 

```php
public function initAdmin()
{
    service('recycler')->register('photos', 'Photos', PhotoModel::class, [
        'title', 'caption',
    ]);
}
```

The arguments are, in order:

**alias** is what you would use to specify the default resource to show when visiting the Recycler, and is part of
the URLs for restoring and purging.

**label** is the name as it should be displayed to the users. 

**model** is the fully qualified classname of the model to get this resource's data from.

**columns** is an array of database column names for this resource that will be displayed on the Recycler table. 
This information should only be what is needed to allow someone to find the correct record. 

An app can also add a resource, or replace how a module's resource is displayed, within `app/Config/Recycler.php`. 
An entry here wins over a module's registration with the same alias:

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
            'email'        => 'Email',
        ],
    ],
```

If Recycler finds the localized strings, it will use them. Otherwise the original DB column names (capitalized, undescore replaced with space) will be used.

## Changing How A Model Is Recycled

The Recycler does three things with a resource: it lists the deleted records, restores one, and purges one. 
By default it lists the records through the model's soft delete field, ordered by the date they were deleted and
returned as arrays; restores by setting that field to `null`; and purges by deleting the record through the model. It 
uses the primary key and soft delete field of the model, whatever they are called. It will only restore or purge a 
record that is in the Recycler.

To change any of the three, have the model implement the matching interface from `Bonfire\Recycler\Interfaces`.
Implement only the ones you need. A method with the right name on a model that does not implement the interface is not
called.

### Modifying the Recycler Query

While the basics mentioned above cannot be changed without breaking functionality, you can add requirements, or 
specify the fields to select, etc. Implement `CustomRecyclerQuery` and its `setupRecycler()` method. It doesn't take
any arguments, and should return the modified model. 

Here is an example from the `UserModel` that pulls in the `email` field from the `auth_identities` table.  

```php
class UserModel extends ShieldUsers implements CustomRecyclerQuery
{
    public function setupRecycler(): static
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
}
```

### Overriding the Restore

You may override the default handling of the restore process if you have needs outside simply setting the 
`deleted_at` date to `null`. Implement `CustomRecyclerRestore` and its `recyclerRestore()` method. 
It only accepts a single argument, the record primary key, and returns whether the record was restored. 

```php
public function recyclerRestore(int $id): bool
{
    return $this->where('id', $id)
        ->set('deleted_at', null)
        ->update();
}
```

### Overriding the Purge

You may override the default handling of the purge process if you have needs outside simply deleting the record. 
Implement `CustomRecyclerPurge` and its `recyclerPurge()` method.
It only accepts a single argument, the record primary key, and returns whether the record was purged.

```php
public function recyclerPurge(int $id): bool
{
    return (bool) $this->delete($id, true);
}
```
