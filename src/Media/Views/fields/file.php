<?php
/**
 * The input of a file or image field: the picked file's ID in a hidden input, a preview of the file, and buttons to
 * choose another or clear it. Choose opens a dialog with the Media picker, where the user can also upload. The
 * dialog moves to <body>, so the picker's own forms aren't inside the field's form. Choose shows only to those with
 * media.access. Copy it to app/Views/fields/file.php to change it.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name
 * @var string               $id
 * @var string               $value    the ID of the picked file
 * @var bool                 $disabled
 */
$media     = $value === '' ? null : model(Bonfire\Media\Models\MediaModel::class)->find($value);
$type      = Bonfire\Fields\FieldTypes::get($field->type) instanceof Bonfire\Media\Fields\ImageField ? 'image' : 'file';
$folder    = $field->options['folder'] ?? null;
$pickerUrl = url_to('media-picker') . '?' . http_build_query(['type' => $type] + (is_string($folder) ? ['folder' => $folder] : []));
$url       = $media?->url() ?? '';
$canPick   = ! $disabled && auth()->user()?->can('media.access');
?>
<?php // The state is read from the markup, so a copy of a list row or block keeps the file picked in it. A stored ID with no file to name is a file that is gone. ?>
<div x-data="{ id: '', name: '', url: '', image: false,
               init() { const link = this.$el.querySelector('[data-file-link]'); this.id = this.$el.querySelector('[data-file-id]').value; this.name = link.textContent.trim(); this.url = link.getAttribute('href'); this.image = ! this.$el.querySelector('[data-file-thumb]').hidden; },
               get missing() { return this.id !== '' && this.name === ''; },
               open(url) { this.$refs.dialog.showModal(); window.htmx.ajax('GET', url, { target: this.$refs.picker, swap: 'innerHTML' }); },
               pick(file) { Object.assign(this, { id: file.id, name: file.name, url: file.url, image: file.image === '1' }); this.changed(); this.$refs.dialog.close(); },
               clear() { Object.assign(this, { id: '', name: '', url: '', image: false }); this.changed(); },
               changed() { this.$nextTick(() => this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }))); } }"
     class="d-flex flex-wrap align-items-center gap-2">
    <input type="hidden" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" value="<?= esc($value, 'attr') ?>" :value="id" x-ref="input" data-file-id<?= $disabled ? ' disabled' : '' ?>>

    <div class="d-flex align-items-center gap-2" :hidden="id === '' || missing"<?= $media === null ? ' hidden' : '' ?>>
        <img<?= $media?->isImage() ? ' src="' . esc($url, 'attr') . '"' : ' hidden' ?> :src="url" :hidden="! image" alt="" class="rounded border object-fit-cover" width="64" height="64" data-file-thumb>
        <a href="<?= esc($url, 'attr') ?>" :href="url" x-text="name" target="_blank" rel="noopener" class="text-break" data-file-link><?= esc($media?->filename ?? '') ?></a>
    </div>
    <span class="text-danger" :hidden="! missing"<?= $value !== '' && $media === null ? ' data-missing' : ' hidden' ?>>
        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> <?= lang('Media.fileNotFound') ?>
    </span>

    <?php if ($canPick) : ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-picker="<?= esc($pickerUrl, 'attr') ?>" @click="open($el.dataset.picker)"><?= lang('Media.choose') ?></button>
    <?php endif ?>
    <?php if (! $disabled) : ?>
        <button type="button" class="btn btn-sm btn-link text-danger" :hidden="id === ''" @click="clear()"<?= $value === '' ? ' hidden' : '' ?>><?= lang('Media.clear') ?></button>
    <?php endif ?>

    <?php if ($canPick) : ?>
        <?php // No ids in here: a list row's copy rewrites its key in ids, but not inside a template. ?>
        <template x-teleport="body">
            <dialog class="bf-confirm-dialog" style="max-width: 960px" aria-label="<?= esc(lang($type === 'image' ? 'Media.chooseImage' : 'Media.chooseFile'), 'attr') ?>" x-ref="dialog">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0"><?= esc(lang($type === 'image' ? 'Media.chooseImage' : 'Media.chooseFile')) ?></h2>
                    <button type="button" class="btn-close" aria-label="<?= esc(lang('Media.close'), 'attr') ?>" @click="$refs.dialog.close()"></button>
                </div>
                <?php // The picker's links and forms inherit the target, so each of them swaps the picker in here. ?>
                <div hx-target="this" hx-swap="innerHTML" x-ref="picker"></div>
            </dialog>
        </template>
    <?php endif ?>
</div>
