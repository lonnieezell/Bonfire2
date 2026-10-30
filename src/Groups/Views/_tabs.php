<x-page-head back="<?= site_url(ADMIN_AREA . '/settings/groups') ?>" backLabel="<?= esc(lang('Groups.groupsModTitle'), 'attr') ?>" title="<?= esc($title) ?>"></x-page-head>
<x-tabs>
    <li class="nav-item">
        <a class="nav-link <?php if ($tab === 'basics') : ?> active <?php endif ?>"
           href="<?= site_url(ADMIN_AREA . '/settings/groups/' . $group) ?>">
            <?= lang('Groups.groupDetails') ?>
        </a>
    </li>
    <?php if (auth()->user()->can('groups.edit')) : ?>
        <li class="nav-item">
            <a class="nav-link <?php if ($tab === 'permissions') : ?> active <?php endif ?>"
               href="<?= site_url(ADMIN_AREA . '/settings/groups/' . $group . '/permissions') ?>">
                <?= lang('Groups.permissions') ?>
            </a>
        </li>
        <?= service('resourceTabs')->renderTabsFor('group') ?>
    <?php endif ?>
</x-tabs>
