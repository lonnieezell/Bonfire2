<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2><?= lang('Tools.logsModTitle')?></h2>
    </x-page-head>

    <x-admin-box>

    <?php if (count($logs)) : ?>

        <form action="<?= site_url(ADMIN_AREA . '/tools/delete-log'); ?>" method="post">
            <?= csrf_field() ?>

        <div class="table-responsive">
            <table class="table table-hover logs" cellspacing="0" width="100%" >
                <thead>
                    <tr>
                        <?php if (auth()->user()->can('logs.manage')) : ?>
                            <th class="column-check text-center" style="width: 2rem">
                                <input class="select-all" type="checkbox" />
                            </th>
                        <?php endif ?>
                        <th class='date no-wrap'><?= lang('Tools.date'); ?></th>
                        <th><?= lang('Tools.content'); ?></th>
                        <th class="d-none d-lg-table-cell"><?= lang('Tools.file'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ($logs as $log) :
                        ?>
                    <tr>
                        <?php if (auth()->user()->can('logs.manage')) : ?>
                            <td class="column-check text-center">
                                <input type="checkbox" value="<?= esc($log['name']) ?>" name="checked[]" />
                            </td>
                        <?php endif ?>
                        <td class='date no-wrap'>
                            <a href='<?= site_url(ADMIN_AREA . "/tools/view-log/" . $log['name']); ?>'>
                                <?= app_date(str_replace('log-', '', $log['name'])); ?>
                            </a>
                        </td>
                        <td><?= $log['content'] ?></td>
                        <td class="d-none d-lg-table-cell"><?= esc($log['name'] . '.log') ?></td>
                    </tr>
                        <?php
                    endforeach;
        ?>
                </tbody>
            </table>
        </div>

        <?= $pager->links('default', 'bonfire_full') ?>

        <?php if (auth()->user()->can('logs.manage')) : ?>
            <div class="btn-group">
                <x-button name="delete" id="delete-me" color="outline-danger" onclick="return confirm('<?= lang('Tools.deleteSelectedConfirm'); ?>')"><?= lang('Tools.deleteSelected'); ?></x-button>
                <x-button name="delete_all" color="danger" onclick="return confirm('<?= lang('Tools.deleteAllConfirm'); ?>')"><?= lang('Tools.deleteAll'); ?></x-button>
            </div>
        <?php endif ?>
    </form>
    <?php else : ?>
        <div class="text-center">
            <i class="fas fa-clipboard-list fa-3x my-3"></i><br/> <?= lang('Tools.empty'); ?>
        </div>
    <?php endif ?>

        </x-admin-box>
    <?php $this->endSection() ?>
