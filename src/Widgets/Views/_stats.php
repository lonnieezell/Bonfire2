<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <?= view('Bonfire\Widgets\Views\_tabs', ['tab' => $tab]) ?>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?= esc($tab) ?>">

        <x-admin-box title="Statistics widgets">
            <x-setting-row label='Show the "View Detail" link' for="stats_showLink">
                <x-switch name="stats_showLink" value="on" offValue id="stats_showLink" checked="<?= setting('Stats.stats_showLink') ? '1' : '0' ?>" />
            </x-setting-row>
        </x-admin-box>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
