<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2>System Info</h2>
    </x-page-head>

    <x-admin-box>

        <fieldset class="first">
            <legend>Server Information</legend>

            <div class="col-12 col-sm-6">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td>PHP Version</td>
                            <td><?= PHP_VERSION ?></td>
                        </tr>
                        <tr>
                            <td>CodeIgniter Version</td>
                            <td><?= $ciVersion ?></td>
                        </tr>
                        <tr>
                            <td>SQL Engine</td>
                            <td><?= $dbDriver . ' ' . $dbVersion ?></td>
                        </tr>
                        <tr>
                            <td>Server OS</td>
                            <td><?= php_uname('s') ?> <?= php_uname('r') ?> (<?= php_uname('m') ?>)</td>
                        </tr>
                        <tr>
                            <td>Server Load</td>
                            <td><?= $serverLoad !== null ? number_format($serverLoad, 1) : 'Unknown' ?></td>
                        </tr>
                        <tr>
                            <td>Max Upload</td>
                            <td><?= (int) (ini_get('upload_max_filesize')) ?>M</td>
                        </tr>
                        <tr>
                            <td>Max POST</td>
                            <td><?= (int) (ini_get('post_max_size')) ?>M</td>
                        </tr>
                        <tr>
                            <td>Memory Limit</td>
                            <td><?= (int) (ini_get('memory_limit')) ?>M</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </fieldset>

        <fieldset>
            <legend>PHP Info</legend>

            <a href="<?= site_url(ADMIN_AREA . '/tools/php-info') ?>" class="btn btn-primary" target="_blank">View PHP Info</a>
        </fieldset>

        <fieldset>
            <legend>Filesystem</legend>

            <div class="col-12 col-md-10 col-xl-6">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td colspan="3"><i class="far fa-file"></i> .env</td>
                            <td>
                                <?php if (is_file(ROOTPATH . '.env')) : ?>
                                    <span class="text-success">present</span>
                                <?php else : ?>
                                    <span class="text-danger">missing</span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3"><i class="far fa-folder"></i> /writeable</td>
                            <td>
                                <?php if (is_really_writable(WRITEPATH)) : ?>
                                    <span class="text-success">writeable</span>
                                <?php else : ?>
                                    <span class="text-danger">not writeable</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php foreach (get_dir_file_info(WRITEPATH, true) as $folder => $info) : ?>
                        <tr>
                            <?php if (is_dir(WRITEPATH . $folder)) : ?>
                                <td colspan="3">
                                    <i class="fas fa-minus"></i>
                                    <i class="far fa-folder"></i>
                                    <?= trim($folder, ' /') ?>
                                </td>
                            <?php else : ?>
                                <td>
                                    <i class="fas fa-minus"></i>
                                    <i class="far fa-file"></i>
                                    <?= trim($folder, ' /') ?></td>
                                <td>
                                    <?= lang('Bonfire.lastModified') ?>: <?= strftime('%c', $info['date']) ?></td>
                                <td>
                                    <?= lang('Bonfire.fileSize') ?>: <?= number_to_size($info['size']) ?>
                                </td>
                            <?php endif ?>
                            <td>
                                <?php if (is_really_writable(WRITEPATH . $folder)) : ?>
                                    <span class="text-success">writeable</span>
                                <?php else : ?>
                                    <span class="text-danger">not writeable</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </fieldset>
    </x-admin-box>
<?php $this->endSection() ?>
