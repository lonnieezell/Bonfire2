<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="/<?= ADMIN_AREA ?>/users" class="back">&larr; Users</a>
    <h2>Edit User</h2>
</x-page-head>

<?= view('Bonfire\Modules\Users\Views\_tabs', ['tab' => 'permissions', 'user' => $user]) ?>

<x-admin-box>
    <form action="<?= current_url() ?>" method="post">
        <?= csrf_field() ?>

        <fieldset>
            <legend>User Permissions</legend>

            <p>These permissions are applied in addition to any allowed by the user's groups.</p>

            <p>Indeterminate checkboxes indicate the permission is already available from one or more groups the user is a part of.</p>

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
                        <td>
                            <input class="form-check-input <?= $user->can($permission) ? 'in-group' : '' ?>" type="checkbox" name="permissions[]" value="<?= $permission ?>"
                                <?php if($user->hasPermission($permission)) : ?>
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

<?php $this->section('scripts') ?>
<script>
    let inputs = document.getElementsByClassName('in-group');
    Array.prototype.forEach.call(inputs, function(el, i){
        el.indeterminate = true;
    });
</script>
<?php $this->endSection() ?>
