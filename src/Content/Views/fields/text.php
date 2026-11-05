<?php
/**
 * The input of a text field. Copy it to app/Views/content/fields/text.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name     the input's name, such as fields[title]
 * @var string                $id       the input's id, which the label points to
 * @var string                $value
 * @var bool                  $disabled
 */
?>
<input type="text" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" value="<?= esc($value, 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
