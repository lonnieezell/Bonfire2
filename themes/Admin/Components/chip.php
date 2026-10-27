<?php
// A pill filter. Links when given an href, otherwise a toggle button.
// Attributes other than label, count, href and active (hx-get, …) pass through.
$isActive    = isset($active) && ! in_array($active, ['', '0', 'false'], true);
$passThrough = '';

foreach (array_diff_key($data, array_flip(['label', 'count', 'href', 'active', 'slot'])) as $attribute => $attributeValue) {
    $passThrough .= ' ' . esc($attribute) . '="' . esc($attributeValue) . '"';
}
?>
<?php if (isset($href)) : ?>
<a class="bf-chip<?= $isActive ? ' active' : '' ?>" href="<?= esc($href) ?>"<?= $isActive ? ' aria-current="true"' : '' ?><?= $passThrough ?>>
<?php else : ?>
<button type="button" class="bf-chip<?= $isActive ? ' active' : '' ?>" aria-pressed="<?= $isActive ? 'true' : 'false' ?>"<?= $passThrough ?>>
<?php endif ?>
    <?= esc($label) ?>
    <?php if (isset($count)) : ?><span class="bf-chip-count"><?= esc($count) ?></span><?php endif ?>
<?= isset($href) ? '</a>' : '</button>' ?>
