<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($user->displayName()) ?><?php $this->endSection() ?>

<?php $this->section('styles') ?>
  <?= asset_link('auth/css/auth.css', 'css') ?>
<?php $this->endSection() ?>

<?php $this->section('main') ?>
<?= $this->include('Bonfire\Users\Views\_user_head') ?>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'security', 'user' => $user]) ?>

<x-admin-box title="<?= lang('Users.changePass') ?>">
    <?= view('Bonfire\Users\Views\password_change', ['user' => $user ?? null]) ?>
</x-admin-box>

<x-admin-box title="<?= lang('Users.recentLogins') ?>">
    <?php if (isset($logins) && count($logins)) : ?>
        <div class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <th><?= lang('Users.date') ?></th>
                        <th><?= lang('Users.ipAddress') ?></th>
                        <th><?= lang('Users.userAgent') ?></th>
                        <th><?= lang('Users.success') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logins as $login) : ?>
                        <tr>
                            <td><?= app_date($login->date, true, true) ?></td>
                            <td class="bf-mono"><?= esc($login->ip_address ?? '') ?></td>
                            <td class="bf-muted"><?= esc($login->user_agent ?? '') ?></td>
                            <td>
                                <?php if ($login->success) : ?>
                                    <x-status tone="ok" label="<?= lang('Users.successYes') ?>" />
                                <?php else : ?>
                                    <x-status tone="danger" label="<?= lang('Users.successNo') ?>" />
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <x-empty-state title="<?= lang('Users.noRecentLogins') ?>" />
    <?php endif ?>
</x-admin-box>

<?php $this->endSection() ?>

<?= $this->section('scripts') ?>

  <?= asset_link('auth/js/passStrength.js', 'js') ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>

<?= $this->endSection() ?>
