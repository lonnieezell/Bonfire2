<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="/<?= ADMIN_AREA ?>/users" class="back">&larr; Users</a>
    <h2>Edit User</h2>
</x-page-head>

<?= view('Bonfire\Modules\Users\Views\_tabs', ['tab' => 'permissions', 'user' => $user]) ?>

<x-admin-box>

    <fieldset>
        <legend>User Permissions</legend>

        <p>These permissions are applied in addition to any allowed by the user's groups.</p>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="width: 3rem"></th>
                    <th>Permission</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($permissions as $permission => $description) : ?>
                <tr>
                    <td></td>
                    <td><?= $permission ?></td>
                    <td><?= $description ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </fieldset>

</x-admin-box>

<?php $this->endSection() ?>
