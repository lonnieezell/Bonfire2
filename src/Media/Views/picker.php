<?php
/**
 * The body of a file or image field's picker, which htmx swaps into the field's dialog: the folders, a search, an
 * upload form for those who can upload, and the files to pick. Picking calls the field's Alpine `pick()`.
 *
 * @var list<Bonfire\Media\Entities\Media> $files
 * @var CodeIgniter\Pager\Pager            $pager
 * @var string                             $type    file or image
 * @var list<string>                       $folders
 * @var string|null                        $folder  null for every folder, '' for files in none
 * @var string                             $search
 * @var bool                               $canEdit
 */
$pickerUrl = static fn (array $query): string => url_to('media-picker') . '?' . http_build_query(['type' => $type] + $query);
$swap      = 'hx-target="closest .bf-media-picker" hx-swap="innerHTML"';
?>
<?php if (session('message')) : ?>
    <div class="alert alert-success py-2" role="status"><?= esc(session('message')) ?></div>
<?php endif ?>
<?php if (session('error')) : ?>
    <?php // The upload escaped the error, as it holds the names the files were uploaded with. ?>
    <div class="alert alert-danger py-2" role="alert"><?= session('error') ?></div>
<?php endif ?>

<?php if ($canEdit) : ?>
    <form hx-post="<?= url_to('media-upload') ?>" hx-encoding="multipart/form-data" <?= $swap ?> class="row g-2 align-items-end mb-3" data-picker-upload>
        <?php // data-csrf: a post rotates the token, so the field copies this one into the page's forms. ?>
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" data-csrf>
        <input type="hidden" name="picker" value="<?= esc($type, 'attr') ?>">
        <div class="col-md-6">
            <input type="file" name="files[]" class="form-control" aria-label="<?= esc(lang('Media.files'), 'attr') ?>"<?= $type === 'image' ? ' accept="image/*"' : '' ?> multiple required>
        </div>
        <div class="col-md-4">
            <input type="text" name="folder" class="form-control" value="<?= esc($folder ?? '', 'attr') ?>" maxlength="255" placeholder="<?= esc(lang('Media.folder'), 'attr') ?>" aria-label="<?= esc(lang('Media.folder'), 'attr') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-upload" aria-hidden="true"></i> <?= lang('Media.upload') ?></button>
        </div>
    </form>
<?php endif ?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <nav class="nav nav-pills small" aria-label="<?= esc(lang('Media.folders'), 'attr') ?>">
        <button type="button" class="nav-link<?= $folder === null ? ' active' : '' ?>" hx-get="<?= esc($pickerUrl([]), 'attr') ?>" <?= $swap ?>><?= lang('Media.allFolders') ?></button>
        <button type="button" class="nav-link<?= $folder === '' ? ' active' : '' ?>" hx-get="<?= esc($pickerUrl(['folder' => '']), 'attr') ?>" <?= $swap ?>><?= lang('Media.noFolder') ?></button>
        <?php foreach ($folders as $name) : ?>
            <button type="button" class="nav-link<?= $folder === $name ? ' active' : '' ?>" hx-get="<?= esc($pickerUrl(['folder' => $name]), 'attr') ?>" <?= $swap ?>><i class="fas fa-folder" aria-hidden="true"></i> <?= esc($name) ?></button>
        <?php endforeach ?>
    </nav>

    <form hx-get="<?= url_to('media-picker') ?>" <?= $swap ?> role="search" class="d-flex gap-2">
        <input type="hidden" name="type" value="<?= esc($type, 'attr') ?>">
        <?php if ($folder !== null) : ?>
            <input type="hidden" name="folder" value="<?= esc($folder, 'attr') ?>">
        <?php endif ?>
        <input type="search" name="q" class="form-control form-control-sm" value="<?= esc($search, 'attr') ?>" placeholder="<?= esc(lang('Media.searchPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('Media.search'), 'attr') ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= lang('Media.search') ?></button>
    </form>
</div>

<?php if ($files === []) : ?>
    <p class="text-muted"><?= lang('Media.empty') ?></p>
<?php else : ?>
    <div class="row row-cols-3 row-cols-md-4 row-cols-lg-6 g-2">
        <?php foreach ($files as $file) : ?>
            <div class="col">
                <button type="button" class="card h-100 w-100 p-0 text-start" data-pick data-id="<?= esc($file->id, 'attr') ?>"
                        data-name="<?= esc($file->filename, 'attr') ?>" data-url="<?= esc($file->url(), 'attr') ?>" data-image="<?= $file->isImage() ? '1' : '' ?>"
                        @click="pick($el.dataset)">
                    <?php if ($file->isImage()) : ?>
                        <img src="<?= esc($file->url(), 'attr') ?>" alt="<?= esc($file->alt ?? '', 'attr') ?>" class="card-img-top object-fit-cover" style="aspect-ratio: 1" loading="lazy">
                    <?php else : ?>
                        <span class="card-img-top d-flex align-items-center justify-content-center bg-body-tertiary" style="aspect-ratio: 1"><i class="fas fa-file fa-2x bf-muted" aria-hidden="true"></i></span>
                    <?php endif ?>
                    <span class="card-body p-1 small text-break"><?= esc($file->filename) ?></span>
                </button>
            </div>
        <?php endforeach ?>
    </div>

    <div class="mt-2" hx-boost="true" hx-push-url="false" <?= $swap ?>>
        <?= $pager->links('default', 'bonfire_full') ?>
    </div>
<?php endif ?>
