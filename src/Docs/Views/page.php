<?php $this->extend('master') ?>

<?php $this->section('crumbParent') ?><?= lang('Docs.modTitle') ?><?php $this->endSection() ?>
<?php $this->section('crumb') ?><?= esc($page['title']) ?><?php $this->endSection() ?>

<?php $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/styles/github-dark-dimmed.min.css" rel="stylesheet" integrity="sha384-PiLidnnRuzFgp4qiN8oGNmktrV8ETL+6a8heAxljUX4A+3XWlocwaMn9duBUepfK" crossorigin="anonymous">
<?php $this->endSection() ?>

<?php $this->section('main') ?>
<div class="bf-settings">
    <nav class="bf-settings-nav" aria-label="<?= lang('Docs.modTitle') ?>">
        <?php foreach ($page['nav'] as $link) : ?>
            <?php $current = $link['uri'] === $page['uri'] ?>
            <a class="bf-settings-nav-link<?= $current ? ' active' : '' ?>" href="<?= site_url(rtrim(ADMIN_AREA . '/docs/' . $link['uri'], '/')) ?>"<?= $current ? ' aria-current="page"' : '' ?>>
                <?= esc($link['title']) ?>
            </a>
        <?php endforeach ?>
    </nav>
    <div class="bf-settings-body">
        <div class="bf-card">
            <div class="bf-card-body" id="docs-page">
                <?= $page['html'] ?>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/highlight.min.js" integrity="sha384-RH2xi4eIQ/gjtbs9fUXM68sLSi99C7ZWBRX1vDrVv6GQXRibxXLbwO2NGZB74MbU" crossorigin="anonymous"></script>
<script>hljs.highlightAll();</script>
<?php $this->endSection() ?>
