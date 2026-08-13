<!doctype html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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

                <div class="px-2 px-md-4" style="margin-top: -48px; padding-top: 48px;">
                    <?= $this->renderSection('main') ?>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/alpinejs@3.16.1/dist/cdn.min.js" integrity="sha384-75oSay1c6HzSZ8dPUa3pKH8KLR9R2yAq9zTvyhTrR3gy1qI9w3AExggq3/RE57uR" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/htmx.org@2.0.10/dist/htmx.min.js" integrity="sha384-H5SrcfygHmAuTDZphMHqBJLc3FhssKjG7w/CeCpFReSfwBWDTKpkzPP8c+cLsK+V" crossorigin="anonymous"></script>
    <?= asset_link('admin/js/admin.js', 'js') ?>
    <?= $this->renderSection('scripts') ?>
    <?= $viewMeta->render('script') ?>
    <?= $viewMeta->render('rawScripts') ?>
</body>

</html>