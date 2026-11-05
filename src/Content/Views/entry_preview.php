<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($entry->title) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<x-page-head back="<?= url_to('content-edit', $collection->handle, $entry->id) ?>" backLabel="<?= esc($entry->title, 'attr') ?>" title="<?= esc(lang('Content.previewLinkTitle'), 'attr') ?>" />

<x-admin-box>
    <p><?= lang('Content.previewLinkHint', [esc(app_date($expires, true, true))]) ?></p>
    <div class="input-group">
        <input type="text" class="form-control" value="<?= esc($link, 'attr') ?>" readonly onfocus="this.select()" aria-label="<?= esc(lang('Content.previewLinkTitle'), 'attr') ?>" data-preview-url>
        <a href="<?= esc($link, 'attr') ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer" data-preview-open><?= lang('Content.previewOpen') ?></a>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
