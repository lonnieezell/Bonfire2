<!doctype html>
<html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?= config('App')->siteName ?? 'bonfire' ?></title>

    <link href="/assets/css/admin.css" rel="stylesheet">
    <?= $this->renderSection('styles') ?>
</head>
<body>

<header class="navbar navbar-light flex-md-nowrap p-0 shadow-sm">
    <a class="px-3 d-block fs-3 text-dark text-decoration-none col-md-3 col-lg-2 me-0" href="/<?= ADMIN_AREA ?>">
        <?= config('App')->siteName ?? 'bonfire' ?>
        <span class="fs-6 text-black-50"><?= BONFIRE_VERSION ?></span>
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <input class="form-control form-control bg-light w-100" type="text" placeholder="Search" aria-label="Search">
    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="#">Sign out</a>
        </div>
    </div>
</header>

<div class="container-fluid">
	<div class="row">
        <nav id="sidebars" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
		    <x-sidebar />
        </nav>

		<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?= $this->renderSection('main') ?>
		</main>
	</div>
</div>


<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body></html>
