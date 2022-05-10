<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="/<?= ADMIN_AREA ?>/settings/groups" class="back">&larr; Groups</a>
    <h2>Edit Groups &amp; Permissions</h2>
</x-page-head>

<?= view('Bonfire\Groups\Views\_tabs', ['tab' => 'permissions', 'group' => $group->alias]) ?>

<x-admin-box>
    <form action="<?= current_url() ?>" method="post">
        <?= csrf_field() ?>

        <fieldset>
            <legend><?= esc($group->title) ?></legend>

            <p>Select the permissions this group should have access to.</p>

            <table class="table table-striped">
                <thead>
                <tr>
                    <th style="width: 3rem"></th>
                    <th>Permission</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($permissions as $permission => $description) : ?>
                    <tr>
                        <td>
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $permission ?>"
                                <?php if ($group->can($permission)) : ?>
                                    checked
                                <?php endif ?>
                            >
                        </td>
                        <td><?= $permission ?></td>
                        <td><?= $description ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>

        </fieldset>

        <div class="text-end">
            <input type="submit" class="btn btn-primary" value="Save Permissions">
        </div>

    </form>

</x-admin-box>

<?php $this->endSection() ?>
