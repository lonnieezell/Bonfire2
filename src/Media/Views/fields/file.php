<?php
/**
 * The input of a file or image field: the picked file's ID in a hidden input, a preview of the file, and buttons to
 * choose another or clear it. Choose opens a dialog with the Media picker, where the user can also upload. The
 * dialog moves to <body>, so the picker's own forms aren't inside the field's form. Copy it to
 * app/Views/fields/file.php to change it.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name
 * @var string               $id
 * @var string               $value    the ID of the picked file
 * @var bool                 $disabled
 */
helper('media');

$media     = media($value);
$type      = Bonfire\Fields\FieldTypes::get($field->type) instanceof Bonfire\Media\Fields\ImageField ? 'image' : 'file';
$folder    = $field->options['folder'] ?? null;
$pickerUrl = url_to('media-picker') . '?' . http_build_query(['type' => $type] + (is_string($folder) ? ['folder' => $folder] : []));
$missing   = $value !== '' && $media === null;
$state     = [
    'id'    => $value,
    'name'  => $media?->filename ?? '',
    'url'   => $media?->url() ?? '',
    'image' => $media?->isImage() ?? false,
];
?>
<?php // A stored ID with no file to name is a file that is gone. ?>
<div x-data="{ ...<?= esc(json_encode($state, JSON_THROW_ON_ERROR), 'attr') ?>,
               get missing() { return this.id !== '' && this.name === ''; },
               pick(file) { Object.assign(this, { id: file.id, name: file.name, url: file.url, image: file.image === '1' }); this.changed(); document.getElementById('<?= esc($id, 'js') ?>-dialog').close(); },
               clear() { Object.assign(this, { id: '', name: '', url: '', image: false }); this.changed(); },
               changed() { this.$nextTick(() => this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }))); } }"
     class="d-flex flex-wrap align-items-center gap-2">
    <input type="hidden" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" value="<?= esc($value, 'attr') ?>" :value="id" x-ref="input"<?= $disabled ? ' disabled' : '' ?>>

    <?php // The preview is rendered for the stored file, then Alpine keeps it in step with what is picked. ?>
    <div class="d-flex align-items-center gap-2" :hidden="id === '' || missing"<?= $media === null ? ' hidden' : '' ?>>
        <img<?= $state['image'] ? ' src="' . esc($state['url'], 'attr') . '"' : ' hidden' ?> :src="url" :hidden="! image" alt="" class="rounded border object-fit-cover" width="64" height="64">
        <a href="<?= esc($state['url'], 'attr') ?>" :href="url" x-text="name" target="_blank" rel="noopener" class="text-break"><?= esc($state['name']) ?></a>
    </div>
    <span class="text-danger" :hidden="! missing"<?= $missing ? ' data-missing' : ' hidden' ?>>
        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> <?= lang('Media.fileNotFound') ?>
    </span>

    <?php if (! $disabled) : ?>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                hx-get="<?= esc($pickerUrl, 'attr') ?>" hx-target="#<?= esc($id, 'attr') ?>-picker" hx-swap="innerHTML"
                @click="document.getElementById('<?= esc($id, 'js') ?>-dialog').showModal()"><?= lang('Media.choose') ?></button>
        <button type="button" class="btn btn-sm btn-link text-danger" :hidden="id === ''" @click="clear()"<?= $value === '' ? ' hidden' : '' ?>><?= lang('Media.clear') ?></button>

        <template x-teleport="body">
            <dialog id="<?= esc($id, 'attr') ?>-dialog" class="bf-confirm-dialog" style="max-width: 960px" aria-labelledby="<?= esc($id, 'attr') ?>-dialog-title">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 id="<?= esc($id, 'attr') ?>-dialog-title" class="h5 mb-0"><?= esc(lang($type === 'image' ? 'Media.chooseImage' : 'Media.chooseFile')) ?></h2>
                    <button type="button" class="btn-close" aria-label="<?= esc(lang('Media.close'), 'attr') ?>" @click="$el.closest('dialog').close()"></button>
                </div>
                <?php // The picker's links and forms inherit the target, so each of them swaps the picker in here. ?>
                <div id="<?= esc($id, 'attr') ?>-picker" hx-target="this" hx-swap="innerHTML"></div>
            </dialog>
        </template>
    <?php endif ?>
</div>
