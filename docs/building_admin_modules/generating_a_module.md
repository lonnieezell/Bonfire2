# Generating an Admin Module

`php spark make:module` writes a working admin module for a model you already have: a sidebar item, a list with
search and a pager, a form to create and edit records, row and bulk delete, permissions, and a feature test. The
generated files are yours to change, like any module you write by hand.

You need:

- an app with Bonfire installed (see [Installation](../intro/installation.md))
- a model, and a migration that creates its table

All paths below are relative to your project root.

## Prepare the model

The generator reads the model to decide what to write:

- **`$allowedFields`** become the form's text inputs, and the first three become the list's columns. The timestamp and
  `deleted_at` fields are left out. The first field is the one the search box searches, and its column links to the
  edit form.
- **`$returnType`** must be `'object'` or an entity class. Bonfire's row and bulk actions work on objects, so the
  generator refuses a model that returns arrays, which is CodeIgniter's default.
- **`$validationRules`** check the form when it's saved. The generated controller has no rules of its own.
- **`$useSoftDeletes`** registers the model with the [Recycler](recycler.md), so deleted records can be restored. Add
  `deleted_at` to `$allowedFields` too, or the Recycler can't restore them.

For example:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table           = 'articles';
    protected $returnType      = 'object';
    protected $useSoftDeletes  = true;
    protected $useTimestamps   = true;
    protected $allowedFields   = ['title', 'summary', 'deleted_at'];
    protected $validationRules = ['title' => 'required|max_length[200]'];
}
```

## Generate the module

Pass the module's name, in plural, and the model's class. Quote the class name, so your shell keeps its backslashes:

```console
php spark make:module Articles --model 'App\Models\ArticleModel'
```

The command writes:

```
app/
    Modules/
        Articles/
            Actions/DeleteArticles.php
            Config/Registrar.php
            Config/Routes.php
            Controllers/ArticleController.php
            Views/_table.php
            Views/form.php
            Views/list.php
            Module.php
tests/
    _support/AdminTestCase.php
    feature/ArticlesModuleTest.php
```

It writes `tests/_support/AdminTestCase.php` only when that file doesn't exist yet.

The module's name sets its URL, route names and permissions. `Articles` gives:

| What               | Value                                                                  |
|--------------------|------------------------------------------------------------------------|
| List URL           | `/admin/articles`                                                      |
| Route names        | `articles-list`, `articles-new`, `articles-create`, `articles-edit`, `articles-save` |
| Permissions        | `articles.list`, `articles.create`, `articles.edit`, `articles.delete` |
| Actions list       | `articles`                                                             |

A name of two words, like `BlogPosts`, becomes `blog-posts` in each of these.

### Options

| Option        | What it does                                                                 |
|---------------|------------------------------------------------------------------------------|
| `--model`     | The model class whose records the module manages. Required.                  |
| `--namespace` | The module's namespace. Defaults to `App\Modules\{name}`, in `app/Modules/{name}`. |
| `--force`     | Overwrite files that exist. Without it, the command writes nothing if any file it would write is already there. |

## Finish the setup

The command ends with a list of what the module still needs. On a new app, that's:

1. **Turn on module discovery.** Uncomment the `App\Modules` entry of `$appModules` in `app/Config/Bonfire.php`. See
   [Module Structure](creating_an_admin_module.md#configuring-module-locations).
2. **Register the module's namespace** in `$psr4` in `app/Config/Autoload.php`, so CodeIgniter reads its
   `Config/Registrar.php`:

    ```php
    public $psr4 = [
        APP_NAMESPACE          => APPPATH,
        'App\Modules\Articles' => APPPATH . 'Modules/Articles',
    ];
    ```

3. **Grant the permissions.** The registrar grants all four to the `admin` group and `articles.*` to `superadmin`.
   Apply them with:

    ```console
    php spark bonfire:permissions
    ```

The command leaves out the first two steps once they're done, and also tells you when the Recycler can't restore your
records.

**Check it:** reload the admin. **Articles** is in the sidebar. Open it, click **New article**, save one, and find it in
the list. Delete it from its row's menu, then restore it from **Tools › Recycler**.

## Run the generated test

The feature test checks that an admin sees the list and the new form, that a user without `articles.list` is sent to
the dashboard, and that a guest is sent to the login page:

```console
vendor/bin/phpunit tests/feature/ArticlesModuleTest.php
```

It runs every migration, your model's included, into the test database. [Testing Your Admin Module](testing.md)
explains `AdminTestCase` and how to add your own tests.

## Change what was generated

Every input is a plain text field. Change them to suit the data: a `<textarea>`, a `<select>`, or an
`<x-switch>` from the [View Components](view_components.md). Then carry on with the guides for what you want to add:

- Filters beside the search box: [Filters](filters.md)
- More row or bulk actions: [Row and Bulk Actions](actions.md)
- Results in the admin's global search: [Search](search.md)
- Your own wording in other languages: [Localization](localization.md)
