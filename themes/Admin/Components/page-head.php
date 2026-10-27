<div class="page-head bf-page-head">
<?php if (isset($back)) : ?>
    <a class="bf-back" href="<?= esc($back) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> <?= esc($backLabel ?? lang('Bonfire.back')) ?></a>
<?php endif ?>
<?php if (isset($title)) : ?>
    <div class="bf-page-head-row">
        <div class="bf-page-head-text">
            <div class="bf-page-head-title">
                <h1><?= esc($title) ?></h1>
                <?php if (isset($count)) : ?><span class="bf-count"><?= esc($count) ?></span><?php endif ?>
            </div>
            <?php if (isset($subtitle)) : ?><p class="bf-page-head-subtitle"><?= esc($subtitle) ?></p><?php endif ?>
        </div>
        <div class="bf-page-head-actions"><?= $slot ?? '' ?></div>
    </div>
<?php else : ?>
    <?= $slot ?? '' ?>
<?php endif ?>
</div>
