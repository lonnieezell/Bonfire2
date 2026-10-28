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
                                <div class="d-inline-flex gap-2">
                                    <a href="<?= url_to('recycler-restore', $currentResource->alias(), $item[$currentResource->primaryKey()]) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-trash-restore" aria-hidden="true"></i> <?= lang('Recycler.restoreMsgTitle') ?>
                                    </a>
                                    <a href="<?= url_to('recycler-purge', $currentResource->alias(), $item[$currentResource->primaryKey()]) ?>" class="btn btn-sm btn-outline-danger"
                                       data-confirm="<?= esc(lang('Recycler.purgeMsgContent')) ?>" data-confirm-button="<?= esc(lang('Recycler.purgeConfirm')) ?>" data-confirm-danger="1">
                                        <?= lang('Recycler.purgeMsgTitle') ?>
                                    </a>
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
