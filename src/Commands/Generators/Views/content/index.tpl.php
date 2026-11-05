<@= $this->extend('master') ?>

<@= $this->section('main') ?>
<h1><?= esc($title) ?></h1>

<@php foreach ($entries as $entry): ?>
    <article>
        <h2><a href="<@= esc((string) $entry->uri) ?>"><@= esc($entry->title) ?></a></h2>
<?php if ($teaser !== null) : ?>
        <p><@= esc($entry->field(<?= $teaser ?>)) ?></p>
<?php endif ?>
    </article>
<@php endforeach ?>

<@= $pager->links() ?>
<@= $this->endSection() ?>
