<?php
/**
 * The input of a toggle field. Copy it to app/Views/content/fields/toggle.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value     1 for on, 0 for off
 * @var bool                  $disabled
 */
?>
<x-switch name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" label="<?= esc($field->label, 'attr') ?>" checked="<?= $value === '1' ? '1' : '0' ?>" <?= $disabled ? 'disabled' : '' ?> />
