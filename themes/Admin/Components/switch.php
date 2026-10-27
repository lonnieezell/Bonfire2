<?php
// A checkbox styled as a switch. The hidden input posts "0" when it is off (and nothing when disabled).
// Attributes other than name, value, checked and label (id, x-model, …) go on the checkbox.
$isChecked = isset($checked) && ! in_array($checked, ['', '0', 'false'], true);
$extra     = stringify_attributes(array_diff_key($data, array_flip(['name', 'value', 'checked', 'label', 'slot'])));
?>
<span class="bf-switch">
    <input type="hidden" name="<?= esc($name) ?>" value="0"<?= isset($disabled) ? ' disabled' : '' ?>>
    <input type="checkbox" role="switch" class="bf-switch-input" name="<?= esc($name) ?>" value="<?= esc($value ?? '1') ?>"<?php if (isset($label)) : ?> aria-label="<?= esc($label) ?>"<?php endif ?><?= $extra ?><?= $isChecked ? ' checked' : '' ?>>
</span>
