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
| [`terms`](#terms) | A checkbox for each term | A list of slugs |

A field left empty doesn't add a key to the file, though a key that is already there keeps its empty value.

## text

A single line of text.

## textarea

Several lines of text. `rows` sets its height, and is 6 to start with.

## markdown

A textarea with a **Preview** tab that renders the text through CommonMark. `rows` sets its height, and is 16 to start
with. Give it the handle `body` to edit the entry's body. See [Editing entries](admin.md#the-markdown-preview).

## slug

The entry's slug, which names its file and its URI. It isn't saved in the front matter, and it can't change once the
entry exists, so the form shows it disabled.

On a new entry, the input fills itself from the title as you type, until you type in it. Left empty, the slug is made
from the title on the server. A slug is lowercase letters and numbers with `-` between, up to 100 characters, and the
entry is refused if its file or URI is already taken. `from` names another field to make it from, and is `title` to
start with.

## toggle

A switch, which saves `true` or `false`.

## date

A date, saved as `2026-10-27`. With `time: true` it is a date and time, saved as `2026-10-27 09:30:00`, in your app's
timezone. A value that isn't a date is refused.

## terms

The terms of a [taxonomy](taxonomies.md) an entry has, chosen from the terms that exist. `taxonomy` is required and names
the taxonomy:

```yaml
- handle: tags
  type: terms
  taxonomy: tags
```

The input is a checkbox for each term. The front matter holds the checked slugs as a list under the taxonomy's handle, not
under the field's handle, because that is where the reindex looks: `tags: [php, laravel]`. Unchecking every term saves
`tags: []`. A slug in the file that no term has is shown as "(no such term)", checked, so that saving doesn't drop it.
Anything posted that isn't a slug is dropped. Use one `terms` field for each taxonomy, and don't name a taxonomy `id`,
`status`, `published_at`, `blueprint`, `title` or `body`, which the entry uses itself: the blueprint is refused.

For the entries to be indexed with their terms, the collection has to list the taxonomy in its `taxonomies`.

## Changing an input

Each type's input is a partial in `src/Content/Views/fields`. To change one, copy it to
`app/Views/content/fields/<type>.php`, for example `app/Views/content/fields/text.php`. Bonfire uses your copy for every
field of that type. The partial gets the variables listed at the top of the file it replaces: `$field`, `$name`, `$id`,
`$value` and `$disabled`. It must name its input `$name`, and give it `$id` so the label points to it.

## The contract

A type is a class in `Bonfire\Content\Fields` that extends `FieldType`:

| Method | What it does |
|---|---|
| `partial()` | The name of the input partial. |
| `prepare($input, $field, $submitted)` | Tidies the posted value before it's validated, and returns text. Most inputs post text, but `terms` posts a list. |
| `rules($field)` | The validation rules: the blueprint's `validate` and any the type needs. |
| `toFrontMatter($input, $field)` | The value to write to the file, from a valid value. |
| `fromFrontMatter($stored, $field)` | The value the form shows, from what the file holds. |
| `storedInFrontMatter()` | `false` for a type that isn't a front matter key, like `slug`. |
| `key($field)` | The front matter key that holds the value. The field's handle, except for `terms`. |
| `check($field)` | Throws a `ContentException` if the blueprint gave the field options it can't use. |

The types a blueprint can use are the seven above. Bonfire doesn't load types from your app yet.
