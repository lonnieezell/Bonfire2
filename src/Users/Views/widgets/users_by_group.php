<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
        <a href="<?= site_url(ADMIN_AREA . '/users') ?>"><?= lang('Users.openUsers') ?></a>
    </header>
    <div class="bf-card-body">
        <ul class="bf-bars">
            <?php foreach ($rows as $row) : ?>
                <li>
                    <span class="bf-bars-label"><?= esc($row['title']) ?></span>
                    <span class="bf-bars-track"><span class="bf-bars-fill" style="width: <?= round($row['count'] / $most * 100, 2) ?>%"></span></span>
                    <span class="bf-bars-value"><?= number_format($row['count']) ?></span>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
</section>
