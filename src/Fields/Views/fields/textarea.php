<?php
/**
 * The input of a textarea field. Copy it to app/Views/fields/textarea.php to change it.
 *
 * @var Bonfire\Fields\Field $field    `rows` in the blueprint sets its height
 * @var string               $name
 * @var string               $id
 * @var string               $value
 * @var bool                 $disabled
 */
$rows = is_int($field->options['rows'] ?? null) ? $field->options['rows'] : 6;
?>
<?php // A browser drops the line break right after <textarea>, so an extra one keeps a value that starts with its own. ?>
<textarea name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" rows="<?= $rows ?>"<?= $disabled ? ' disabled' : '' ?>>
<?= esc($value) ?></textarea>
