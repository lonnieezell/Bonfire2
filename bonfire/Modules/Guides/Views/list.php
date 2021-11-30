<?= $this->extend('master') ?>

<?= $this->section('main') ?>

    <x-page-head>
        <h2>Guides</h2>
    </x-page-head>

    <x-admin-box>
        <?php if (isset($collections) && count($collections)) : ?>
            <?php foreach($collections as $alias => $info) : ?>
                <h2 class="mb-4">
                    <a href="/<?= ADMIN_AREA ?>/guides/<?= $alias ?>">
                        <?= $info['title'] ?>
                    </a>
                </h2>
            <?php endforeach ?>
        <?php else : ?>
            <div class="alert alert-warning">
                <?= lang('Bonfire.resourceNotFound', ['guides']) ?>
            </div>
        <?php endif ?>
    </x-admin-box>

<?= $this->endSection() ?>
