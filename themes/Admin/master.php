<!doctype html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
            <a href="<?= site_url(ADMIN_AREA . '/settings/general') ?>"><?= lang('Bonfire.here') ?></a>.
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

                <div class="px-2 px-md-4 vh-100" style="margin-top: -48px; padding-top: 48px;">
                    <?= $this->renderSection('main') ?>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/alpinejs@3.14.8/dist/cdn.min.js" integrity="sha384-X9kJyAubVxnP0hcA+AMMs21U445qsnqhnUF8EBlEpP3a42Kh/JwWjlv2ZcvGfphb" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/htmx.org@2.0.4" integrity="sha384-HGfztofotfshcF7+8n44JQL2oJmowVChPTg48S+jvZoztPfvwD79OC/LTtG6dMp+" crossorigin="anonymous"></script>
    <?= asset_link('admin/js/admin.js', 'js') ?>
    <?= $this->renderSection('scripts') ?>
    <?= $viewMeta->render('script') ?>
    <?= $viewMeta->render('rawScripts') ?>
</body>

</html>