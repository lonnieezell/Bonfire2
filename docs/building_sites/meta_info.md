# Meta Info

Bonfire provides Entity-Attribute-Value style storage for additional data of entities without
modifying the underlying models and database tables. By default, it can be used to add meta
information to users, and it can also be used to store nad retrieve meta information for the
entities managed by your own admin modules.

Once you configure meta info for a resource as described in the Meta Info page of
[Modules section](../modules/meta_info.md), you can access and write extra data to an entity without
the modifying the underlying resource database table:

```php
// access your user entity
$user = model('UserModel')->find(5);

$url = $this->request->getPost('website_url');
// save meta field:
$user->saveMeta('website_url', $url);

// get all meta data
$meta = $user->allMeta();

// delete meta info entry
$user->deleteMeta('website_url');

// delete all meta info for an entry
$user->deletResouceMeta();

// update meta for a resource
$post = [
    'website_url' => 'https://example.com',
    'facebook_name' => 'johnny.rose'
];
$user->syncMeta($post);
```

See more examples and detailed documentation on [Meta Info](../modules/meta_info.md) page.
