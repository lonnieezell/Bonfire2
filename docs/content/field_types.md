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

## Changing an input

Each type's input is a partial in `src/Content/Views/fields`. To change one, copy it to
`app/Views/content/fields/<type>.php`, for example `app/Views/content/fields/text.php`. Bonfire uses your copy for every
field of that type. The partial gets the variables listed at the top of the file it replaces: `$field`, `$name`, `$id`,
`$value`, `$disabled` and `$isNew`. It must name its input `$name`, and give it `$id` so the label points to it.

## The contract

A type is a class in `Bonfire\Content\Fields` that extends `FieldType`:

| Method | What it does |
|---|---|
| `partial()` | The name of the input partial. |
| `prepare($input, $field, $submitted)` | Tidies the posted value before it's validated. Everything posted is text. |
| `rules($field)` | The validation rules: the blueprint's `validate` and any the type needs. |
| `toFrontMatter($input, $field)` | The value to write to the file, from a valid value. |
| `fromFrontMatter($stored, $field)` | The value the form shows, from what the file holds. |
| `storedInFrontMatter()` | `false` for a type that isn't a front matter key, like `slug`. |

The types a blueprint can use are the six above. Bonfire doesn't load types from your app yet.
