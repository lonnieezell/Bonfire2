<!doctype html>
<html lang="<?= service('request')->getLocale() ?>">

<head>
    <?= $viewMeta->render('meta') ?>

    <?= $viewMeta->render('title') ?>
    <!-- hopefully this will be replaced with the data from view
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonfire2 Admin Area for CodeIgniter 4</title>
    -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <?= asset_link('app/css/app.css', 'css') ?>

    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>

    <?= $this->renderSection('styles') ?>

    <?= $viewMeta->render('style') ?>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <?= asset_link('app/js/app.js', 'js') ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>

</html>