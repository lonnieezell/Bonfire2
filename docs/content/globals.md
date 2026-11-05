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
A value the file leaves out or empty, or that doesn't fit its type, such as `posts_per_page: lots`, gives the default you
pass, or `null`. A value the blueprint doesn't list comes back as the file holds it.

A [custom field type](custom_field_types.md) casts with its `cast()` method.

## How it works

A set is read straight from its file on each call to `globals()`. There is no index and no migration, so `content:reindex`
doesn't touch it and an edit to the file shows on the next request.

Globals work with [auto-routing](routing.md) on or off, and with no theme. A set has no routes and no URL: nothing serves
`content/globals`, and it isn't an entry.

`globals()` throws a `Bonfire\Content\ContentException` for a handle with no file, and for a file that isn't valid YAML,
isn't a mapping, or whose blueprint is invalid.
