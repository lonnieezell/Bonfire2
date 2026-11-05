# Globals

Globals are values the whole site uses, such as its name, a contact address or a footer line. Each set is one YAML file
that you read from any template or controller with `globals('site')`.

## Creating a set

Add a file at `content/globals/<handle>.yaml` with the values:

```yaml
name: Bonfire
tagline: A fire to gather round
posts_per_page: 12
maintenance: false
launched: 2026-10-27
```

Handles use letters, numbers, `-` and `_`.

## Describing the values

A blueprint at `resources/blueprints/globals/<handle>.yaml` says what type each value is. It has the same `fields` list as
other [blueprints](blueprints.md), without a `body` field, because a set has no Markdown body:

```yaml
title: Site
fields:
  - handle: name
    type: text
  - handle: posts_per_page
    type: integer
  - handle: maintenance
    type: toggle
  - handle: launched
    type: date
```

The blueprint is optional. Without one, a value comes back as the file holds it.

## Reading a value

```php
$site = globals('site');

$site->field('name');                  // 'Bonfire'
$site->field('posts_per_page');        // 12, an int
$site->field('maintenance');           // false, a bool
$site->field('launched');              // '2026-10-27'
$site->field('phone', 'not listed');   // the default, because the file has no `phone`
```

`field()` casts the value by the type of the blueprint field that holds it. A toggle is a `bool`, an integer is an `int`
and a date or datetime is text like `2026-10-27` or `2026-10-27 09:30:00`. Other types come back as the file holds them.
A value the file leaves out or empty, or an integer or date that doesn't fit, such as `posts_per_page: lots`, gives the
default you pass, or `null`. A value the blueprint doesn't list comes back as the file holds it.

## Editing in the admin

Each set that has a blueprint gets an item in the **Content** menu, titled with the blueprint's `title` (or its handle), and
a form at `/admin/site-values/<handle>`. The form is built from the blueprint the same way an [entry's form](admin.md) is, so
it has the same [field types](field_types.md) and the same `validate:` rules. A set with no blueprint has no form and no
menu item, because there is nothing to build one from.

Users need `content.access` and `content.edit` to see the item, open the form or save it. Anyone else is sent back to the
dashboard.

Saving writes the set's YAML file, so `globals('site')` shows the new values on the next call:

- The file is written whole to a temporary file and renamed into place, so nothing reads half of it.
- Keys the blueprint doesn't list stay as they were, with their values. A field left empty doesn't add a key.
- Comments in the file are dropped, because the YAML is written afresh. Keys keep their order.
- A date written without quotes, such as `2026-10-27`, is saved back as `2026-10-27 00:00:00`. Quote a date to keep it
  exactly as you wrote it.
- If the form doesn't validate, it shows again with what you typed and an error under each field that failed. Nothing is
  written.

The form carries a hash of the file it loaded. If the file is different when you save, because someone else saved it or you
edited it by hand, the save is refused with the same message and reload link as an [entry's](admin.md#when-the-file-changed).
Your input stays in the form, and the file is left as the other change made it.

## How it works

A set is read straight from its file on each call to `globals()`. There is no index and no migration, so `content:reindex`
doesn't touch it and an edit to the file shows on the next request.

Globals work with [auto-routing](routing.md) on or off, and with no theme. A set has no front-end routes and no URL:
nothing serves `content/globals`, and it isn't an entry. Only the admin form above has a route.

`globals()` throws a `Bonfire\Content\ContentException` for a handle with no file, and for a file that isn't valid YAML,
isn't a mapping, or whose blueprint is invalid.
