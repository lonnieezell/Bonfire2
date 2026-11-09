# Blocks

A blocks field holds a list of blocks in an entry's front matter. Each block has a type, such as `hero` or `cta`, with
its own fields, and you render each type through a view of your own. That lets one page mix a hero, a few text sections
and a call to action, in an order the file sets, while you keep control of the HTML.

You can write the blocks in the entry's file, and the admin edits them as a list of cards that can be added, moved,
copied and deleted. See [Editing blocks](admin.md#editing-blocks).

## Block types

A block type is a file at `resources/blueprints/blocks/<type>.yaml`. It looks like a [blueprint](blueprints.md): a
`title`, an optional `icon`, and a list of `fields`.

```yaml
# resources/blueprints/blocks/hero.yaml
title: Hero
icon: star
fields:
  - handle: heading
    type: text
    validate: required|max_length[80]
  - handle: image
    type: text
  - handle: dark
    type: toggle
```

The fields use the same [keys](blueprints.md#field-keys) and [types](field_types.md) as a blueprint's, with these limits:

- A block can't have a `slug`, `terms` or `blocks` field. A slug names an entry's file, terms are indexed, and a block
  can't hold blocks.
- A block can't have a field with the handle `type`, because the block's own type is kept under that key.

A block type that breaks one of these is refused with a message that names the file and the field.

The `fragment` block type is built in and needs no file. See [Fragments](fragments.md#the-fragment-block).

## The blocks field

Add a `blocks` field to a blueprint:

```yaml
- handle: sections
  type: blocks
  blocks: [hero, cta]
  min_items: 1
  max_items: 10
```

| Key | Default | What it does |
|---|---|---|
| `blocks` | Every file in `resources/blueprints/blocks`, and `fragment` if the site has a fragment collection | The block types the field allows. |
| `max_items` | None | The most blocks there can be. |
| `min_items` | None | The fewest blocks there can be. |

The blueprint is refused if a type it lists has no file, or if there are no block types at all.

The front matter holds a list of maps. Each has a `type` key, and the rest are that block's fields:

```markdown
---
id: 3f0f0c1e-0a53-4e0c-9d7a-2f7c3a8f5d11
title: Home
sections:
  - type: hero
    heading: Welcome
    dark: true
  - type: cta
    label: Join us
    count: 3
---
```

Run `php spark content:reindex` after you edit a file by hand. See [The index and deploys](the_index.md).

## Rendering blocks

Each block type renders through a partial at `app/Views/blocks/<type>.php`. Render a whole field, in the order the file
lists the blocks:

```php
<?= $entry->renderBlocks('sections') ?>
```

The partial gets the block as `$block`:

```php
<?php // app/Views/blocks/hero.php ?>
<section class="hero<?= $block->field('dark') ? ' hero-dark' : '' ?>">
    <h1><?= esc($block->field('heading')) ?></h1>
    <x-button href="/join">Join us</x-button>
</section>
```

The partial renders through `view()`, so [View Components](../building_sites/components.md) work in it. A partial's
variables don't leak into the next one, so every block gets its own `$block`.

To change where the partials live, set `$blockViewPath` in `Config\Content`. It's a view folder, `blocks` by default.
See [Configuration](reference.md).

### A partial that is missing

If a block's partial doesn't exist, rendering throws a `Bonfire\Content\ContentException` that names the file it expected,
so you see the problem while you build. In production it is logged instead, and the block renders nothing. The rest of the
blocks still render.

## The block objects

`$entry->blocks('sections')` returns a `Bonfire\Content\BlockList`. You can loop over it, count it, and filter it:

| Method | Returns |
|---|---|
| `foreach`, `count()` | The blocks, in the order the file lists them. |
| `ofType('hero', 'cta')` | A list of only the blocks of these types. |
| `first()` | The first block, or `null` when there are none. |
| `render()` | Every block through its partial. |

```php
foreach ($entry->blocks('sections')->ofType('hero') as $hero) {
    echo $hero->field('heading');
}
```

`$entry->block('sections')` is the first block, or `null`. `$entry->renderBlocks('sections')` is
`blocks('sections')->render()`.

A field the entry doesn't have gives an empty list, and so does an item that isn't a map with a `type`.

Each `Bonfire\Content\Block` has:

| Member | What it is |
|---|---|
| `type` | The block's type, such as `hero`. |
| `field($name, $default = null)` | A value of the block. |
| `fragment()` | The published entry a `fragment` block links to, or `null`. See [Fragments](fragments.md#its-partial). |
| `entry` | The entry the block belongs to. |
| `render()` | The block through its partial. |

`field()` casts a value by the block blueprint's field type, the way [`globals()`](globals.md) does: a `toggle` is
`true` or `false`, and an `integer` is an `int`. A value the file leaves out, leaves empty, or that doesn't fit its type
gives the default. A key the block blueprint doesn't list is returned as the file holds it.

If a block type's file is gone, its blocks are still read, but with every value as the file holds it.

## Saving

The entry form checks a blocks field when you save. It refuses:

- more blocks than `max_items`, or fewer than `min_items`, even when the field is empty;
- a block of a type the field doesn't allow, or that has no file;
- a block field that fails its own `validate` rules or its type's checks.

Errors are keyed by the position the editor sees, counting from 1: the first block is `1`. The form's errors, by the
field's handle, hold the first problem, such as `Block 2 (Hero): The Heading field is required.`, and the others are under
`sections.2.heading`, or `sections.2` for a block's type.

Saving in the admin writes each block's fields as its blueprint lists them, so a key you wrote by hand that the block
blueprint doesn't list is dropped. A block whose type has no file any more is the exception: it is written back as it was.

The editor names each block's inputs with a random key, such as `fields[sections][b_3f9a2c][heading]`, and takes the
order of the blocks from the order they are posted in. The keys are not saved.
