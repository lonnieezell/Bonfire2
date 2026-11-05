<?php use Bonfire\Docs\Libraries\MarkdownRenderer;

$this->extend('master') ?>

<?php $this->section('crumbParent') ?><?= lang('Docs.modTitle') ?><?php $this->endSection() ?>
<?php $this->section('crumb') ?><?= esc($page['title']) ?><?php $this->endSection() ?>

<?php $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/styles/github-dark-dimmed.min.css" rel="stylesheet" integrity="sha384-PiLidnnRuzFgp4qiN8oGNmktrV8ETL+6a8heAxljUX4A+3XWlocwaMn9duBUepfK" crossorigin="anonymous">
<style>
    /* Page titles run longer than the settings labels this layout was sized for. */
    .bf-docs .bf-settings-nav-link {
        height: auto;
        min-height: 34px;
        padding-block: 6px;
    }

    @media (min-width: 992px) {
        .bf-docs {
            grid-template-columns: clamp(200px, 25%, 320px) minmax(0, 1fr);
        }
    }

    /* Long-form reading text, where the admin's sizes are set for labels and tables. */
    #docs-page {
        font-size: 15px;
        line-height: 1.6;
    }

    #docs-page :is(p, ul, ol, blockquote) {
        max-width: 60ch;
    }

    #docs-page :is(h1, h2, h3, h4) {
        line-height: 1.25;
    }

    #docs-page h1 {
        font-size: 28px;
    }

    #docs-page h2 {
        margin-top: 32px;
        font-size: 22px;
    }

    #docs-page h3 {
        margin-top: 24px;
        font-size: 18px;
    }

    #docs-page h4 {
        margin-top: 20px;
        font-size: 16px;
    }
</style>
<?php $this->endSection() ?>

<?php $this->section('main') ?>
<div class="bf-settings bf-docs">
    <nav class="bf-settings-nav" aria-label="<?= lang('Docs.modTitle') ?>">
        <?php foreach ($page['nav'] as $link) : ?>
            <?php $current = $link['uri'] === $page['uri'] ?>
            <a class="bf-settings-nav-link<?= $current ? ' active' : '' ?>" href="<?= site_url(MarkdownRenderer::adminPath($link['uri'])) ?>"<?= $current ? ' aria-current="page"' : '' ?>>
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
