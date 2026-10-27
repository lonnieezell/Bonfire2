<?php
// A checkbox styled as a switch. A hidden input posts `offValue` ("0" by default) when it is off;
// nothing posts when it is disabled or `offValue` is empty (as for an array like `permissions[]`).
// Attributes other than name, value, checked, label and offValue (id, x-model, …) go on the checkbox.
$isChecked = isset($checked) && ! in_array($checked, ['', '0', 'false'], true);
$offValue ??= '0';
$extra     = stringify_attributes(array_diff_key($data, array_flip(['name', 'value', 'checked', 'label', 'offValue', 'slot'])));
?>
<span class="bf-switch">
<?php if ($offValue !== '') : ?>
    <input type="hidden" name="<?= esc($name) ?>" value="<?= esc($offValue) ?>"<?= isset($disabled) ? ' disabled' : '' ?>>
<?php endif ?>
    <input type="checkbox" role="switch" class="bf-switch-input" name="<?= esc($name) ?>" value="<?= esc($value ?? '1') ?>"<?php if (isset($label)) : ?> aria-label="<?= esc($label) ?>"<?php endif ?><?= $extra ?><?= $isChecked ? ' checked' : '' ?>>
</span>
