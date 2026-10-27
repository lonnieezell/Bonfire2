<?php use Bonfire\Tools\Libraries\Logs;

$this->extend('master') ?>

<?php $this->section('main') ?>
<?php $canManage = auth()->user()->can('logs.manage') ?>
<x-page-head title="<?= lang('Tools.logsModTitle') ?>" count="<?= $pager->getTotal() ?>"></x-page-head>

<x-admin-box>
<?php if (count($logs)) : ?>
    <form action="<?= site_url(ADMIN_AREA . '/tools/delete-log') ?>" method="post"
          x-data="{ filter: 'all', confirmAll: false, shows(levels) { return this.filter === 'all' || (this.filter === 'errors' ? /error|critical|alert|emergency/.test(levels) : levels.includes('warning')) } }">
        <?= csrf_field() ?>

        <div class="bf-toolbar">
            <div class="bf-chips">
                <x-chip label="<?= lang('Tools.allFiles') ?>" active="1" @click="filter = 'all'" :aria-pressed="filter === 'all'" :class="{ active: filter === 'all' }" />
                <x-chip label="<?= lang('Tools.withErrors') ?>" @click="filter = 'errors'" :aria-pressed="filter === 'errors'" :class="{ active: filter === 'errors' }" />
                <x-chip label="<?= lang('Tools.withWarnings') ?>" @click="filter = 'warnings'" :aria-pressed="filter === 'warnings'" :class="{ active: filter === 'warnings' }" />
            </div>
            <?php if ($canManage) : ?>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger" x-show="! confirmAll" @click="confirmAll = true">
                        <?= lang('Tools.deleteAll') ?>
                    </button>
                    <span class="d-flex gap-2" x-show="confirmAll" x-cloak>
                        <button type="button" class="btn btn-outline-secondary" @click="confirmAll = false"><?= lang('Tools.cancel') ?></button>
                        <button type="submit" name="delete_all" value="1" class="btn btn-danger"><?= lang('Tools.deleteAllConfirm') ?></button>
                    </span>
                </div>
            <?php endif ?>
        </div>

        <div class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <?php if ($canManage) : ?>
                            <th><input class="select-all form-check-input" type="checkbox" aria-label="<?= lang('Tools.deleteSelected') ?>" /></th>
                        <?php endif ?>
                        <th><?= lang('Tools.date') ?></th>
                        <th><?= lang('Tools.levels') ?></th>
                        <th class="text-end"><?= lang('Tools.entriesCol') ?></th>
                        <th class="d-none d-lg-table-cell"><?= lang('Tools.file') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <?php
                        $date  = str_replace('log-', '', $log['name']);
                        $entries = array_sum($log['counts']);
                        ?>
                        <tr data-levels="<?= esc(strtolower(implode(' ', array_keys($log['counts'])))) ?>" x-show="shows($el.dataset.levels)">
                            <?php if ($canManage) : ?>
                                <td class="bf-select-cell">
                                    <input type="checkbox" class="form-check-input" value="<?= esc($log['name']) ?>" name="checked[]" aria-label="<?= esc($log['name']) ?>" />
                                </td>
                            <?php endif ?>
                            <td>
                                <a href="<?= site_url(ADMIN_AREA . '/tools/view-log/' . $log['name']) ?>" class="bf-log-date"><?= app_date($date) ?></a>
                                <div class="bf-log-weekday"><?= date('l', strtotime($date)) ?></div>
                            </td>
                            <td>
                                <div class="bf-level-bar" aria-hidden="true">
                                    <?php foreach ($log['counts'] as $level => $count) : ?><span class="bf-level-bar-segment bf-level-<?= Logs::levelClass($level) ?>" style="width: <?= $count / $entries * 100 ?>%"></span><?php endforeach ?>
                                </div>
                                <div class="bf-level-tags">
                                    <?php foreach ($log['counts'] as $level => $count) : ?>
                                        <span class="bf-level-tag bf-level-<?= Logs::levelClass($level) ?>"><?= strtolower($level) ?> <?= $count ?></span>
                                    <?php endforeach ?>
                                </div>
                            </td>
                            <td class="text-end"><?= $entries ?></td>
                            <td class="bf-mono bf-muted"><?= esc($log['name'] . '.log') ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="bf-list-footer">
            <?= $pager->links('default', 'bonfire_full') ?>
        </div>

        <?php if ($canManage) : ?>
            <x-selection-bar>
                <button type="submit" name="delete" value="1" class="btn btn-danger" onclick="return confirm('<?= lang('Tools.deleteSelectedConfirm') ?>')">
                    <i class="fas fa-trash" aria-hidden="true"></i> <?= lang('Tools.deleteSelected') ?>
                </button>
            </x-selection-bar>
        <?php endif ?>
    </form>
<?php else : ?>
    <x-empty-state title="<?= lang('Tools.empty') ?>" />
<?php endif ?>
</x-admin-box>
<?php $this->endSection() ?>
