# Localization

Bonfire's admin text comes from language files, so you can change its wording, run it in another language, and
translate your own modules the same way. This page shows how. It builds on CodeIgniter's
[Localization](https://codeigniter.com/user_guide/outgoing/localization.html) guide.

## How Bonfire finds a string

Views and controllers call `lang('File.key')`. `lang('Users.usersModTitle')` reads the `usersModTitle` key from a file
named `Users.php` in a `Language/{locale}/` folder.

Bonfire keeps those files beside the code that uses them:

| File | Where it lives in the package | Holds |
|---|---|---|
| `Bonfire.php` | `src/Language/{locale}/` | Shared admin text: save and delete messages, collection titles. |
| `Actions.php`, `Consent.php`, `Email.php`, `Groups.php`, `Recycler.php`, `Settings.php`, `Tools.php`, `Users.php`, `Widgets.php` | `src/{Module}/Language/{locale}/` | Each module's own text. |
| `Auth.php` | Shield, `vendor/codeigniter4/shield/src/Language/{locale}/` | Login, registration and magic link text. |

Bonfire registers every module folder as a namespace, so CodeIgniter searches each module's `Language/` folder too.
When more than one namespace has the same file, CodeIgniter merges them key by key, and files under `app/` are
merged last. A key in your `app/` file wins; every key you leave out still comes from Bonfire.

When a key has no translation for the current locale, CodeIgniter tries the next locale in this order, and shows the
first one it finds:

1. The full locale, like `fr-CA`.
2. The language without the region, `fr`.
3. English, `en`.
4. The key itself, like `Users.usersModTitle`.

## Override a string

To change Bonfire's wording, create a file of the same name under `app/Language/{locale}/` and put in only the keys
you're changing.

To call users "members", create `app/Language/en/Users.php`:

```php
<?php

return [
    'usersModTitle' => 'Members',
    'userGenitive'  => 'member',
];
```

Reload the admin. The Users menu item now says **Members**, and messages like "Unable to find the member." use the
new word. All the other `Users.*` keys still come from Bonfire, so new keys
added in a Bonfire update show up without you having to touch this file.

The same works for any file in the table above, including Shield's `Auth.php`. Look up the key names in the
package's `en` file.

!!! note
    Some strings take placeholders, like `'resourceSaved' => 'The {0} has been saved.'`. Keep the placeholders in
    your version. `lang()` fills them in, in order.

## Add a locale

Bonfire ships translations in Spanish, Italian, Lithuanian and Russian alongside English. To run the admin in another language, French for
example:

1. Copy Bonfire's English files into a folder named after the locale:

    ```console
    mkdir -p app/Language/fr
    cp vendor/lonnieezell/bonfire/src/Language/en/*.php \
       vendor/lonnieezell/bonfire/src/*/Language/en/*.php \
       app/Language/fr/
    ```

    Only copy whole files for a locale Bonfire doesn't ship. For `es`, `it`, `lt` or `ru`, a copied English file
    would replace the translations Bonfire already has; add only the missing keys instead, as in
    [Override a string](#override-a-string).

2. Translate the values in each copied file. Leave the keys as they are.

3. Tell CodeIgniter about the locale in `app/Config/App.php`:

    ```php
    public string $defaultLocale = 'fr';

    public array $supportedLocales = ['fr', 'en'];
    ```

    To pick the locale from each visitor's browser instead, set `$negotiateLocale = true;`. CodeIgniter then
    chooses from `$supportedLocales`.

Shield already ships `Auth.php` in many languages, French included, so you don't need to copy it. For
CodeIgniter's own strings, like the pager's, install the community translations:

```console
composer require codeigniter4/translations
```

You don't have to translate every key at once. Any key missing from your files falls back to English, so a partial
translation still works.

## Translate your own module

Give your module its own language file, named after the module, in a `Language/{locale}/` folder inside the module.
For a Blog module in `app/Modules/Blog`:

```text
app/Modules/Blog/
├── Language/
│   ├── en/
│   │   └── Blog.php
│   └── fr/
│       └── Blog.php
├── Module.php
└── ...
```

`app/Modules/Blog/Language/en/Blog.php`:

```php
<?php

return [
    'title'      => 'Blog',
    'posts'      => 'Posts',
    'postSaved'  => 'The post "{0}" has been saved.',
    'postCount'  => '{0, plural, =0 {No posts} one {# post} other {# posts}}',
];
```

Bonfire finds the folder once your module is loaded through `Config\Bonfire::$appModules` (see
[Module Structure](creating_an_admin_module.md)). Use the strings anywhere in the module, including menu
items:

```php
service('menus')->menu('sidebar')->collection('content')->addItem(new MenuItem([
    'title'      => lang('Blog.posts'),
    'namedRoute' => 'blog-posts',
]));
```

```php
return redirect()->back()->with('message', lang('Blog.postSaved', [$post->title]));
```

```php
<?= lang('Blog.postCount', [$total]) ?>
```

`{0}` placeholders and ICU `plural` rules need PHP's `intl` extension. Without it, `lang()` returns the string with
the placeholders still in it.

!!! warning
    Don't name your file after one of Bonfire's: `Bonfire`, `Actions`, `Auth`, `Consent`, `Email`, `Groups`,
    `Recycler`, `Settings`, `Tools`, `Users` or `Widgets`. CodeIgniter would merge your keys into Bonfire's, and a matching key
    in your module would change Bonfire's text.

## Check your translations

Set `$defaultLocale` to the locale and open the admin pages you translated. A string that shows in English has no
translation for that locale. A string that shows as `File.key` has no English line either, which usually means a
typo in the key or the file name.

The admin theme prints the current locale in `<html lang="…">`, which you can check in the page source.

## Contribute a translation to Bonfire

Translations of Bonfire itself are welcome, including partial ones. Put each file beside its English version in the
package: `src/Language/{locale}/Bonfire.php` and `src/{Module}/Language/{locale}/{Module}.php`. Open a pull request
on [GitHub](https://github.com/lonnieezell/Bonfire2).

The shipped translations have gaps. Spanish has only `Bonfire.php`, `Actions.php` and `Settings.php` exist only in English, and the
other files lack many of the newer English keys. Those show in English until someone translates them.
