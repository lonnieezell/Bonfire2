<?php use Bonfire\Tools\Libraries\Logs;

$this->extend('master') ?>

<?php $this->section('crumbParent') ?><?= lang('Tools.logsModTitle') ?><?php $this->endSection() ?>
<?php $this->section('crumb') ?><?= esc($logFilePretty) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<x-page-head back="<?= site_url(ADMIN_AREA . '/tools/logs') ?>" backLabel="<?= lang('Tools.logsModTitle') ?>"
    title="<?= lang('Tools.log') ?>: <?= esc($logFilePretty) ?>"
    subtitle="<?= esc($logFile) ?>.log · <?= lang('Tools.entries', [$entryCount]) ?>">
    <a href="<?= url_to('log-download', $logFile) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-download" aria-hidden="true"></i> <?= lang('Tools.download') ?>
    </a>
    <?php if ($canDelete) : ?>
        <form action="<?= site_url(ADMIN_AREA . '/tools/delete-log') ?>" method="post" class="d-flex gap-2" x-data="{ confirming: false }">
            <?= csrf_field() ?>
            <input type="hidden" name="checked[]" value="<?= esc($logFile) ?>"/>
            <button type="button" class="btn btn-outline-danger" x-show="! confirming" @click="confirming = true">
                <i class="fas fa-trash" aria-hidden="true"></i> <?= lang('Tools.deleteFile') ?>
            </button>
            <span class="d-flex gap-2" x-show="confirming" x-cloak>
                <button type="button" class="btn btn-outline-secondary" @click="confirming = false"><?= lang('Tools.cancel') ?></button>
                <button type="submit" name="delete" value="1" class="btn btn-danger"><?= lang('Tools.confirmDelete') ?></button>
            </span>
        </form>
    <?php endif ?>
</x-page-head>

<x-admin-box>
    <div x-data="{
            level: 'all',
            search: '',
            groups: { errors: ['error', 'critical', 'alert', 'emergency'], warnings: ['warning'], info: ['info', 'notice'], debug: ['debug'] },
            shows(el) {
                return (this.level === 'all' || this.groups[this.level].includes(el.dataset.level))
                    && el.querySelector('.bf-log-text').textContent.toLowerCase().includes(this.search.toLowerCase());
            },
        }">
        <div class="bf-toolbar">
            <div @change="level = $event.target.value">
                <x-segmented name="level" label="<?= lang('Tools.level') ?>" value="all"
                    options="<?= esc(json_encode(['all' => lang('Tools.allLevels'), 'errors' => 'Errors', 'warnings' => 'Warnings', 'info' => 'Info', 'debug' => 'Debug'])) ?>" />
            </div>
            <div class="bf-toolbar-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" class="form-control" x-model="search" placeholder="<?= lang('Tools.searchMessages') ?>" aria-label="<?= lang('Tools.searchMessages') ?>">
            </div>
            <div class="ms-auto"><?= $filesPager ?? '' ?></div>
        </div>

        <div class="bf-log-entries">
            <?php foreach ($logContent as $log) : ?>
                <div class="bf-log-entry" data-level="<?= strtolower($log['level']) ?>" x-show="shows($el)">
                    <span class="bf-level-tag bf-level-<?= Logs::levelClass($log['level']) ?>"><?= strtolower($log['level']) ?></span>
                    <span class="bf-log-time bf-mono"><?= app_date($log['date'], true) ?></span>
                    <div class="bf-log-message">
                        <span class="bf-log-text"><?= esc($log['content']) ?></span>
                        <?php if (array_key_exists('extra', $log)) : ?>
                            <details class="bf-log-trace">
                                <summary>Stack trace</summary>
                                <pre><?= esc(trim($log['extra'], " \n")) ?></pre>
                            </details>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <div class="bf-list-footer">
            <?= $pager->links('default', 'bonfire_full') ?>
        </div>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
