<!doctype html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <?= asset_link('auth/css/auth.css', 'css') ?>
    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>
    <?= $this->renderSection('styles') ?>
    <?= $viewMeta->render('style') ?>
</head>

<body>

    <aside id="alerts-wrapper">
        {alerts}
    </aside>

    <header class="navbar navbar-light bg-none flex-md-nowrap p-0 shadow-sm">
        <a class="px-3 d-block fs-3 text-dark text-decoration-none col-md-3 col-lg-2 me-0" href="<?= site_url(ADMIN_AREA) ?>">
            <?= setting('Site.siteName') ?? 'Bonfire' ?>
        </a>
    </header>

    <div class="container-fluid main">
        <main class="ms-sm-auto px-md-4">
            <?= $this->renderSection('main') ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/alpinejs@3.16.1/dist/cdn.min.js" integrity="sha384-75oSay1c6HzSZ8dPUa3pKH8KLR9R2yAq9zTvyhTrR3gy1qI9w3AExggq3/RE57uR" crossorigin="anonymous"></script>
    <?= $this->renderSection('scripts') ?>
    <?= $viewMeta->render('script') ?>
</body>

</html>