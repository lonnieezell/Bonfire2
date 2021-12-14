<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
    <x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
    <h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Modules\Widgets\Views\_tabs', ['tab' => 'basics']) ?>

<x-admin-box>

    <form action="/admin/settings/widgetsReset" method="post">
		<?= csrf_field() ?>
        <fieldset>

            <legend><i class="fas fa-object-group"></i> Widgets Settings</legend>

            <p>In this section you can customize the general parameters of the widgets.</p>

        </fieldset>

        <div class="text-end px-5 py-3">
            <input type="submit" value="Reset All Settings to Default" class="btn btn-danger btn-lg">
        </div>
    </form>
</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
