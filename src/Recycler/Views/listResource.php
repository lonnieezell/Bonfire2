<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= lang('Recycler.recyclerModTitle') ?>" count="<?= $pager->getTotal() ?>">
    <?php if (count($resources) > 1) : ?>
        <select name="r" class="form-select" aria-label="<?= lang('Recycler.resourceType') ?>" x-on:change="sendRecyclerGetRequest($event.target.value)">
            <?php foreach ($resources as $alias => $details) : ?>
                <option value="<?= strtolower((string) $alias) ?>" <?= $currentResource->alias() === (string) $alias ? 'selected' : '' ?>><?= esc($details->label()) ?></option>
            <?php endforeach ?>
        </select>
    <?php endif ?>
</x-page-head>

<x-admin-box>
    <?php if (isset($items) && count($items)) : ?>
        <div id="resource" class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <?php foreach ($currentResource->localizedColumns() as $column) : ?>
                            <th><?= esc(ucfirst(str_replace('_', ' ', $column))) ?></th>
                        <?php endforeach ?>
                        <th class="text-end"><?= lang('Recycler.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <?php foreach ($currentResource->columns() as $column) : ?>
                                <td><?= esc($item[$column] ?? '') ?></td>
                            <?php endforeach ?>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2" x-data="{ confirming: false }">
                                    <a href="<?= url_to('recycler-restore', $currentResource->alias(), $item[$currentResource->primaryKey()]) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-trash-restore" aria-hidden="true"></i> <?= lang('Recycler.restoreMsgTitle') ?>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" x-show="! confirming" x-cloak @click="confirming = true">
                                        <?= lang('Recycler.purgeMsgTitle') ?>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" x-show="confirming" x-cloak @click="confirming = false">
                                        <?= lang('Recycler.cancel') ?>
                                    </button>
                                    <a href="<?= url_to('recycler-purge', $currentResource->alias(), $item[$currentResource->primaryKey()]) ?>" class="btn btn-sm btn-danger" x-show="confirming" x-cloak>
                                        <?= lang('Recycler.purgeConfirm') ?>
                                    </a>
                                    <noscript>
                                        <a href="<?= url_to('recycler-purge', $currentResource->alias(), $item[$currentResource->primaryKey()]) ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('<?= lang('Recycler.purgeMsgContent') ?>');"><?= lang('Recycler.purgeMsgTitle') ?></a>
                                    </noscript>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="bf-list-footer">
            <span class="bf-muted"><?= lang('Recycler.resultLabel', [$pager->getTotal()]) ?></span>
            <?= $pager->links('default', 'bonfire_full') ?>
        </div>
    <?php else : ?>
        <x-empty-state title="<?= lang('Recycler.notFound', [esc($currentResource->alias())]) ?>" />
    <?php endif ?>
</x-admin-box>
<?php $this->endSection() ?>
