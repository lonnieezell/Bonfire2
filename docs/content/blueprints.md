# Blueprints

A blueprint lists the fields of an entry's form, and how each one is validated and saved. A collection names the
blueprints its entries can use in its `blueprints` list, and the first is the default. See
[Collections](collections.md#collection-config).

Blueprints live outside the content root, at
`resources/blueprints/collections/<collection>/<blueprint>.yaml`. Change the folder with `$blueprintPath` in
`app/Config/Content.php`.

```yaml
title: Post
fields:
  - handle: title
    type: text
    label: Title
    validate: required|max_length[255]
  - handle: slug
    type: slug
  - handle: tagline
    type: text
    instructions: One line under the title.
  - handle: featured
    type: toggle
  - handle: body
    type: markdown
    validate: required
```

## Blueprint keys

| Key | What it does |
|---|---|
| `title` | The blueprint's name. Defaults to its filename. |
| `fields` | A list of fields, shown in this order. Required. |
| `seo` | Which fields feed an entry's SEO `title` and `description`. See [SEO](seo.md#mapping-fields-in-a-blueprint). |

## Field keys

| Key | Default | What it does |
|---|---|---|
| `handle` | Required | The front matter key the value is saved under. Letters, numbers and `_`, starting with a letter. |
| `type` | Required | One of the [field types](field_types.md). |
| `label` | The handle, as words | The label above the input. |
| `validate` | None | CodeIgniter validation rules, as `required\|max_length[255]` or as a list. |
| `instructions` | None | Help text under the input. |

A type can read more keys, such as `rows` or `time`. They are listed with the type.

A blueprint that can't be loaded, because its YAML is invalid, a field has no `handle`, uses a `type` that doesn't exist
or repeats a handle, is refused with a message that says which. The entry form doesn't open, and the collection's list
still does.

## Fields that aren't ordinary keys

- **`body`.** A field with the handle `body` is the entry's Markdown body, the text after the front matter, not a key.
  Use a `markdown` or `textarea` field for it. Without one the form doesn't show the body and saving keeps it.
- **A `slug` field** is the file's name, not a key. See [Slug](field_types.md#slug).
- **`id`, `status`, `published_at` and `blueprint`** can't be field handles. The form manages them: the id is made once,
  the status and publish date have their own inputs, and `blueprint` is written when the entry doesn't use the
  collection's default.
- **`title`.** A field with the handle `title` becomes the title in the list. Without one, the title is the slug.

An entry keeps the blueprint it was created with, in a `blueprint` key when it isn't the default.

## Validation

The `validate` rules run in CodeIgniter's validator against the posted value, with the field's label in the message. A
type adds its own rules to yours, such as `valid_date` for a `date`. To read more, see
[Validation rules](https://codeigniter.com/user_guide/libraries/validation.html#available-rules).
