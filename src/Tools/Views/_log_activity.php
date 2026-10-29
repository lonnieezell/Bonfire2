<section class="bf-card bf-log-activity">
    <header class="bf-card-head">
        <div>
            <h2><?= esc($title) ?></h2>
            <p><?= lang('Tools.logActivityErrors', ['<strong>' . number_format($errors) . '</strong>', $errors, count($days)]) ?></p>
        </div>
        <a href="<?= site_url(ADMIN_AREA . '/tools/logs') ?>"><?= lang('Tools.openLogs') ?></a>
    </header>
    <div class="bf-card-body">
        <ol class="bf-log-bars">
            <?php foreach ($days as $day) : ?>
                <li>
                    <?php if ($day['name'] === null) : ?>
                        <div class="bf-log-track"><span class="bf-log-none"></span></div>
                    <?php else : ?>
                        <?php
                        $total   = array_sum($day['counts']);
                        $summary = $day['label'] . ': ' . implode(', ', array_map(
                            static fn (string $level, int $count) => $count . ' ' . lang('Tools.level' . ucfirst($level)),
                            array_keys($day['counts']),
                            $day['counts'],
                        ));
                        ?>
                        <div class="bf-log-track"><a class="bf-log-stack" style="height: <?= round($total / $most * 100, 2) ?>%" href="<?= site_url(ADMIN_AREA . '/tools/view-log/' . $day['name']) ?>" title="<?= esc($summary) ?>" aria-label="<?= esc($summary) ?>"><?php foreach (array_filter($day['counts']) as $level => $count) : ?><span class="bf-log-segment bf-log-<?= $level ?>" style="flex-grow: <?= $count ?>"></span><?php endforeach ?></a></div>
                    <?php endif ?>
                    <span class="bf-log-label"><?= esc($day['label']) ?></span>
                </li>
            <?php endforeach ?>
        </ol>
        <ul class="bf-log-legend">
            <?php foreach ($levels as $level) : ?>
                <li><span class="bf-log-<?= $level ?>"></span><?= lang('Tools.level' . ucfirst($level)) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
</section>
