<?php if (isset($user) && $user !== null) : ?>
<?php $permissionCount = count(array_filter(array_keys(setting('AuthGroups.permissions') ?? []), static fn (string $permission): bool => $user->can($permission))) ?>
<x-tabs>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'details') : ?> active <?php endif ?>" href="<?= $user->adminLink() ?>">
            <?= lang('Users.cardDetails') ?>
        </a>
    </li>
    <?php if (auth()->user()->can('users.edit')) : ?>
        <li class="nav-item">
            <a class="nav-link <?php if ($tab === 'permissions') : ?> active <?php endif ?>" href="<?= $user->adminLink('permissions') ?>">
                <?= lang('Users.cardPermissions') ?>
                <span class="bf-count"><?= $permissionCount ?></span>
            </a>
        </li>
    <?php endif ?>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'security') : ?> active <?php endif ?>" href="<?= $user->adminLink('security') ?>">
            <?= lang('Users.cardSecurity') ?>
        </a>
    </li>
    <?= service('resourceTabs')->renderTabsFor('user') ?>
</x-tabs>
<?php endif ?>
