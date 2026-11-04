<?php
/**
 * The input of a datetime field. Copy it to app/Views/content/fields/datetime.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value     2026-10-27 09:30:00
 * @var bool                  $disabled
 */
?>
<input type="datetime-local" step="1" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" value="<?= esc(str_replace(' ', 'T', $value), 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
