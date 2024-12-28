<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="<?= site_url(ADMIN_AREA . '/users') ?>" class="back">
        <i class="fa fa-arrow-left"></i>
        <?= lang('Users.usersModTitle') ?>
    </a>
    <h2><?= lang('Users.editUser') ?></h2>
</x-page-head>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'permissions', 'user' => $user]) ?>

<x-admin-box>
    <form action="<?= current_url() ?>" method="post">
        <?= csrf_field() ?>

        <fieldset class="first">
            <legend><?= lang('Users.perms') ?></legend>
            <p><?= lang('Users.permsDetail', ['users.manage-admins']) ?></p>
            <p><?= lang('Users.permsIndeterminate') ?></p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 3rem"></th>
                            <th><?= lang('Users.permission') ?></th>
                            <th><?= lang('Users.description') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $permission => $description) : ?>
                        <tr>
                            <td>
                                <input
                                    class="form-check-input <?= $user->can($permission) ? 'in-group' : '' ?>"
                                    type="checkbox" name="permissions[]"
                                    value="<?= $permission ?>"
                                    <?php if ($user->hasPermission($permission)) : ?>
                                checked
                                <?php endif ?>
                                <?php if (
                                    ! $user->hasPermission($permission)
                                    && ! auth()->user()->can('users.manage-admins')
                                    && explode('.', $permission)[0] === 'users'
                                ) :
                                    ?>
                                disabled
                                <?php endif ?>
                                >
                            </td>
                            <td><?= $permission ?></td>
                            <td><?= $description ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </fieldset>

        <x-button-container>
            <x-button><?= lang('Users.savePerms') ?></x-button>
        </x-button-container>

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