<@= $this->extend('master') ?>

<?php if (! $controller) : ?>
<@php
// A controller calls content_seo() itself, and $autoSeo adds the tags for a template.
if (! config('Content')->autoSeo) {
    content_seo($entry);
}
?>

<?php endif ?>
<@= $this->section('main') ?>
<article>
    <h1><@= esc($entry->title) ?></h1>
<?php if ($fields !== []) : ?>

    <dl>
<?php foreach ($fields as $field) : ?>
        <dt><?= esc($field['label']) ?></dt>
<?php if ($field['type'] === 'toggle') : ?>
        <dd><@= $entry->field(<?= $field['key'] ?>) ? 'Yes' : 'No' ?></dd>
<?php elseif ($field['type'] === 'entries') : ?>
        <dd>
            <@php foreach ($entry->entries(<?= $field['key'] ?>) as $linked): ?>
                <@php if ($linked->isPublished()): ?>
                    <a href="<@= esc((string) $linked->uri) ?>"><@= esc($linked->title) ?></a>
                <@php endif ?>
            <@php endforeach ?>
        </dd>
<?php elseif ($field['type'] === 'list') : ?>
        <dd>
            <@php foreach ($entry->field(<?= $field['key'] ?>, []) as $row): ?>
                <p><@= esc(implode(', ', array_filter($row, is_scalar(...)))) ?></p>
            <@php endforeach ?>
        </dd>
<?php elseif (in_array($field['type'], ['date', 'datetime', 'integer', 'markdown', 'select', 'text', 'textarea'], true)) : ?>
        <dd><@= esc($entry->field(<?= $field['key'] ?>)) ?></dd>
<?php else : ?>
        <dd><@= esc(implode(', ', (array) $entry->field(<?= $field['key'] ?>, []))) ?></dd>
<?php endif ?>
<?php endforeach ?>
    </dl>
<?php endif ?>
<?php if ($body) : ?>

    <@= $entry->content() ?>
<?php endif ?>
</article>
<@= $this->endSection() ?>
