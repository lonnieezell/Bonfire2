<?php use Bonfire\Users\User;
$this->extend('master') ?>

<?php $this->section('crumb') ?><?= isset($user) ? esc($user->displayName()) : lang('Users.newUser') ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php if (isset($user)) : ?>
    <?= $this->include('Bonfire\Users\Views\_user_head') ?>
<?php else : ?>
    <x-page-head back="<?= site_url(ADMIN_AREA . '/users') ?>" backLabel="<?= lang('Users.usersModTitle') ?>" title="<?= lang('Users.newUser') ?>" />
<?php endif ?>

<?php if (isset($user) && $user->deleted_at !== null) : ?>
    <div class="alert danger">
        <?= lang('Users.userDeletedOn', [$user->deleted_at->humanize()]) ?>.
        <a href="#"><?= lang('Users.restoreUser') ?>?</a>
    </div>
<?php endif ?>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'details', 'user' => $user ?? null]) ?>

<form action="<?= ($user ?? new User())->adminLink('/save') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php if (isset($user)) : ?>
        <input type="hidden" name="id" value="<?= $user->id ?>">
    <?php endif ?>

    <x-admin-box title="<?= lang('Users.profile') ?>">
        <div class="bf-profile">
            <div id="avatar-place" class="bf-profile-avatar">
                <?= $this->include('\Bonfire\Users\Views\_avatar') ?>
            </div>
            <div class="bf-profile-fields">
                <div class="form-group">
                    <label for="email" class="form-label"><?= lang('Users.email') ?></label>
                    <input type="text" name="email" id="email" class="form-control" autocomplete="email" value="<?= esc(old('email', $user->email ?? '')) ?>">
                    <?php if (has_error('email')) : ?>
                        <p class="text-danger"><?= error('email') ?></p>
                    <?php endif ?>
                </div>
                <div class="form-group">
                    <label for="username" class="form-label"><?= lang('Users.username') ?></label>
                    <input type="text" name="username" id="username" class="form-control" autocomplete="username" value="<?= esc(old('username', $user->username ?? '')) ?>">
                    <?php if (has_error('username')) : ?>
                        <p class="text-danger"><?= error('username') ?></p>
                    <?php endif ?>
                </div>
                <div class="form-group">
                    <label for="first_name" class="form-label"><?= lang('Users.firstName') ?></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" autocomplete="given-name" value="<?= esc(old('first_name', $user->first_name ?? '')) ?>">
                    <?php if (has_error('first_name')) : ?>
                        <p class="text-danger"><?= error('first_name') ?></p>
                    <?php endif ?>
                </div>
                <div class="form-group">
                    <label for="last_name" class="form-label"><?= lang('Users.lastName') ?></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" autocomplete="family-name" value="<?= esc(old('last_name', $user->last_name ?? '')) ?>">
                    <?php if (has_error('last_name')) : ?>
                        <p class="text-danger"><?= error('last_name') ?></p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </x-admin-box>

    <?php if (isset($user)) : ?>
        <x-admin-box title="<?= lang('Users.access') ?>">
            <x-setting-row label="<?= lang('Users.activated') ?>" for="activate">
                <x-switch name="activate" id="activate" checked="<?= $user->isActivated() ? '1' : '0' ?>"
                    <?= $user->isNotActivated() ? '' : 'disabled' ?> />
            </x-setting-row>
            <div x-data="{ isBanned: <?= $user->isBanned() ? 'true' : 'false' ?> }">
                <x-setting-row label="<?= lang('Users.banned') ?>" for="ban">
                    <x-switch name="ban" id="ban" x-model="isBanned" checked="<?= $user->isBanned() ? '1' : '0' ?>"
                        <?= $access->canBan($user) ? '' : 'disabled' ?> />
                </x-setting-row>
                <div class="bf-setting-extra" x-show="isBanned" x-cloak>
                    <label for="ban_reason" class="form-label"><?= lang('Users.enterBanReason') ?></label>
                    <input type="text" name="ban_reason" id="ban_reason" class="form-control" x-bind:disabled="!isBanned"
                        value="<?= esc($user->getBanMessage() ?? '') ?>">
                </div>
            </div>
        </x-admin-box>
    <?php endif ?>

    <?php if ($access->canEditUsers()) : ?>
        <?php $description = lang('Users.selectGroups') . ($access->canManageAdmins() ? '' : '. ' . lang('Users.cannotAddAdminGroups')) ?>
    <?php else : ?>
        <?php $description = lang('Users.groupListDisabled') ?>
    <?php endif ?>
    <x-admin-box title="<?= lang('Users.groups') ?>" description="<?= esc($description) ?>">
        <?php $oldGroups = old('groups', isset($user) ? $user->getGroups() : []) ?>
        <div class="bf-group-cards">
            <?php foreach ($groups as $group => $info) : ?>
                <label class="bf-group-card">
                    <input type="checkbox" name="groups[]" value="<?= esc($group) ?>" class="form-check-input"
                        <?= in_array($group, $oldGroups, true) ? 'checked' : '' ?>
                        <?= $access->canEditUsers() && $access->canAssignGroup($group) ? '' : 'disabled' ?>
                    >
                    <span class="bf-group-card-title"><?= esc($info['title'] ?? $group) ?></span>
                    <span class="bf-group-card-desc"><?= esc($info['description'] ?? '') ?></span>
                    <span class="bf-group-card-meta"><?= lang('Users.groupPermissions', [$permissionCounts[$group] ?? 0]) ?></span>
                </label>
            <?php endforeach ?>
        </div>
    </x-admin-box>

    <?= view_cell('\Bonfire\Users\Libraries\UserCells::metaFormFields') ?>

    <x-save-bar save="<?= lang('Users.saveUser') ?>" />
</form>

<?php $this->endSection() ?>
