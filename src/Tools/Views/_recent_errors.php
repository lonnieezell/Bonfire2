<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
        <a href="<?= site_url(ADMIN_AREA . '/tools/logs') ?>"><?= lang('Tools.openLogs') ?></a>
    </header>
    <div class="bf-card-body">
        <?php if ($entries === []) : ?>
            <p class="bf-rows-empty"><?= lang('Tools.noRecentErrors') ?></p>
        <?php else : ?>
            <ul class="bf-rows">
                <?php foreach ($entries as $entry) : ?>
                    <?php $message = trim($entry['content']) ?>
                    <li>
                        <a class="bf-rows-main" href="<?= site_url(ADMIN_AREA . '/tools/view-log/' . $entry['log']) ?>" title="<?= esc($message, 'attr') ?>">
                            <span role="img" class="bf-rows-dot bf-log-<?= $entry['level'] === 'WARNING' ? 'warning' : 'error' ?>" aria-label="<?= esc($entry['level'], 'attr') ?>"></span>
                            <code class="bf-rows-text"><?= esc($message) ?></code>
                        </a>
                        <time class="bf-rows-meta" datetime="<?= esc(str_replace(' ', 'T', $entry['date']), 'attr') ?>"><?= esc(str_starts_with($entry['date'], $today) ? substr($entry['date'], 11, 5) : \CodeIgniter\I18n\Time::parse($entry['date'])->toLocalizedString($pattern)) ?></time>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
