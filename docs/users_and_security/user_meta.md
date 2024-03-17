# User Meta Info

By default, Bonfire comes with a pretty limited amount of data saved to a user. Most applications will likely require
additional information available to the user, whether this is a bio, a website URL, the name of the school they
attended, or anything else. Bonfire provides User Meta information that you can easily define to add additional
information to a user. This data is seamlessly integrated into the Create/Edit User form so you do not have to
modify that manually.

## Defining Meta Fields

All fields are defined within `app/Config/Users.php`. Two fields are provided (though commented out) as examples.
Additionally, if you need labels for fields in validation messages, or if you need custom validation messages,
expand the $metaFields 'validation' value into multidimensional array of it's own, with keys
`label` and `rules` like it is done with 'baz' value below (in case of custom error messages, you can add them
in `errors` subarray, see
[Validation in Codeigniter 4 documentation](https://codeigniter4.github.io/userguide/libraries/validation.html) 
for more details).

```php
public $metaFields = [
        'Example Fields' => [
            'foo' => ['label' => 'Foo', 'type' => 'text', 'validation' => 'permit_empty|string'],
            'Bar' => ['type' => 'text', 'validation' => 'required|string'],
            'baz' => [
                'label' => 'Baz',
                'type' => 'checkbox',
                'validation' => [
                    'label' => 'Baz',
                    'rules' => 'required|in_list[true,false]'
                ],
            ],
        ],
    ];
```



Each major grouping within the `$metaFields` array specifies a fieldset legend name. This provides logical grouping
of fields within the Edit User form.

Each entry within this fieldset is a single piece of information. The key is the name that you would refer to the
field by later. Then each has an array of information that defines it. The array has the following options that
you can specify:

- **label** is the label shown for the HTML input (if **label** is within validation sub-array, it is displayed in validation messages).

- **type** is the type of HTML input. Currently most of the text-type inputs are supported (text, password, email, date, number, etc)
    as well as checkboxes, and textareas for when you need more information.

- **validation** is the set of validation rules that should be applied to this field when saving, or a container for validation array of
  a field (with optional `label`, `rules`, `errors` keys).

## Using Meta Info

The User entity has a trait applied, `HasMeta`, that provides all the functionality you should need to work
with the meta information for that user.

**meta(string $key)**

This returns the value of the user's meta named `$key`, or `null` if nothing has been set for that user.
The name is the key of the array mentioned above.

```php
$website = $user->meta('website_url');
```

**allMeta()**

This returns all meta fields for this user. Note that it returns the full database results, not just the name/value.

```php
$meta = $user->allMeta();

var_dump($meta);

// Returns:
[
    'resource_id' => 123,
    'class' => 'Bonfire\Users\User',
    'key' => 'website_url',
    'value' => 'https://example.com',
    'created_at' => '2021-01-12 12:31:12',
    'updated_at' => '2021-01-12 12:31:12',
]
```

**hasMeta(string $key)**

Used to check if a user has a value set for the given meta field.

```php
if ($user->hasMeta('foo')) {
    //
}
```

**saveMeta(string $key, $value)**

Saves a single meta value to the user. This is immediately saved. There is no need to save the User through the UserModel.

```php
$url = $this->request->getPost('website_url');
$user->saveMeta('website_url', $url);
```

**deleteMeta(string $key)**

Deletes a single meta value from the user. This is immediately deleted. There is no need to save the User through the UserModel.

```php
$user->deleteMeta('website_url');
```

**syncMeta(array $post)**

Given an array of key/value pairs representing the name of the meta field and it's value, this will update existing
meta values, insert new ones, and delete any ones that were not passed in. Useful when grabbing the information from
a form and updating all the values at once.

```php
$post = [
    'website_url' => 'https://example.com',
    'facebook_name' => 'johnny.rose'
];
$user->syncMeta($post);
```

**metaValidationRules(string $configClass, string $prefix=null)**

This examines the specified config file and returns an array with the names of each field and their validation rules,
ready to be used within CodeIgniter's validation library. If your form groups the name as an array, (like `meta[website_url]`)
you may specify the prefix to append to the field names so that validation will pick it up properly.

```php
$rules = $user->metaValidationRules('\Config\Users', 'meta');

var_dump($rules);

// Returns:
[
    'meta.website_url' => 'required|string|valid_url',
]
```

## Using Meta on Your Classes

The meta solution is flexible enough to be used outside of Users in your own classes, if desired. There are two steps
required to make that work.

1. Create a Config file. This should include a `$metaFields` array, formatted as described above.
2. Add the `HasMeta` trait to the Entity class that represents your resource.

```php
use Bonfire\Traits\HasMeta;
use CodeIgniter\Entity;

class CustomEntity extends Entity
{
    use HasMeta;
}
```
