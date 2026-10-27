<div class="page-head bf-page-head">
    <div class="bf-page-head-row">
        <div class="bf-page-head-text">
            <div class="bf-page-head-date"><?= esc($today) ?></div>
            <h1><?= esc($greeting) ?></h1>
        </div>
        <div class="bf-page-head-actions">
            <h2 class="visually-hidden"><?= lang('Bonfire.quickLinks') ?></h2>
            <?php foreach ($menu->items() as $item) : ?>
                <?php if ($item->userCanSee()): ?>
                    <a class="btn btn-outline-secondary" href="<?= site_url($item->url) ?>">
                        <?= $item->icon ?>
                        <?= $item->title ?>
                    </a>
                <?php endif ?>
            <?php endforeach ?>
        </div>
    </div>
</div>
