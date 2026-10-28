<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <?= view('Bonfire\Widgets\Views\_tabs', ['tab' => $tab]) ?>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?= esc($tab) ?>">

        <x-admin-box title="Log activity widget" description="These are the defaults; admins can override them per widget on their own dashboard.">
            <x-setting-row label="Days to show" description="The last days up to today, from 1 to 31." for="logActivity_fileCount">
                <input type="number" class="form-control" name="logActivity_fileCount" id="logActivity_fileCount" min="1" max="31" required value="<?= esc(setting('LogActivity.logActivity_fileCount')) ?>">
            </x-setting-row>
            <x-setting-row label="Hide Debug entries" for="logActivity_hideDebug">
                <x-switch name="logActivity_hideDebug" value="on" offValue id="logActivity_hideDebug" checked="<?= setting('LogActivity.logActivity_hideDebug') ? '1' : '0' ?>" />
            </x-setting-row>
        </x-admin-box>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
