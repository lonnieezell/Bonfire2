<div class="dashboard-cell-container">
    <h2 class="text-start"><?= lang('Bonfire.quickLinks') ?></h2>

    <div class="text-start">
    <?php foreach ($menu->items() as $item) : ?>
        <?php if ($item->userCanSee()): ?>
            <div class="quick-link">
                <a href="<?= $item->url ?>">
                    <?= $item->icon ?>
                    <span><?= $item->title ?></span>
                </a>
            </div>
        <?php endif ?>
    <?php endforeach ?>
    </div>
</div>
