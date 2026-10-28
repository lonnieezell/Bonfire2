<!doctype html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>

    <script>
        // Set the color mode before first paint so dark mode doesn't flash light.
        (() => {
            let mode = null;
            try { mode = localStorage.getItem('bf-theme'); } catch (e) {}
            document.documentElement.dataset.bsTheme = mode || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            // Same for the sidebar, which starts collapsed on narrow screens.
            let sidebar = null;
            try { sidebar = localStorage.getItem('bf-sidebar'); } catch (e) {}
            if (sidebar ? sidebar === 'collapsed' : innerWidth < 960) {
                document.documentElement.classList.add('bf-sidebar-collapsed');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <?= asset_link('admin/css/admin.css', 'css') ?>
    <style>:root { --bf-accent: <?= admin_accent() ?>; }</style>
    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>
    <?= $this->renderSection('styles') ?>
    <?= $viewMeta->render('style') ?>
</head>

<body>

    <?php if (site_offline()) : ?>
        <div class="alert alert-secondary alert-offline">
            <?= lang('Bonfire.offlineNotice') ?>
            <a href="<?= site_url(ADMIN_AREA . '/settings/general') ?>"><?= lang('Bonfire.here') ?></a>.
        </div>
    <?php endif ?>

    <div class="bf-app">
        <aside id="sidebar" class="bf-sidebar">
            <x-sidebar />

            <div class="bf-sidebar-foot">
                <button type="button" class="bf-nav-link" onclick="bfSidebar.toggle()" title="<?= lang('Bonfire.collapseSidebar') ?>">
                    <i class="fas fa-angle-double-left bf-collapse-icon"></i>
                    <span class="bf-label"><?= lang('Bonfire.collapseSidebar') ?></span>
                </button>
            </div>
        </aside>

        <div class="bf-main">
            <?= $this->include('_header') ?>

            <main class="bf-content">
                <?= $this->renderSection('main') ?>
            </main>
        </div>
    </div>

    <?= $this->include('_palette') ?>

    <x-confirm-dialog />

    <aside id="alerts-wrapper" class="bf-toasts" aria-live="polite">
        {alerts}
        <x-action-run />
    </aside>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <?php // Before Alpine, so its alpine:init listeners are in place when Alpine starts. ?>
    <?= asset_link('admin/js/admin.js', 'js') ?>
    <script src="https://unpkg.com/alpinejs@3.16.1/dist/cdn.min.js" integrity="sha384-75oSay1c6HzSZ8dPUa3pKH8KLR9R2yAq9zTvyhTrR3gy1qI9w3AExggq3/RE57uR" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/htmx.org@2.0.10/dist/htmx.min.js" integrity="sha384-H5SrcfygHmAuTDZphMHqBJLc3FhssKjG7w/CeCpFReSfwBWDTKpkzPP8c+cLsK+V" crossorigin="anonymous"></script>
    <?= $this->renderSection('scripts') ?>
    <?= $viewMeta->render('script') ?>
    <?= $viewMeta->render('rawScripts') ?>
</body>

</html>