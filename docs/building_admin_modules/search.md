# Search

Bonfire provides a flexible search system that is highlighted on all areas of the admin area. In order to make
this as powerful as possible, any module may integrate into the search results, both by adding filters for the
Advanced Search form, and by providing results that will be displayed.

## Providing Search Results

The most basic integration is to register your module as a search provider. This is done by creating a class at
the root of your module's code called `SearchProvider`. It must extend `Bonfire\Search\Interfaces\SearchProviderInterface`
and fill in a few small methods. Below is an example search provider
for module Users.

```php
<?php

namespace Bonfire\Users;

use Bonfire\Search\Interfaces\SearchProviderInterface;
use Bonfire\Users\Models\UserModel;

class SearchProvider extends UserModel implements SearchProviderInterface
{
    /**
     * Performs a primary search for just this resource.
     *
     * @param string     $term
     * @param int        $limit
     * @param array|null $post
     *
     * @return array
     */
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        return $this->select('users.*')
            ->distinct()
            ->join('auth_identities', 'auth_identities.user_id = users.id', 'inner')
            ->like('first_name', $term, 'right', true, true)
            ->orlike('last_name', $term, 'right', true, true)
            ->orLike('username', $term, 'right', true, true)
            ->orLike('secret', $term, 'both', true, true)
            ->orderBy('first_name', 'asc')
            ->findAll($limit);
    }

    /**
     * Returns the name of the resource.
     *
     * @return string
     */
    public function resourceName(): string
    {
        return 'users';
    }

    /**
     * Returns a URL to the admin area URL main list
     * for this resource.
     *
     * @return string
     */
    public function resourceUrl(): string
    {
        return ADMIN_AREA .'/users';
    }

    /**
     * Returns the name of the view to use when
     * displaying the list of results for this
     * resource type.
     *
     * @return string
     */
    public function resultView(): string
    {
        return 'Search/users';
    }
}
```

**search()**

Given the search term it will return an array of any search results for this resource type. You'll see this SearcProvider
extends the UserModel. It is not necessary to do so, but is a simple way to get access to the model features when you
need them.

If you want the search to find not only the data from the main table, but also
the related meta info (see [Meta Info](../modules/meta_info.md)), you should:

1. Put a property `$includeMetaFieldsInSearech` into the module config file
    (example for `app/Config/Users.php`, since, suppose, we want to be able to search
    for users by their blog urls, which we keep in the `meta_info` table):

    ```php
        public $includeMetaFieldsInSearech = [
        'blog',
        ];
    ```

2. Employ `Bonfire\Core\Traits\SearchInMeta` trait: put `use Bonfire\Core\Traits\SearchInMeta;` before the SearchProvider class declaration and `use SearchInMeta` after the class declaration;

3. Write the `search()` method employing the trait methods `joinMetaInfo` and `orLikeInMetaInfo()` when constructing the query:

    ```php
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        $query = $this->select('users.*')->distinct();
        $query->join('auth_identities', 'auth_identities.user_id = users.id', 'inner');

        $searchInMeta = setting('Users.includeMetaFieldsInSearech');

        if (!empty($searchInMeta)) {
            // first argument is the resource entity name, second – the DB table name
            $query->joinMetaInfo('Bonfire\Users\User', 'users');
        }

        $query->like('first_name', $term, 'right', true, true)
            ->orlike('last_name', $term, 'right', true, true)
            ->orLike('username', $term, 'right', true, true)
            ->orLike('secret', $term, 'both', true, true);

        if (!empty($searchInMeta)) {
            foreach ($searchInMeta as $metaField) {
                // here syntax almost exactly like that of orLike()
                $query->orLikeInMetaInfo($metaField, $term, 'both', true, true);
            }
        }

        $query->orderBy('first_name', 'asc');

        return $query->findAll($limit);
    }
    ```

**resourceName()**

The name of the resource. This is displayed as a header on the search results page where it shows the top 10 results
from all search providers.

**resourceUrl()**

Returns the relative URL to the main page for this resource. In this case it takes you to the list of users.

**resultView()**

Returns the name of the view that should be used to display the results on the overview page. This MUST be in the
Admin theme folder in order to be found. This can be a fairly straight-forward view file:

```php
<table class="table table-hover">
    <?= $this->setData(['headers' => [
        'email' => 'Email',
        'username' => 'Username',
        'groups' => 'Groups',
        'last_active' => 'Last Active'
    ]])->include('_table_head') ?>
    <tbody>
    <?php service('actions')->for('users')->showing($rows) ?>
    <?php foreach($rows as $user) : ?>
        <tr>
            <?= view('Bonfire\Users\Views\_row_info', ['user' => $user]) ?>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
```
