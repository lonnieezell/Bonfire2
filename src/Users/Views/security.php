<?php $this->extend('master') ?>

<?php $this->section('styles') ?>
  <?= asset_link('auth/css/auth.css', 'css') ?>
<?php $this->endSection() ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="/<?= ADMIN_AREA ?>/users" class="back">&larr; Users</a>
    <h2>Edit User</h2>
</x-page-head>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'security', 'user' => $user]) ?>

<x-admin-box>

    <fieldset>

        <legend>Change password</legend>
        <?= view('Bonfire\Users\Views\password_change', ['user' => $user ?? null]) ?>
  </fieldset>

    <fieldset>
        <legend>Recent Logins</legend>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Success?</th>
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
                                <span class="badge rounded-pill bg-success">Success</span>
                            <?php else : ?>
                                <span class="badge rounded-pill bg-secondary">Failed</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <?php else : ?>
                <div class="alert alert-secondary">No recent login attempts.</div>
            <?php endif ?>
        </table>
    </fieldset>

</x-admin-box>

<?php $this->endSection() ?>

<?= $this->section('scripts') ?>

  <?= asset_link('auth/js/passStrength.js', 'js') ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>

<?= $this->endSection() ?>
