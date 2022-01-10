<!doctype html>
<html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?= config('App')->siteName ?? 'bonfire' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <?= asset_link('auth/css/auth.css', 'css') ?>
    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>
    <?= $this->renderSection('styles') ?>
</head>
<body>

    <aside id="alerts-wrapper">
    {alerts}
    </aside>

    <div class="container">
        <main class="ms-sm-auto px-md-4 py-5">
            <?= $this->renderSection('main') ?>
        </main>
    </div>

    <footer class="border-top text-center p-5">
        <div class="environment">
            <p>Page rendered in {elapsed_time} seconds  &hearts;  Environment: <?= ENVIRONMENT ?></p>
        </div>
    </footer>

</body></html>
