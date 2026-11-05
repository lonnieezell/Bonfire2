# Field types

The `type` of a [blueprint](blueprints.md) field decides how it looks in the form, how it's validated and how its value
is saved in the front matter.

| Type | Input | Saved as |
|---|---|---|
| [`text`](#text) | One line | A string |
| [`textarea`](#textarea) | Several lines | A string |
| [`markdown`](#markdown) | Several lines, with a preview | A string, or the body |
| [`slug`](#slug) | One line, filled from the title | The file's name |
| [`toggle`](#toggle) | A switch | `true` or `false` |
| [`date`](#date) | A date, or a date and time | A string |
| [`datetime`](#datetime) | A date and time | A string |
| [`select`](#select) | A dropdown, or a list to pick several from | A string, or a list of them |
| [`integer`](#integer) | A number | A number |
| [`entries`](#entries) | Entries to link to | A list of UUIDs |
| [`terms`](#terms) | A checkbox for each term | A list of slugs |

You can add types of your own. See [Custom field types](custom_field_types.md).

A field left empty doesn't add a key to the file, though a key that is already there keeps its empty value.

Besides the [keys every field has](blueprints.md#field-keys), each type reads the keys in its own table.

## text

A single line of text. It has no keys of its own.

## textarea

Several lines of text.

| Key | Default | What it does |
|---|---|---|
| `rows` | `6` | The height of the input, in lines. |

## markdown

A textarea with a **Preview** tab that renders the text through CommonMark. Give it the handle `body` to edit the
entry's body. See [Editing entries](admin.md#the-markdown-preview).

| Key | Default | What it does |
|---|---|---|
| `rows` | `16` | The height of the input, in lines. |

## slug

The entry's slug, which names its file and its URI. It isn't saved in the front matter, and it can't change once the
entry exists, so the form shows it disabled.

| Key | Default | What it does |
|---|---|---|
| `from` | `title` | The field the slug is made from. |

On a new entry, the input fills itself from the `from` field as you type, until you type in it. Left empty, the slug is
made from that field on the server. A slug is lowercase letters and numbers with `-` between, up to 100 characters, and
the entry is refused if its file or URI is already taken.

## toggle

A switch, which saves `true` or `false`. It has no keys of its own.

## date

A date, saved as `2026-10-27`. A value that isn't a date is refused.

| Key | Default | What it does |
|---|---|---|
| `time` | `false` | With `true`, it's a date and time, saved as `2026-10-27 09:30:00` in your app's timezone. |

## datetime

A date and time, saved as `2026-10-27 09:30:00` in your app's timezone. It's a `date` field with `time: true`, under its
own name, and has no keys of its own. A value that isn't a date and time is refused.

## select

One option from a list, saved as a string, or several, saved as a list.

| Key | Default | What it does |
|---|---|---|
| `options` | None | A list of values, or a map of value to label. The value is what the file holds and the label is what the form shows. |
| `multiple` | `false` | With `true`, the input is a list to pick several from, saved as a list. |

```yaml
- handle: mood
  type: select
  options: [happy, sad]
- handle: genre
  type: select
  options:
    sf: Science fiction
    fan: Fantasy
- handle: tags
  type: select
  multiple: true
  options: [php, yaml, css]
```

A value that isn't one of the options is refused. Values are saved as text, so an option `1` is saved as `'1'`. Add
`required` to `validate` to make an empty choice an error.

## integer

A whole number, saved as a number. A value that isn't a whole number, or is outside the bounds, is refused.

| Key | Default | What it does |
|---|---|---|
| `min` | None | The lowest number allowed. |
| `max` | None | The highest number allowed. |

```yaml
- handle: rating
  type: integer
  min: 1
  max: 5
```

## entries

Links to other entries, saved as a list of their UUIDs.

| Key | Default | What it does |
|---|---|---|
| `collections` | Every collection | The collections the form offers entries from, and accepts. |
| `max_items` | None | The most entries that can be linked. With `1` the form shows a dropdown instead of checkboxes. |

```yaml
- handle: related
  type: entries
  collections: [pages, blog]
  max_items: 3
```

The form lists every entry of those collections, drafts too, with a checkbox each. That suits collections of a few
hundred entries. For more, [change the input](#changing-an-input). A save is refused if it links an entry that doesn't
exist, belongs to another collection, or takes the field over `max_items`.

Read the links back with [`$entry->entries('related')`](query_api.md#entries), which returns entry objects. A link whose
entry is gone is skipped without an error, and saving the form drops it from the file.

A link to an entry in the [trash](admin.md#deleting-and-restoring) survives a save. The form shows it checked and muted,
marked "In trash", and you can untick it. A trashed entry is never offered as a new choice, and its link counts toward
`max_items`. `$entry->entries()` skips it until it's restored, and then the link works again.

!!! note "Upgrading"

    If you copied the `entries` input to your app's views before trashed links were kept, add what's new in
    `fields/entries.php` to your copy, or saving your form drops those links.

## terms

The terms of a [taxonomy](taxonomies.md) an entry has, chosen from the terms that exist.

| Key | Default | What it does |
|---|---|---|
| `taxonomy` | Required | The handle of the taxonomy. |

```yaml
- handle: tags
  type: terms
  taxonomy: tags
```

The input is a checkbox for each term. The front matter holds the checked slugs as a list under the taxonomy's handle,
not under the field's handle, because that is where the reindex looks: `tags: [php, laravel]`. Unchecking every term
saves `tags: []`. A slug in the file that no term has is shown as "(no such term)", checked, so that saving doesn't drop
it. Anything posted that isn't a slug is dropped.

Use one `terms` field for each taxonomy. Don't name a taxonomy `id`, `status`, `published_at`, `blueprint`, `title` or
`body`, which the entry uses itself, or the blueprint is refused.

For the entries to be indexed with their terms, the collection has to list the taxonomy in its `taxonomies`.

## Changing an input

Each type's input is a partial in `src/Content/Views/fields`. To change one, copy it to
`app/Views/content/fields/<type>.php`, for example `app/Views/content/fields/text.php`. Bonfire uses your copy for every
field of that type. The partial gets the variables listed at the top of the file it replaces: `$field`, `$name`, `$id`,
`$value` and `$disabled`. It must name its input `$name`, and give it `$id` so the label points to it.

## Writing your own

A type is a class that extends `Bonfire\Content\Fields\FieldType`, and the built-in types are classes like this too. To
write one, and for the methods a type can override, see [Custom field types](custom_field_types.md).
