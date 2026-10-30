<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <?= view('Bonfire\Groups\Views\_tabs', ['tab' => 'basics', 'group' => $groupAlias, 'title' => $group['title']]) ?>

    <form action="<?= current_url() ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="<?= esc(lang('Groups.details'), 'attr') ?>">
            <x-setting-row label="<?= esc(lang('Groups.title'), 'attr') ?>" for="title">
                <input type="text" name="title" id="title" class="form-control" autocomplete="off"
                       value="<?= esc(old('title', $group['title'] ?? '')) ?>">
                <?php if (has_error('title')) : ?>
                    <p class="text-danger small mb-0"><?= error('title') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Groups.description'), 'attr') ?>" for="description">
                <textarea name="description" id="description" rows="3" class="form-control"><?= esc(old('description', $group['description'] ?? '')) ?></textarea>
                <?php if (has_error('description')) : ?>
                    <p class="text-danger small mb-0"><?= error('description') ?></p>
                <?php endif ?>
            </x-setting-row>
        </x-admin-box>

        <x-save-bar save="<?= esc(lang('Groups.saveGroup'), 'attr') ?>" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
