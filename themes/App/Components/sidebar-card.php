<div class="card mb-3"
<?php if (isset($alpine_x_data)) : ?>
        x-data="<?= $alpine_x_data ?>"
    <?php endif; ?>
>
    <?php if (isset($title)) : ?>
        <div class="card-header"><?= $title ?></div>
    <?php endif; ?>
    <div class="card-body">
        <?= $slot ?? '' ?>
    </div>
    <?php if (isset($footer)) : ?>
        <div class="card-footer"><?= $footer ?></div>
    <?php endif; ?>
</div>