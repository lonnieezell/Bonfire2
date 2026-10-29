<section class="bf-card bf-list-card">
    <header class="bf-card-head">
        <h2><?= esc($title) ?></h2>
        <a href="<?= url_to('sys-info') ?>"><?= lang('Tools.openSystemInfo') ?></a>
    </header>
    <div class="bf-card-body">
        <?php if ($problems === []) : ?>
            <p class="bf-rows-empty"><?= lang('Tools.systemHealthy') ?></p>
        <?php else : ?>
            <ul class="bf-rows">
                <?php foreach ($problems as $problem) : ?>
                    <li>
                        <span class="bf-rows-main">
                            <span class="bf-rows-dot bf-log-<?= $problem['problem'] === 'highLoad' ? 'warning' : 'error' ?>" aria-hidden="true"></span>
                            <span class="bf-rows-text"><?= esc(lang('Tools.problem' . ucfirst($problem['problem']), [$problem['subject']])) ?></span>
                        </span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
