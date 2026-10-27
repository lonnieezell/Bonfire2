<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <x-admin-box title="User Groups" description="Groups work like traditional roles, but can also be more specific groupings of users that might not fit a role-based system.">
        <div class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Alias</th>
                        <th class="text-end">Users</th>
                        <th class="text-end">Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups ?? [] as $alias => $group) : ?>
                        <tr>
                            <td>
                                <div class="bf-group-row-title">
                                    <a href="<?= site_url(ADMIN_AREA . '/settings/groups/' . $alias) ?>"><?= esc($group['title']) ?></a>
                                    <?php if ($alias === $defaultGroup) : ?><span class="bf-pill">Default</span><?php endif ?>
                                </div>
                                <div class="bf-muted small"><?= esc($group['description']) ?></div>
                            </td>
                            <td class="bf-mono"><?= esc($alias) ?></td>
                            <td class="text-end"><?= esc(number_format($group['user_count'])) ?></td>
                            <td class="text-end"><?= $permissionCounts[$alias] ?? 0 ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </x-admin-box>
</x-settings-layout>
<?php $this->endSection() ?>
