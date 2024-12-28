<?php $this->extend('master') ?>

<?php $this->section('styles') ?>
  <?= asset_link('auth/css/auth.css', 'css') ?>
<?php $this->endSection() ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="<?= site_url(ADMIN_AREA . '/users') ?>" class="back">
        <i class="fa fa-arrow-left"></i>
        <?= lang('Users.usersModTitle') ?>
    </a>
    <h2><?= lang('Users.editUser') ?></h2>
</x-page-head>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'security', 'user' => $user]) ?>

<x-admin-box>

    <fieldset class="first">

        <legend><?= lang('Users.changePass') ?></legend>
        <?= view('Bonfire\Users\Views\password_change', ['user' => $user ?? null]) ?>
  </fieldset>

    <fieldset>
        <legend><?= lang('Users.recentLogins') ?></legend>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= lang('Users.date') ?></th>
                    <th><?= lang('Users.ipAddress') ?></th>
                    <th><?= lang('Users.userAgent') ?></th>
                    <th><?= lang('Users.success') ?></th>
                </tr>
            </thead>
            <?php if (isset($logins) && count($logins)) : ?>
            <tbody>
                <?php foreach ($logins as $login) : ?>
                    <tr>
                        <td><?= app_date($login->date, true, true) ?></td>
                        <td><?= $login->ip_address ?? '' ?></td>
                        <td><?= $login->user_agent ?? '' ?></td>
                        <td>
                            <?php if ($login->success) : ?>
                                <span class="badge rounded-pill bg-success"><?= lang('Users.successYes') ?></span>
                            <?php else : ?>
                                <span class="badge rounded-pill bg-secondary"><?= lang('Users.successNo') ?></span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <?php else : ?>
                <div class="alert alert-secondary"><?= lang('Users.noRecentLogins') ?></div>
            <?php endif ?>
        </table>
    </fieldset>

</x-admin-box>

<?php $this->endSection() ?>

<?= $this->section('scripts') ?>

  <?= asset_link('auth/js/passStrength.js', 'js') ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>

<?= $this->endSection() ?>
