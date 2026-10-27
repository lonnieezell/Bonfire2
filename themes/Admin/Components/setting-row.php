<div class="bf-setting-row">
    <div class="bf-setting-text">
        <?php if (isset($for)) : ?>
            <label class="bf-setting-label" for="<?= esc($for) ?>"><?= esc($label) ?></label>
        <?php else : ?>
            <div class="bf-setting-label"><?= esc($label) ?></div>
        <?php endif ?>
        <?php if (isset($description)) : ?><div class="bf-setting-desc"><?= esc($description) ?></div><?php endif ?>
    </div>
    <div class="bf-setting-control"><?= $slot ?? '' ?></div>
</div>
