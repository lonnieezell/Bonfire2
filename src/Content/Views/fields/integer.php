<?php
/**
 * The input of an integer field. Copy it to app/Views/content/fields/integer.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value
 * @var bool                  $disabled
 */
$min = Bonfire\Content\Fields\IntegerField::bound($field, 'min');
$max = Bonfire\Content\Fields\IntegerField::bound($field, 'max');
?>
<input type="number" step="1"<?= $min === null ? '' : ' min="' . $min . '"' ?><?= $max === null ? '' : ' max="' . $max . '"' ?> name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" value="<?= esc($value, 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
