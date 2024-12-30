<div class="card mb-3"
    <?php if (isset($id)) : ?>
    id="<?= $id ?>"
    <?php endif; ?>>
    <div class="card-body">
        <?php if (isset($title)) : ?>
            <h4 class="card-title"><?= $title ?></h4>
        <?php endif; ?>
        <?php if (isset($subtitle)) : ?>
            <h6 class="card-subtitle mb-2 text-body-secondary"><?= $subtitle ?></h6>
        <?php endif; ?>

        <?= $slot ?? '' ?>

        <?php if (isset($footer)) : ?>
            <div class="card-footer"><?= $footer ?></div>
        <?php endif; ?>

    </div>
</div>