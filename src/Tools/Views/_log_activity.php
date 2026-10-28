<section class="bf-card bf-log-activity">
    <header class="bf-card-head">
        <div>
            <h2><?= esc($title) ?></h2>
            <p><?= lang('Tools.logActivityErrors', ['<strong>' . number_format($errors) . '</strong>', $errors, count($files)]) ?></p>
        </div>
        <a href="<?= site_url(ADMIN_AREA . '/tools/logs') ?>"><?= lang('Tools.openLogs') ?></a>
    </header>
    <div class="bf-card-body">
        <?php if ($files === []) : ?>
            <x-empty-state title="<?= lang('Tools.empty') ?>" />
        <?php else : ?>
            <ol class="bf-log-bars">
                <?php foreach ($files as $file) : ?>
                    <?php
                    $total   = array_sum($file['counts']);
                    $summary = $file['label'] . ': ' . implode(', ', array_map(
                        static fn (string $level, int $count) => $count . ' ' . lang('Tools.level' . ucfirst($level)),
                        array_keys($file['counts']),
                        $file['counts'],
                    ));
                    ?>
                    <li>
                        <div class="bf-log-track"><a class="bf-log-stack" style="height: <?= round($total / $most * 100, 2) ?>%" href="<?= site_url(ADMIN_AREA . '/tools/view-log/' . $file['name']) ?>" title="<?= esc($summary) ?>" aria-label="<?= esc($summary) ?>"><?php foreach (array_filter($file['counts']) as $level => $count) : ?><span class="bf-log-segment bf-log-<?= $level ?>" style="flex-grow: <?= $count ?>"></span><?php endforeach ?></a></div>
                        <span class="bf-log-label"><?= esc($file['label']) ?></span>
                    </li>
                <?php endforeach ?>
            </ol>
            <ul class="bf-log-legend">
                <?php foreach ($levels as $level) : ?>
                    <li><span class="bf-log-<?= $level ?>"></span><?= lang('Tools.level' . ucfirst($level)) ?></li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</section>
