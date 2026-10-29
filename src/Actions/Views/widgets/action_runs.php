<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
    </header>
    <div class="bf-card-body">
        <?php if ($runs === []) : ?>
            <p class="bf-rows-empty"><?= lang('Actions.noActionRuns') ?></p>
        <?php else : ?>
            <ul class="bf-rows">
                <?php foreach ($runs as $run) : ?>
                    <li>
                        <a class="bf-rows-main" href="<?= url_to('action-run', $run['id']) ?>">
                            <span class="bf-rows-text"><?= esc($run['label']) ?> · <?= esc($run['list']) ?></span>
                        </a>
                        <x-status tone="<?= $run['status']->tone() ?>" label="<?= esc($run['status']->label()) ?>" />
                        <span class="bf-rows-meta"><?= $run['processed'] + $run['skipped'] ?> / <?= $run['total'] ?></span>
                        <time class="bf-rows-meta" datetime="<?= $run['created_at']->format('c') ?>"><?= esc($run['created_at']->humanize()) ?></time>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
