<!doctype html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <?= asset_link('app/css/app.css', 'css') ?>

    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>

    <?= $this->renderSection('styles') ?>

    <?= $viewMeta->render('style') ?>

    <script src="https://unpkg.com/alpinejs@3.16.1/dist/cdn.min.js" integrity="sha384-75oSay1c6HzSZ8dPUa3pKH8KLR9R2yAq9zTvyhTrR3gy1qI9w3AExggq3/RE57uR" crossorigin="anonymous" defer></script>

</head>

<body>
    <aside id="alerts-wrapper">
        {alerts}
    </aside>

    <?= $this->renderSection('navbar') ?>

    <div class="container-fluid main-content">
        <!-- Main Content -->
        <?= $this->renderSection('main') ?>
    </div>

    <footer class="text-center mt-4">
        <p>&copy; 2020 – <?= date('Y') ?> Lonnie Ezell and contributors. Distributed under the <a href="https://choosealicense.com/licenses/mit/">MIT License</a>
            <br>Page rendered in {elapsed_time} seconds. Environment: <?= ENVIRONMENT ?>
        </p>
    </footer>

    <?= $viewMeta->render('script') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>

</html>