<?php // Place inside a form. With JavaScript it shows once the form changes; without, it always shows. ?>
<div class="bf-floating-bar" x-data="bfSaveBar" x-show="dirty">
    <div class="bf-floating-bar-inner bf-save-bar">
        <span class="bf-dot" aria-hidden="true"></span>
        <span class="bf-save-bar-text"><?= lang('Bonfire.unsavedChanges') ?></span>
        <button type="button" class="btn btn-outline-secondary" x-cloak @click="discard()">
            <?= lang('Bonfire.discard') ?>
        </button>
        <button type="submit" class="btn btn-primary">
            <?= esc($save ?? lang('Bonfire.save')) ?>
        </button>
    </div>
</div>
