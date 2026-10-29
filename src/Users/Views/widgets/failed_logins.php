<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
    </header>
    <div class="bf-card-body">
        <?php if ($logins === []) : ?>
            <p class="bf-rows-empty"><?= lang('Users.noFailedLogins') ?></p>
        <?php else : ?>
            <ul class="bf-rows">
                <?php foreach ($logins as $login) : ?>
                    <li>
                        <?php if (isset($links[$login->id])) : ?>
                            <a class="bf-rows-main" href="<?= $links[$login->id] ?>">
                                <span class="bf-rows-text"><?= esc($login->identifier) ?></span>
                            </a>
                        <?php else : ?>
                            <span class="bf-rows-main">
                                <span class="bf-rows-text"><?= esc($login->identifier) ?></span>
                            </span>
                        <?php endif ?>
                        <span class="bf-rows-meta"><?= esc($login->ip_address) ?></span>
                        <time class="bf-rows-meta" datetime="<?= $login->date->format('c') ?>"><?= esc($login->date->toLocalizedString($pattern)) ?></time>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
