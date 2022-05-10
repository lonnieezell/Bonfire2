# Search

Bonfire provides a flexible search system that is highlighted on all areas of the admin area. In order to make
this as powerful as possible, any module may integrate into the search results, both by adding filters for the
Advanced Search form, and by providing results that will be displayed.

## Providing Search Results

The most basic integration is to register your module as a search provider. This is done by creating a class at
the root of your module's code called `SearchProvider`. It must extend `Bonfire\Search\Interfaces\SearchProviderInterface`
and fill in a few small methods.

```php
<?php

namespace Bonfire\Users;

use Bonfire\Search\Interfaces\SearchProviderInterface;
use App\Models\UserModel;

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
    public function search(string $term, int $limit=10, array $post=null): array
    {
        //
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
    <?php foreach($rows as $user) : ?>
        <tr>
            <?= view('Bonfire\Users\Views\_row_info', ['user' => $user]) ?>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
```
