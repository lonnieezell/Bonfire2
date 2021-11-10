<!doctype html>
<html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?= config('App')->siteName ?? 'bonfire' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <?= asset_link('auth/css/auth.css', 'css') ?>
    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>
    <?= service('alerts')->css(); ?>
    <?= $this->renderSection('styles') ?>
</head>
<body>

<?= service('alerts')->display(); ?>
<header class="navbar navbar-dark bg-dark flex-md-nowrap p-0 shadow-sm">
    <a class="px-3 d-block fs-3 text-light text-decoration-none col-md-3 col-lg-2 me-0" href="/<?= ADMIN_AREA ?>">
				<img class="img-fluid" src="<?= (setting('App.siteLogo'))? asset('logo/thumb_'. setting('App.siteLogo'),'img'):base_url('/uploads/logo/thumb_default.png'); ?>" />
        <?= config('App')->siteName ?? 'bonfire' ?>
        <span class="fs-6 text-white-50"><?= BONFIRE_VERSION ?></span>
    </a>
</header>

<div class="container-fluid main">
    <main class="ms-sm-auto px-md-4">
        <?= $this->renderSection('main') ?>
    </main>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body></html>
