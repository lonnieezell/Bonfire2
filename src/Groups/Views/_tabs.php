<x-tabs>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'basics') : ?> active <?php endif ?>"
           href="<?= isset($group) ? site_url(ADMIN_AREA . '/settings/groups/' . $group) : '#' ?>">
            Group Details
        </a>
    </li>
    <?php if (auth()->user()->can('groups.edit')) : ?>
        <li class="nav-item">
            <a class="nav-link <?php if ($tab === 'permissions') : ?> active <?php endif ?>"
               href="<?= site_url(ADMIN_AREA . '/settings/groups/' . $group . '/permissions') ?>">
                Permissions
            </a>
        </li>
        <?= service('resourceTabs')->renderTabsFor('group') ?>
    <?php endif ?>
</x-tabs>
