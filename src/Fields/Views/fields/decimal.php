<?php
/**
 * The input of a decimal field. Copy it to app/Views/fields/decimal.php to change it.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name
 * @var string               $id
 * @var string               $value
 * @var bool                 $disabled
 */
$min = Bonfire\Fields\Types\DecimalField::option($field, 'min');
?>
<input type="number" step="<?= esc(Bonfire\Fields\Types\DecimalField::step($field), 'attr') ?>"<?= $min === null ? '' : ' min="' . esc($min, 'attr') . '"' ?> name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" value="<?= esc($value, 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
