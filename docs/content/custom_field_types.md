# Custom field types

When the [built-in field types](field_types.md) don't fit, write one of your own and register it. Blueprints use it
by its handle, and it works in the entry form like any other.

This page makes a `color` type: a color picker that saves `#rrggbb`.

## Write the class

Extend `Bonfire\Fields\FieldType` and override what differs from plain text. Only `partial()` is required. Put the
class anywhere your app autoloads, such as `app/Content/ColorField.php`:

```php
<?php

namespace App\Content;

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;

final class ColorField extends FieldType
{
    public function partial(): string
    {
        return 'color';
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

Every method is optional but `partial()`. The built-in types are classes like this too:

| Method | What it does | Default |
|---|---|---|
| `partial()` | The name of the input partial. | Required |
| `prepare($input, $field, $submitted)` | Tidies what was posted, before it's validated. `$submitted` is every posted field. Returns text. | The text, or `''` for anything else |
| `rules($field)` | The validation rules. | The blueprint's `validate` |
| `toStored($input, $field)` | The PHP value to store, such as a `bool`, an `int` or a list. In Content it's written to the file, and `null` or `''` leaves the key out. | The text |
| `fromStored($stored, $field)` | The text the form shows for the stored value. | The value as text, or `''` |
| `itemErrors($input, $field)` | The errors inside a value that holds several, by the 1-based position of the item, such as `2.heading`. `blocks` uses it. | No errors |
| `check($field)` | Throws a `Bonfire\Fields\FieldException` if the blueprint gave the field options it can't use, so the blueprint is refused. | Nothing |
| `viewNamespace()` | The namespace of the views that hold the type's partial. | `Bonfire\Fields\Views\fields` |

A type that needs to say where an entry keeps its value also implements `Bonfire\Content\Fields\ContentFieldType`.
Its `key($field)` is the front matter key that holds the value, which `terms` sets to the taxonomy's handle, and
`storedInFrontMatter()` is `false` if the value isn't a front matter key, like `slug`. A type without it keeps its
value under the field's handle.

Things to know:

- **The form value is always text.** A browser posts text, so `prepare()` returns a string, `rules()` validate a string,
  and `toStored()` turns it into what is stored, such as a number or a list. A type that holds several
  values, like a multiple `select`, keeps them as JSON text with `Bonfire\Fields\ChoiceList`.
- **`$field->options`** holds the keys the blueprint gave the field besides `handle`, `type`, `label`, `validate` and
  `instructions`. Use them for settings, like `min` on an `integer`.
- **`withRules()`** adds your rules to the blueprint's `validate`, whether that is a string or a list. A rule can be a
  closure, `function ($value, $data, &$error): bool`, which sets `$error` to its message when it returns `false`. A
  closure turns a string `validate` into a list.
- **A type is created for each use**, so don't keep state in it.

## Write the input

The partial is a view called `<partial>.php` in `app/Views/fields/`, which Bonfire looks in first for every
type. Here, `app/Views/fields/color.php`:

```php
<?php
/**
 * @var Bonfire\Fields\Field $field
 * @var string               $name      the input's name, such as fields[accent]
 * @var string               $id        the id the label points to
 * @var string               $value
 * @var bool                 $disabled
 */
?>
<input type="color" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control form-control-color" value="<?= esc($value ?: '#000000', 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
```

It has to name its input `$name`, and give it `$id`. A type can also borrow a partial that already exists, such as
`text`, by returning its name from `partial()`. See [Changing an input](field_types.md#changing-an-input).

## Register it

Add the handle and the class to `$types` in `app/Config/Fields.php`, which you create if your app doesn't have it yet.
The field system isn't only Content's, so a type registered here works wherever a form uses fields:

```php
<?php

namespace Config;

use App\Content\ColorField;
use Bonfire\Fields\Config\Fields as BaseFields;

class Fields extends BaseFields
{
    public array $types = [
        'color' => ColorField::class,
    ];
}
```

Now a blueprint can use it:

```yaml
- handle: accent
  type: color
  label: Accent color
```

A registered handle that a built-in type has replaces it. A class that doesn't exist or doesn't extend `FieldType` is
ignored, and a blueprint that uses its handle is refused as if the type didn't exist.
