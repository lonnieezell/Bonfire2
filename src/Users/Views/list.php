<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <div class="row">
            <div class="col">
                <h2>Users</h2>
            </div>
            <div class="col-auto">
                <a href="<?= route_to('user-new') ?>" class="btn btn-primary">New User</a>
            </div>
        </div>
    </x-page-head>

    <x-admin-box>
        <div x-data="{filtered: false}">
            <x-filter-link />

            <div class="row">
                <!-- List Users -->
                <div class="col" id="user-list">
                    <?= $this->include('Bonfire\Users\Views\_table') ?>
                </div>

                <!-- Filters -->
                <div class="col-auto" x-show="filtered" x-transition.duration.240ms>
                    <?= view_cell('Bonfire\Core\Cells\Filters::renderList', 'model=UserFilter target=#user-list') ?>
                </div>
            </div>
        </div>

    </x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>

</script>
<?php $this->endSection() ?>
