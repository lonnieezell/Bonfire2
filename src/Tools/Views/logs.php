<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2>Logs</h2>
    </x-page-head>

    <x-admin-box>

    <?php if (count($logs)) { ?>

        <form action="<?= site_url(ADMIN_AREA . '/tools/delete-log'); ?>" method="post">
            <?= csrf_field() ?>

        <div class="table-responsive">
            <table class="table table-hover logs" cellspacing="0" width="100%" >
                <thead>
                    <tr>
                        <th class="column-check"><input class="check-all" type="checkbox" /></th>
                        <th class='date'><?= lang('Logs.date'); ?></th>
                        <th><?= lang('Logs.file'); ?></th>
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
                        <td class="column-check">
                            <input type="checkbox" value="<?= esc($log); ?>" name="checked[]" />
                        </td>
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

        <input type="submit" name="delete" id="delete-me" class="btn btn-sm btn-danger" value="<?= lang('Logs.delete_selected'); ?>" onclick="return confirm('<?= lang('Logs.delete_selected_confirm'); ?>')" />

        <input type="submit" value='<?= lang('Logs.delete_all'); ?>' name="delete_all" class="btn btn-sm btn-danger" onclick="return confirm('<?= lang('Logs.delete_all_confirm'); ?>')" />

    </form>
        <?php } else { ?>
        <div class="text-center">
            <i class="fas fa-clipboard-list fa-3x my-3"></i><br/> <?= lang('Logs.empty'); ?>
        </div>
    <?php } ?>

        </x-admin-box>
    <?php $this->endSection() ?>
