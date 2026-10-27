<?php // Place inside the form that holds the row checkboxes; it shows while any are checked (always, without JavaScript). ?>
<noscript>
    <div class="bf-floating-bar">
        <div class="bf-floating-bar-inner bf-selection-bar"><?= $slot ?? '' ?></div>
    </div>
</noscript>
<div class="bf-floating-bar" x-data="bfSelectionBar" x-show="count > 0" x-cloak
    data-label="<?= esc(lang('Bonfire.selectedCount', ['%s'])) ?>">
    <div class="bf-floating-bar-inner bf-selection-bar">
        <span class="bf-selection-count" x-text="label"></span>
        <?= $slot ?? '' ?>
        <button type="button" class="bf-icon-btn" @click="clear()" aria-label="<?= lang('Bonfire.clearSelection') ?>">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
</div>
