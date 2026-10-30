<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($user->displayName()) ?><?php $this->endSection() ?>

<?php $this->section('styles') ?>
<?php // The password meter's styles, in the admin's colors. The Auth theme's auth.css would restyle <body>. ?>
<style>
    #pass-meter { display: flex; flex-direction: column-reverse; }
    #pass-meter .segment { display: block; width: 30px; height: 9px; background: var(--bf-surface-2); border: 1px solid var(--bf-border-strong); }
    #pass-meter.good .segment { background: var(--bf-ok); border-color: var(--bf-ok); }
    #pass-meter.warn .segment { background: var(--bf-warn); border-color: var(--bf-warn); }
    #pass-meter.bad .segment { background: var(--bf-danger); border-color: var(--bf-danger); }
    #pass-meter.str-1 .segment:nth-child(n+2),
    #pass-meter.str-2 .segment:nth-child(n+3),
    #pass-meter.str-3 .segment:nth-child(4) { background: var(--bf-surface-2); border-color: var(--bf-border-strong); }
    #pass-suggestions { min-height: 2.5rem; margin-bottom: 10px; color: var(--bf-muted); font-size: .8rem; text-align: center; }
    .pass-match-wrap { display: flex; justify-content: center; width: 53px; }
    .pass-match, .pass-not-match { margin: auto; font-size: 20px; font-weight: bold; color: var(--bf-ok); }
    .pass-not-match { color: var(--bf-danger); }
</style>
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
