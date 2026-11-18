<?php
/**
 * The input of a file or image field: the ID of the picked media file.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name
 * @var string               $id
 * @var string               $value    the ID of the picked file
 * @var bool                 $disabled
 */
?>
<input type="hidden" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" value="<?= esc($value, 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
