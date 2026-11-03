<?php
/**
 * The input of a date field, which has a time as well with `time: true` in the blueprint.
 * Copy it to app/Views/content/fields/date.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value     2026-10-27, or 2026-10-27 09:30:00 with a time
 * @var bool                  $disabled
 * @var bool                  $isNew
 */
$withTime = Bonfire\Content\Fields\DateField::hasTime($field);
?>
<input type="<?= $withTime ? 'datetime-local' : 'date' ?>"<?= $withTime ? ' step="1"' : '' ?> name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" value="<?= esc(str_replace(' ', 'T', $value), 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
