<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
            <h2><?= isset($user) ? 'Edit User' : 'New User' ?></h2>
    </x-page-head>

    <?php if(isset($user) && $user->deleted_at !== null) : ?>
        <div class="alert danger">
            This user was deleted on <?= $user->deleted_at->humanize() ?>.
            <a href="#">Restore user?</a>
        </div>
    <?php endif ?>

    <x-admin-box>
        <form action="<?= route_to('user-save') ?>" method="post">
            <?= csrf_field() ?>

            <fieldset>
                <legend>Basic Info</legend>

                <div class="row">
                    <div class="col-12 col-sm-2">
                        <!-- Avatar preview and edit links -->
                    </div>
                    <div class="col">
                        <div class="row">
                            <!-- First Name -->
                            <div class="form-group col">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" autocomplete="first_name"
                                       value="<?= old('first_name', $user->first_name ?? '') ?>">
                                <?php if(has_error('first_name')) : ?>
                                    <p class="text-danger"><?= error('first_name') ?></p>
                                <?php endif ?>
                            </div>
                            <!-- Last Name -->
                            <div class="form-group col">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" autocomplete="last_name"
                                       value="<?= old('last_name', $user->last_name ?? '') ?>">
                                <?php if(has_error('last_name')) : ?>
                                    <p class="text-danger"><?= error('last_name') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email Address -->
                            <div class="form-group col">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="email">@</span>
                                    <input type="text" name="email" class="form-control" autocomplete="email"
                                           value="<?= old('email', $user->email ?? '') ?>">
                                </div>
                                <?php if(has_error('email')) : ?>
                                    <p class="text-danger"><?= error('email') ?></p>
                                <?php endif ?>
                            </div>
                            <div class="col"></div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <hr>

            <fieldset>
                <legend>Password</legend>

                <p>Reset the user's password by filling in the fields below.</p>

                <div class="row">
                    <!-- Password -->
                    <div class="form-group col">
                        <label for="password" class="form-label optional">Password</label>
                        <input type="password" name="password" class="form-control" autocomplete="password">
                        <?php if(has_error('password')) : ?>
                            <p class="text-danger"><?= error('password') ?></p>
                        <?php endif ?>
                    </div>
                    <!-- Password Confirmation-->
                    <div class="form-group col">
                        <label for="pass_confirm" class="form-label optional">Confirm Password</label>
                        <input type="pass_confirm" name="pass_confirm" class="form-control" autocomplete="pass_confirm">
                        <?php if(has_error('pass_confirm')) : ?>
                            <p class="text-danger"><?= error('pass_confirm') ?></p>
                        <?php endif ?>
                    </div>
                </div>
            </fieldset>

            <hr>

            <div class="text-end px-5 py-3">
                <input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
            </div>

        </form>

    </x-admin-box>

    <div class="bg-white p-4 mb-5 border rounded-3 shadow-sm">
asdf
    </div>
<?php $this->endSection() ?>
