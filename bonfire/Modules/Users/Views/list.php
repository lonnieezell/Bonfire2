<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <div class="row">
            <div class="col">
                <h2>Users</h2>
            </div>
            <div class="col-auto">
                <a href="<?= route_to('user-new') ?>" class="btn btn-primary">New User</a>
            </div>
        </div>
    </x-page-head>

    <x-admin-box>
        <table class="table table-hover">
            <?= $this->include('_table_head') ?>
            <tbody>
            <?php if (isset($users) && count($users)) : ?>
                <?php foreach($users as $user) : ?>
                <tr>
                    <td>
                        <input type="checkbox" name="selects[]" class="form-check">
                    </td>
                    <td><?= $user->email ?></td>
                    <td><?= $user->username ?></td>
                    <td></td>
                    <td><?= $user->lastLogin()->date->humanize() ?></td>
                    <td>

                    </td>
                </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>

        <div class="text-center">
            <?= $pager->links() ?>
        </div>
    </x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>

</script>
<?php $this->endSection() ?>
