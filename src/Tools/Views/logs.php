<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2>Logs</h2>
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
                        <th class='date'><?= lang('Tools.date'); ?></th>
                        <th><?= lang('Tools.file'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ($logs as $log) :
                        // Skip the index.html file.
                        if ($log === 'index.html') {
                            continue;
                        }
                        ?>
                    <tr>
                        <?php if (auth()->user()->can('logs.manage')) : ?>
                            <td class="column-check text-center">
                                <input type="checkbox" value="<?= esc($log); ?>" name="checked[]" />
                            </td>
                        <?php endif ?>
                        <td class='date'>
                            <a href='<?= site_url(ADMIN_AREA . "/tools/view-log/{$log}"); ?>'>
                                <?= date('F j, Y', strtotime(str_replace('.log', '', str_replace('log-', '', $log)))); ?>
                            </a>
                        </td>
                        <td><?= esc($log); ?></td>
                    </tr>
                        <?php
                    endforeach;
        ?>
                </tbody>
            </table>
        </div>

        <?= $pager->links('default', 'bonfire_full') ?>

        <?php if (auth()->user()->can('logs.manage')) : ?>
            <input type="submit" name="delete" id="delete-me" class="btn btn-sm btn-outline-danger"
                value="<?= lang('Tools.deleteSelected'); ?>"
                onclick="return confirm('<?= lang('Tools.deleteSelectedConfirm'); ?>')"
            />

            <input type="submit" value='<?= lang('Tools.deleteAll'); ?>' name="delete_all"
                class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= lang('Tools.deleteAllConfirm'); ?>')"
            />
        <?php endif ?>

    </form>
    <?php else : ?>
        <div class="text-center">
            <i class="fas fa-clipboard-list fa-3x my-3"></i><br/> <?= lang('Tools.empty'); ?>
        </div>
    <?php endif ?>

        </x-admin-box>
    <?php $this->endSection() ?>
