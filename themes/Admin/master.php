<!doctype html>
<html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?= config('App')->siteName ?? 'bonfire' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <?= asset_link('admin/css/admin.css', 'css') ?>
    <?= asset_link('other/components/font-awesome/css/all.css', 'css') ?>
    <?= service('alerts')->css(); ?>
    <?= $this->renderSection('styles') ?>
</head>
<body>

<?= service('alerts')->display(); ?>

<div class="main">
	<div class="h-100 d-flex align-items-stretch">
        <nav id="sidebars" class="sidebar">
		    <x-sidebar />
        </nav>

		<main class="ms-sm-auto flex-grow-1" style="overflow: auto">
            <?= $this->include('_header') ?>

            <div class="px-md-4">
                <?= $this->renderSection('main') ?>
            </div>
		</main>
	</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/htmx.org@1.5.0"></script>
<?= $this->renderSection('scripts') ?>
</body></html>
