# Custom field types

When the [built-in field types](field_types.md) don't fit, write one of your own and register it. Blueprints use it
by its handle, and it works in the entry form like any other.

This page makes a `colour` type: a colour picker that saves `#rrggbb`.

## Write the class

Extend `Bonfire\Content\Fields\FieldType` and override what differs from plain text. Only `partial()` is required. Put the
class anywhere your app autoloads, such as `app/Content/ColourField.php`:

```php
<?php

namespace App\Content;

use Bonfire\Content\Field;
use Bonfire\Content\Fields\FieldType;

final class ColourField extends FieldType
{
    public function partial(): string
    {
        return 'colour';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return strtolower(parent::prepare($input, $field, $submitted));
    }

    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'permit_empty', 'regex_match[/^#[0-9a-f]{6}$/]');
    }
}
```

Every method is optional but `partial()`:

| Method | What it does | Default |
|---|---|---|
| `partial()` | The name of the input partial. | Required |
| `prepare($input, $field, $submitted)` | Tidies what was posted, before it's validated. `$submitted` is every posted field. Returns text. | The text, or `''` for anything else |
| `rules($field)` | The validation rules. | The blueprint's `validate` |
| `toFrontMatter($input, $field)` | The value to write to the file. Return `null` or `''` to leave the key out. | The text |
| `fromFrontMatter($stored, $field)` | The text the form shows for what the file holds. | The value as text, or `''` |
| `storedInFrontMatter()` | `false` if the value isn't a front matter key, like `slug`. | `true` |

Things to know:

- **The form value is always text.** A browser posts text, so `prepare()` returns a string, `rules()` validate a string,
  and `toFrontMatter()` turns it into what the file should hold, such as a number or a list. A type that holds several
  values, like a multiple `select`, keeps them as JSON text with `Bonfire\Content\Fields\ChoiceList`.
- **`$field->options`** holds the keys the blueprint gave the field besides `handle`, `type`, `label`, `validate` and
  `instructions`. Use them for settings, like `min` on an `integer`.
- **`withRules()`** adds your rules to the blueprint's `validate`, whether that is a string or a list. A rule can be a
  closure, `function ($value, $data, &$error): bool`, which sets `$error` to its message when it returns `false`. A
  closure turns a string `validate` into a list.
- **A type is created for each use**, so don't keep state in it.

## Write the input

The partial is a view called `<partial>.php` in `app/Views/content/fields/`, which Bonfire looks in first for every
type. Here, `app/Views/content/fields/colour.php`:

```php
<?php
/**
 * @var Bonfire\Content\Field $field
 * @var string                $name      the input's name, such as fields[accent]
 * @var string                $id        the id the label points to
 * @var string                $value
 * @var bool                  $disabled
 */
?>
<input type="color" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control form-control-color" value="<?= esc($value ?: '#000000', 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
```

It has to name its input `$name`, and give it `$id`. A type can also borrow a partial that already exists, such as
`text`, by returning its name from `partial()`. See [Changing an input](field_types.md#changing-an-input).

## Register it

Add the handle and the class to `$fieldTypes` in `app/Config/Content.php`:

```php
<?php

namespace Config;

use App\Content\ColourField;
use Bonfire\Content\Config\Content as BaseContent;

class Content extends BaseContent
{
    public array $fieldTypes = [
        'colour' => ColourField::class,
    ];
}
```

Now a blueprint can use it:

```yaml
- handle: accent
  type: colour
  label: Accent colour
```

A registered handle that a built-in type has replaces it. A class that doesn't exist or doesn't extend `FieldType` is
ignored, and a blueprint that uses its handle is refused as if the type didn't exist.
