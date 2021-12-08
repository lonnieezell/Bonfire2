<?= $this->extend('master') ?>

<?= $this->section('main') ?>

    <x-page-head>
        <a href="<?= site_url(ADMIN_AREA . '/guides') ?>" class="back">← All Guides</a>
        <h2><?= $collection->title() ?></h2>
    </x-page-head>

    <x-admin-box>
        <div class="guide toc">
            <h2 class="mb-4">Table of Contents</h2>

            <?= $collection->tableOfContents() ?>
        </div>
    </x-admin-box>

<?= $this->endSection() ?>
