<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
        <a href="<?= url_to('recycler') ?>"><?= lang('Recycler.openRecycler') ?></a>
    </header>
    <div class="bf-card-body">
        <?php if ($rows === []) : ?>
            <p class="bf-rows-empty"><?= lang('Recycler.nothingDeleted') ?></p>
        <?php else : ?>
            <ul class="bf-rows">
                <?php foreach ($rows as $row) : ?>
                    <li>
                        <a class="bf-rows-main" href="<?= url_to('recycler') ?>?r=<?= esc($row['alias'], 'url') ?>">
                            <span class="bf-rows-text"><?= esc($row['label']) ?></span>
                        </a>
                        <span class="bf-rows-meta"><?= number_format($row['count']) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
