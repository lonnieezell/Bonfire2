<?php
// A pill filter: a link when given an href, a radio when given a name (for filter forms), otherwise a toggle button.
// Attributes other than label, count, href, name, value and active (hx-get, …) pass through.
$isActive    = isset($active) && ! in_array($active, ['', '0', 'false'], true);
$passThrough = stringify_attributes(array_diff_key($data, array_flip(['label', 'count', 'href', 'name', 'value', 'active', 'slot'])));
?>
<?php if (isset($href)) : ?>
<a class="bf-chip<?= $isActive ? ' active' : '' ?>" href="<?= esc($href) ?>"<?= $isActive ? ' aria-current="true"' : '' ?><?= $passThrough ?>>
<?php elseif (isset($name)) : ?>
<label class="bf-chip">
    <input type="radio" name="<?= esc($name) ?>" value="<?= esc($value ?? '') ?>"<?= $passThrough ?><?= $isActive ? ' checked' : '' ?>>
<?php else : ?>
<button type="button" class="bf-chip<?= $isActive ? ' active' : '' ?>" aria-pressed="<?= $isActive ? 'true' : 'false' ?>"<?= $passThrough ?>>
<?php endif ?>
    <?= esc($label) ?>
    <?php if (isset($count)) : ?><span class="bf-chip-count"><?= esc($count) ?></span><?php endif ?>
<?= isset($href) ? '</a>' : (isset($name) ? '</label>' : '</button>') ?>
