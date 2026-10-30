<?php [$statusTone, $statusLabel] = $user->status() ?>
<x-page-head back="<?= site_url(ADMIN_AREA . '/users') ?>" backLabel="<?= lang('Users.usersModTitle') ?>">
    <div class="bf-user-head">
        <?= $user->renderAvatar(48) ?>
        <div class="bf-user-head-text">
            <div class="bf-user-head-eyebrow"><?= lang('Users.editUser') ?></div>
            <div class="bf-page-head-title">
                <h1><?= esc($user->displayName()) ?></h1>
                <x-status tone="<?= $statusTone ?>" label="<?= esc($statusLabel) ?>" />
            </div>
            <p class="bf-page-head-subtitle"><?= esc($user->email) ?> &middot; <?= lang('Users.lastActive', [$user->last_active?->humanize() ?? lang('Users.never')]) ?></p>
        </div>
        <?php if (auth()->user()->can('users.export')) : ?>
            <div class="bf-page-head-actions ms-auto">
                <a href="<?= url_to('user-export', $user->id) ?>" class="btn btn-outline-secondary" download><i class="fas fa-download" aria-hidden="true"></i> <?= lang('Users.exportData') ?></a>
            </div>
        <?php endif ?>
    </div>
</x-page-head>
