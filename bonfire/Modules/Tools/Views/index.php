<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2>System Info</h2>
    </x-page-head>

    <x-admin-box>

        <fieldset>
            <legend>Server Information</legend>

            <div class="col-12 col-sm-6">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td>PHP Version</td>
                            <td><?= phpversion() ?></td>
                        </tr>
                        <tr>
                            <td>CodeIgniter Version</td>
                            <td><?= $ciVersion ?></td>
                        </tr>
                        <tr>
                            <td>SQL Engine</td>
                            <td><?= $dbDriver .' '. $dbVersion ?></td>
                        </tr>
                        <tr>
                            <td>Server OS</td>
                            <td><?= php_uname('s') ?> <?= php_uname('r') ?> (<?= php_uname('m') ?>)</td>
                        </tr>
                        <tr>
                            <td>Server Load</td>
                            <td><?= number_format($serverLoad, 1) ?></td>
                        </tr>
                        <tr>
                            <td>Max Upload</td>
                            <td><?= (int)(ini_get('upload_max_filesize')) ?>M</td>
                        </tr>
                        <tr>
                            <td>Max POST</td>
                            <td><?= (int)(ini_get('post_max_size')) ?>M</td>
                        </tr>
                        <tr>
                            <td>Memory Limit</td>
                            <td><?= (int)(ini_get('memory_limit')) ?>M</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </fieldset>

        <fieldset>
            <legend>PHP Info</legend>

            <a href="/<?= ADMIN_AREA ?>/tools/php-info" class="btn btn-primary" target="_blank">View PHP Info</a>
        </fieldset>

        <fieldset>
            <legend>Filesystem</legend>

            <div class="col-12 col-sm-6">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td>.env</td>
                            <td>
                                <?php if(is_file(ROOTPATH .'.env')) : ?>
                                    <span class="text-success">present</span>
                                <?php else : ?>
                                    <span class="text-danger">missing</span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td>/writeable</td>
                            <td>
                                <?php if(is_really_writable(WRITEPATH)) : ?>
                                    <span class="text-success">writeable</span>
                                <?php else : ?>
                                    <span class="text-danger">not writeable</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php foreach(directory_map(WRITEPATH, 2) as $folder =>  $info) : ?>
                        <tr>
                            <td>-- <?= trim($folder, ' /') ?></td>
                            <td>
                                <?php if(is_really_writable(WRITEPATH .$folder)) : ?>
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
