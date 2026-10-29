<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
        <a href="<?= site_url(ADMIN_AREA . '/users') ?>"><?= lang('Users.openUsers') ?></a>
    </header>
    <div class="bf-card-body">
        <?php if ($users === []) : ?>
            <p class="bf-rows-empty"><?= lang('Users.noRecentlyActive') ?></p>
        <?php else : ?>
            <ul class="bf-rows">
                <?php foreach ($users as $user) : ?>
                    <li>
                        <a class="bf-rows-main" href="<?= $user->adminLink() ?>">
                            <?= $user->renderAvatar(28) ?>
                            <span class="bf-rows-text"><?= esc($user->displayName()) ?></span>
                        </a>
                        <time class="bf-rows-meta" datetime="<?= $user->last_active->format('c') ?>"><?= esc($user->last_active->humanize()) ?></time>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
