<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="<?= site_url(ADMIN_AREA . '/users') ?>" class="back">&larr; Users</a>
    <h2><?= isset($user) ? 'Edit User' : 'New User' ?></h2>
</x-page-head>

<?php if (isset($user) && $user->deleted_at !== null) : ?>
    <div class="alert danger">
        This user was deleted on <?= $user->deleted_at->humanize() ?>.
        <a href="#">Restore user?</a>
    </div>
<?php endif ?>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'details', 'user' => $user ?? null]) ?>

<x-admin-box>

    <?php if (isset($user) && $user !== null) : ?>
        <form action="<?= $user->adminLink('/save') ?>" method="post" enctype="multipart/form-data">
        <?php else : ?>
            <form action="<?= (new \Bonfire\Users\User())->adminLink('/save') ?>" method="post" enctype="multipart/form-data">
            <?php endif ?>
            <?= csrf_field() ?>

            <?php if (isset($user) && $user !== null) : ?>
                <input type="hidden" name="id" value="<?= $user->id ?>">
            <?php endif ?>

            <fieldset>
                <legend>Basic Info</legend>

                <div class="row">
                    <div id="avatar-place" class="col-12 col-sm-3 d-flex align-items-top pt-3">
                        <!-- Avatar preview and edit links -->
                        <?= $this->include('\Bonfire\Users\Views\_avatar') ?>
                    </div>
                    <div class="col">
                        <div class="row">
                            <!-- Email Address -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="email">@</span>
                                    <input type="text" name="email" class="form-control" autocomplete="email" value="<?= old('email', $user->email ?? '') ?>">
                                </div>
                                <?php if (has_error('email')) : ?>
                                    <p class="text-danger"><?= error('email') ?></p>
                                <?php endif ?>
                            </div>
                            <!-- Username -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" autocomplete="username" value="<?= old('username', $user->username ?? '') ?>">
                                <?php if (has_error('username')) : ?>
                                    <p class="text-danger"><?= error('username') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- First Name -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" autocomplete="first_name" value="<?= old('first_name', $user->first_name ?? '') ?>">
                                <?php if (has_error('first_name')) : ?>
                                    <p class="text-danger"><?= error('first_name') ?></p>
                                <?php endif ?>
                            </div>
                            <!-- Last Name -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" autocomplete="last_name" value="<?= old('last_name', $user->last_name ?? '') ?>">
                                <?php if (has_error('last_name')) : ?>
                                    <p class="text-danger"><?= error('last_name') ?></p>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

                <fieldset>
                    <legend>Groups</legend>

                    <?php if (auth()->user()->can('users.edit')) : ?>
                        <p>Select one or more groups for the user to belong to.
                        <?php if(! auth()->user()->can('users.manage-admins')) : ?>
                            Groups with administrator privileges cannot be added or removed with your current permissions.
                        <?php endif; ?>
                        </p>
                    <?php else : ?>
                        <p>Groups that the user belongs to (you do not have permission to modify the list).</p>
                    <?php endif; ?>

                    <div class="row">
                        <div class="form-group col-12 col-sm-6">
                            <select name="groups[]" multiple="multiple" class="form-control" <?php if (! auth()->user()->can('users.edit')) echo ' disabled' ?>>
                                <?php foreach ($groups as $group => $info) : ?>
                                    <option value="<?= $group ?>"
                                        <?php if (isset($user) && $user->inGroup($group)) : ?> selected <?php endif ?>
                                        <?php if (
                                            ! auth()->user()->can('users.manage-admins')
                                            && in_array($group, ['admin','superadmin'])
                                            && (isset($user) && ! $user->inGroup($group))
                                            ) : ?> disabled <?php endif ?>
                                    >
                                        <?= $info['title'] ?? $group ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

            <!-- User Meta Fields -->
            <div class="row">
                <?= view_cell('\Bonfire\Users\Libraries\UserCells::metaFormFields') ?>
            </div>

            <div class="text-end py-3">
                <input type="submit" value="Save User" class="btn btn-primary btn-lg">
            </div>

            </form>

</x-admin-box>

<?php $this->endSection() ?>