<?php
/**
 * The body of a file or image field's picker, which htmx swaps into the field's dialog: the folders, a search, an
 * upload form for those who can upload, and the files to pick. Picking calls the field's Alpine `pick()`. Its links
 * and forms swap into the element that sets their hx-target, the field's picker.
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
$csrfName  = csrf_token();
?>
<?php // An upload rotates the CSRF token, so htmx swaps this over the page's token inputs out of band, or the field's form couldn't be saved. ?>
<input type="hidden" name="<?= esc($csrfName, 'attr') ?>" value="<?= esc(csrf_hash(), 'attr') ?>" hx-swap-oob="<?= esc("outerHTML:input[type=hidden][name=\"{$csrfName}\"]", 'attr') ?>">

<?php if (session('message')) : ?>
    <div class="alert alert-success py-2" role="status"><?= esc(session('message')) ?></div>
<?php endif ?>
<?php if (session('error')) : ?>
    <?php // The upload escaped the error, as it holds the names the files were uploaded with. ?>
    <div class="alert alert-danger py-2" role="alert"><?= session('error') ?></div>
<?php endif ?>

<?php if ($canEdit) : ?>
    <form hx-post="<?= url_to('media-upload') ?>" hx-encoding="multipart/form-data" class="row g-2 align-items-end mb-3" data-picker-upload>
        <?= csrf_field() ?>
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
        <button type="button" class="nav-link<?= $folder === null ? ' active' : '' ?>" hx-get="<?= esc($pickerUrl([]), 'attr') ?>"><?= lang('Media.allFolders') ?></button>
        <button type="button" class="nav-link<?= $folder === '' ? ' active' : '' ?>" hx-get="<?= esc($pickerUrl(['folder' => '']), 'attr') ?>"><?= lang('Media.noFolder') ?></button>
        <?php foreach ($folders as $name) : ?>
            <button type="button" class="nav-link<?= $folder === $name ? ' active' : '' ?>" hx-get="<?= esc($pickerUrl(['folder' => $name]), 'attr') ?>"><i class="fas fa-folder" aria-hidden="true"></i> <?= esc($name) ?></button>
        <?php endforeach ?>
    </nav>

    <form hx-get="<?= url_to('media-picker') ?>" role="search" class="d-flex gap-2">
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

    <div class="mt-2" hx-boost="true" hx-push-url="false">
        <?= $pager->links('default', 'bonfire_full') ?>
    </div>
<?php endif ?>
