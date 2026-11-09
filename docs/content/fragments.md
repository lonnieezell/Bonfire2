# Fragments

A fragment is a piece of content you write once and use on many pages, such as a call to action. It's an ordinary entry
in a collection that has no route, so it has drafts, the trash, preview, search and the admin like any other entry.

## A fragment collection

Set `fragments: true` in a [collection's config](collections.md#collection-config):

```yaml
# content/collections/promos.yaml
title: Promos
fragments: true
blueprints:
  - promo
```

A fragment collection can't have a `route`, because a fragment only shows inside other pages. A config with both is
refused with a message that says so. Entries live in `content/collections/promos/`, as in any collection.

To preview a draft fragment, give the collection a `template` or a `controller`, as [preview](admin.md) needs for any
collection.

You can have as many fragment collections as you like.

## Finding a fragment

`content()->fragment()` takes a slug and returns the published `Bonfire\Content\Entry`, or `null`. It never returns HTML,
so you decide how the fragment looks:

```php
<?php $sale = content()->fragment('spring-sale'); ?>

<?php if ($sale !== null) : ?>
    <aside>
        <h2><?= esc($sale->title) ?></h2>
        <?= $sale->content() ?>
    </aside>
<?php endif ?>
```

A draft, a scheduled fragment, one in the trash and one that doesn't exist all return `null`. So does the slug of an entry
outside a fragment collection.

With more than one fragment collection, put the collection's handle in front of the slug:

```php
content()->fragment('promos/spring-sale');
```

A slug on its own looks in every fragment collection. If more than one has a published fragment with that slug, it's
logged as an error and you get `null`, so name the collection when slugs can repeat. A dated collection can hold one slug
under two dates, which also gives `null`, so give each fragment its own slug.

## The fragment block

Bonfire has a built-in `fragment` [block type](blocks.md), so an editor can place a fragment among a page's blocks. It has
one [`entries` field](field_types.md), named `fragment`, that holds a single link to an entry in a fragment collection.
The form refuses a link to anything else.

A `blocks` field that doesn't list its types offers the block as soon as the site has a fragment collection. To list it
yourself, add it to `blocks`:

```yaml
- handle: sections
  type: blocks
  blocks: [hero, fragment]
```

A blueprint that lists `fragment` when the site has no fragment collection is refused. A file at
`resources/blueprints/blocks/fragment.yaml` replaces the built-in type. Give it an `entries` field named `fragment` to
keep `$block->fragment()` and the [nesting checks](#fragments-inside-fragments) working.

The front matter holds the link as a list with one UUID:

```yaml
sections:
  - type: fragment
    fragment: [3f0f0c1e-0a53-4e0c-9d7a-2f7c3a8f5d11]
```

### Its partial

The HTML is yours, so create the partial at `app/Views/blocks/fragment.php`. `php spark make:block fragment` writes one
that renders the fragment's `sections` field, for you to change. `$block->fragment()` returns the linked published entry,
or `null` when it's a draft, scheduled, in the trash or gone:

```php
<?php // app/Views/blocks/fragment.php ?>
<?php $fragment = $block->fragment(); ?>

<?php if ($fragment !== null) : ?>
    <aside class="fragment">
        <h2><?= esc($fragment->title) ?></h2>
        <?= $fragment->renderBlocks('sections') ?>
    </aside>
<?php endif ?>
```

## Fragments inside fragments

A fragment can have blocks of its own, including `fragment` blocks. Bonfire keeps track of the fragments it's rendering,
so a block renders nothing and logs an error when:

- it would embed a fragment that's already being rendered, such as A embeds B embeds A;
- it would nest fragments more than 5 deep.

The rest of the page still renders.

## Caching

Saving a fragment clears the [page cache](routing.md#caching), like saving any entry, so a cached page that embeds it
shows the edit on its next request.
