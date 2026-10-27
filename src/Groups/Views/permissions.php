<?php use Bonfire\Groups\Libraries\GroupPermissions;

$this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <?= view('Bonfire\Groups\Views\_tabs', ['tab' => 'permissions', 'group' => $group->alias, 'title' => $group->title]) ?>

    <form action="<?= current_url() ?>" method="post">
        <?= csrf_field() ?>

        <p class="bf-muted">Select the permissions this group should have.</p>

        <?php foreach (GroupPermissions::byPrefix($permissions) as $prefix => $set) : ?>
            <x-admin-box title="<?= esc(ucfirst($prefix)) ?>">
                <?php foreach ($set as $permission => $description) : ?>
                    <?php $id = 'perm-' . str_replace('.', '-', $permission) ?>
                    <x-setting-row label="<?= esc($permission) ?>" for="<?= $id ?>" description="<?= esc($description) ?>">
                        <x-switch name="permissions[]" offValue value="<?= esc($permission) ?>" id="<?= $id ?>" checked="<?= $group->can($permission) ? '1' : '0' ?>" />
                    </x-setting-row>
                <?php endforeach ?>
            </x-admin-box>
        <?php endforeach ?>

        <x-save-bar save="Save Permissions" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
