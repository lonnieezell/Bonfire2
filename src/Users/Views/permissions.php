<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($user->displayName()) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?= $this->include('Bonfire\Users\Views\_user_head') ?>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'permissions', 'user' => $user]) ?>

<?php
$byPrefix = [];

foreach ($permissions as $permission => $description) {
    $byPrefix[explode('.', $permission)[0]][$permission] = $description;
}
?>

<form action="<?= current_url() ?>" method="post">
    <?= csrf_field() ?>

    <p class="bf-muted"><?= lang('Users.permsDetail', ['users.manage-admins']) ?></p>

    <?php foreach ($byPrefix as $prefix => $group) : ?>
        <x-admin-box title="<?= esc(ucfirst($prefix)) ?>">
            <?php foreach ($group as $permission => $description) : ?>
                <?php $via = $user->hasPermission($permission) ? null : $user->groupGranting($permission) ?>
                <?php $id = 'perm-' . str_replace('.', '-', $permission) ?>
                <x-setting-row label="<?= esc($permission) ?>" for="<?= $id ?>" description="<?= esc($description) ?>">
                    <?php if ($via !== null) : ?>
                        <span class="bf-locked"><i class="fas fa-lock" aria-hidden="true"></i> <?= lang('Users.viaGroup', [esc($via)]) ?></span>
                    <?php else : ?>
                        <x-switch name="permissions[]" value="<?= esc($permission) ?>" id="<?= $id ?>"
                            checked="<?= $user->hasPermission($permission) ? '1' : '0' ?>"
                            <?= $access->canAssignPermission($user, $permission) ? '' : 'disabled' ?> />
                    <?php endif ?>
                </x-setting-row>
            <?php endforeach ?>
        </x-admin-box>
    <?php endforeach ?>

    <x-save-bar save="<?= lang('Users.savePerms') ?>" />
</form>

<?php $this->endSection() ?>
