<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <h2><?= isset($user) ? 'Edit User' : 'New User' ?></h2>
</x-page-head>

<?= view('Bonfire\Modules\Users\Views\_tabs', ['active' => 'security']) ?>

<x-admin-box>

    asdf

</x-admin-box>

<?php $this->endSection() ?>
