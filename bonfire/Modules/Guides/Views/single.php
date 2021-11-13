<?= $this->extend('master') ?>

<?= $this->section('styles') ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/styles/default.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
<?= $this->endSection() ?>

<?= $this->section('main') ?>

    <x-page-head>
        <a href="<?= site_url(ADMIN_AREA .'/guides') ?>" class="back">← All Guides</a>
        <h2>
            <a href="<?= $collection->link() ?>">
                <?= esc($collection->title()) ?>
            </a>
        </h2>
    </x-page-head>

    <x-admin-box>
        <div class="guide page" style="max-width: 65em;">
            <h2 class="mb-4"></h2>

            <?= $page ?>

            <?= $collection->pageLinks() ?>
        </div>
    </x-admin-box>

<?= $this->endSection() ?>
