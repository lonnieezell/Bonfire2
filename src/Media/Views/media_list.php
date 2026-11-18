<?php
/**
 * The media browser: the folders, a search, an upload form for those who can upload, and a grid of the files.
 *
 * @var list<Bonfire\Media\Entities\Media> $files
 * @var CodeIgniter\Pager\Pager            $pager
 * @var list<string>                       $folders
 * @var string|null                        $folder  null for every folder, '' for files in none
 * @var string                             $search
 * @var bool                               $canEdit
 * @var bool                               $canDelete
 * @var string                             $query     this page's query string, which a delete goes back to
 */
$listUrl = url_to('media-list');
?>
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc(lang('Media.modTitle'), 'attr') ?>" count="<?= $pager->getTotal() ?>" />

<?php if ($canEdit) : ?>
    <x-admin-box>
        <form action="<?= url_to('media-upload') ?>" method="post" enctype="multipart/form-data" class="row g-2 align-items-end" data-upload>
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label for="media-files" class="form-label"><?= lang('Media.files') ?></label>
                <input type="file" name="files[]" id="media-files" class="form-control" multiple required>
            </div>
            <div class="col-md-4">
                <label for="media-folder" class="form-label"><?= lang('Media.folder') ?></label>
                <input type="text" name="folder" id="media-folder" class="form-control" list="media-folders" value="<?= esc($folder ?? '', 'attr') ?>" maxlength="255">
                <datalist id="media-folders">
                    <?php foreach ($folders as $name) : ?>
                        <option value="<?= esc($name, 'attr') ?>">
                    <?php endforeach ?>
                </datalist>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-upload" aria-hidden="true"></i> <?= lang('Media.upload') ?></button>
            </div>
        </form>
    </x-admin-box>
<?php endif ?>

<x-admin-box>
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
        <nav class="nav nav-pills" aria-label="<?= esc(lang('Media.folders'), 'attr') ?>">
            <a class="nav-link<?= $folder === null ? ' active' : '' ?>" href="<?= $listUrl ?>"><?= lang('Media.allFolders') ?></a>
            <a class="nav-link<?= $folder === '' ? ' active' : '' ?>" href="<?= $listUrl ?>?folder="><?= lang('Media.noFolder') ?></a>
            <?php foreach ($folders as $name) : ?>
                <a class="nav-link<?= $folder === $name ? ' active' : '' ?>" href="<?= $listUrl ?>?folder=<?= esc(rawurlencode($name), 'attr') ?>" data-folder="<?= esc($name, 'attr') ?>"><i class="fas fa-folder" aria-hidden="true"></i> <?= esc($name) ?></a>
            <?php endforeach ?>
        </nav>

        <form action="<?= $listUrl ?>" method="get" role="search" class="d-flex gap-2">
            <?php if ($folder !== null) : ?>
                <input type="hidden" name="folder" value="<?= esc($folder, 'attr') ?>">
            <?php endif ?>
            <input type="search" name="q" class="form-control" value="<?= esc($search, 'attr') ?>" placeholder="<?= esc(lang('Media.searchPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('Media.search'), 'attr') ?>">
            <button type="submit" class="btn btn-outline-secondary"><?= lang('Media.search') ?></button>
        </form>
    </div>

    <?php if ($files === []) : ?>
        <x-empty-state title="<?= esc(lang('Media.empty'), 'attr') ?>" hint="<?= esc(lang($canEdit ? 'Media.emptyHint' : 'Media.emptyHintReadOnly'), 'attr') ?>" />
    <?php else : ?>
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-3">
            <?php foreach ($files as $file) : ?>
                <div class="col" data-media="<?= esc($file->id, 'attr') ?>">
                    <div class="card h-100">
                        <?php if ($file->isImage()) : ?>
                            <img src="<?= esc($file->url(), 'attr') ?>" alt="<?= esc($file->alt ?? '', 'attr') ?>" class="card-img-top object-fit-cover" style="aspect-ratio: 1" loading="lazy">
                        <?php else : ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-body-tertiary" style="aspect-ratio: 1"><i class="fas fa-file fa-3x bf-muted" aria-hidden="true"></i></div>
                        <?php endif ?>
                        <div class="card-body p-2 small">
                            <?php if ($canEdit) : ?>
                                <a href="<?= url_to('media-edit', $file->id) ?>" class="text-break"><?= esc($file->filename) ?></a>
                            <?php else : ?>
                                <a href="<?= esc($file->url(), 'attr') ?>" class="text-break" target="_blank" rel="noopener"><?= esc($file->filename) ?></a>
                            <?php endif ?>
                        </div>
                        <?php if ($canDelete) : ?>
                            <div class="card-footer p-2 text-end">
                                <?= view('Bonfire\Media\Views\media_delete', ['media' => $file, 'size' => 'btn-sm', 'return' => $query], ['saveData' => false]) ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <div class="bf-list-footer">
            <?= $pager->links('default', 'bonfire_full') ?>
        </div>
    <?php endif ?>
</x-admin-box>
<?php $this->endSection() ?>
