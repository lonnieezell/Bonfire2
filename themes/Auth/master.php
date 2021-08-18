<!doctype html>
<html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?= config('App')->siteName ?? 'bonfire' ?></title>

    <link href="/assets/css/admin.css" rel="stylesheet">
    <?= service('alerts')->css(); ?>
    <?= $this->renderSection('styles') ?>
</head>
<body>

<?= service('alerts')->display(); ?>

<header class="navbar navbar-light flex-md-nowrap p-0 shadow-sm">
    <a class="px-3 d-block fs-3 text-dark text-decoration-none col-md-3 col-lg-2 me-0" href="/<?= ADMIN_AREA ?>">
        <?= config('App')->siteName ?? 'bonfire' ?>
        <span class="fs-6 text-black-50"><?= BONFIRE_VERSION ?></span>
    </a>
</header>

<div class="container-fluid">
    <main class="ms-sm-auto px-md-4">
        <?= $this->renderSection('main') ?>
    </main>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body></html>
