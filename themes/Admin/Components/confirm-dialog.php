<?php
/**
 * Once per layout: the dialog that asks before anything with a data-confirm
 * attribute (actions included) or an hx-confirm attribute goes ahead, and the
 * form row actions submit.
 */
?>
<form id="bf-action-form" method="post"><?= csrf_field() ?></form>

<dialog class="bf-confirm-dialog" x-data="bfConfirmDialog" aria-labelledby="bf-confirm-title"
    data-type-label="<?= esc(lang('Actions.typeCount', ['%s'])) ?>" data-default-button="<?= esc(lang('Actions.confirm')) ?>">
    <form method="dialog" @submit.prevent="accept()">
        <h2 id="bf-confirm-title" class="h5" x-text="title"></h2>
        <p x-show="body" x-text="body"></p>
        <template x-if="typeCount">
            <div class="mb-3">
                <label class="form-label" for="bf-confirm-count" x-text="typeLabel"></label>
                <input type="text" inputmode="numeric" autocomplete="off" class="form-control" id="bf-confirm-count" x-model="typed">
            </div>
        </template>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="$root.close()"><?= lang('Actions.cancel') ?></button>
            <button type="submit" class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" x-text="button"
                :disabled="typeCount && typed !== String(count)"></button>
        </div>
    </form>
</dialog>
