<!doctype html>
<html lang="en"><head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <?= asset_link('admin/css/admin.css', 'css') ?>
    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>
    <?= $this->renderSection('styles') ?>
    <?= $viewMeta->render('style') ?>
</head>
<body>

<aside id="alerts-wrapper">
{alerts}
</aside>

<?php if (site_offline()) : ?>
    <div class="alert alert-secondary alert-offline">
    <?= lang('Bonfire.offlineNotice') ?>
        <a href="<?= site_url(ADMIN_AREA .'/settings/general') ?>"><?= lang('Bonfire.here') ?></a>.
    </div>
<?php endif ?>

<div class="main <?= site_offline() ? 'offline' : '' ?>" x-data="{open: (window.innerWidth >= 576)}">
    <div class="h-100 d-flex align-items-stretch">
        <nav id="sidebars" class="sidebar" x-bind:class="{ 'collapsed': ! open }">
            <div class="sidebar-wrap  h-100 position-relative">
                <x-sidebar />

                <div class="nav-item position-absolute bottom-0 w-100">
                    <a href="#" class="nav-link sidebar-toggle" @click="open = !open">
                        <i class="fas fa-angle-double-left"></i>
                        <span><?= lang('Bonfire.collapseSidebar') ?></span>
                    </a>
                </div>
            </div>
        </nav>

        <main class="ms-sm-auto flex-grow-1" style="overflow: auto">
            <?= $this->include('_header') ?>

            <div class="px-md-4 vh-100" style="margin-top: -48px; padding-top: 48px;">
                <?= $this->renderSection('main') ?>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/htmx.org@1.5.0"></script>
<?= asset_link('admin/js/admin.js', 'js') ?>
<?= $this->renderSection('scripts') ?>
<?= $viewMeta->render('script') ?>
<?= $viewMeta->render('rawScripts') ?>
</body></html>
