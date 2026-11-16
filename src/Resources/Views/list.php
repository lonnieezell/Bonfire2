<?php

/**
 * The list of a resource. A list.php beside the resource's class replaces this view.
 *
 * @var Bonfire\Resources\Resource $resource
 * @var string                     $search
 * @var int                        $total
 * @var bool                       $canCreate whether to offer the new record button
 */

use Bonfire\Resources\Actions\ExportRecords;

$hasSearch  = $resource->search !== [];
$hasFilters = $resource->filters() !== [];
$canExport  = array_filter(service('actions')->for($resource->slug)->bulk(auth()->user()), static fn ($action): bool => $action instanceof ExportRecords) !== [];

$this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc($resource->title(), 'attr') ?>" count="<?= $total ?>">
    <?php if ($canExport) : ?>
        <?php // It sends the search and the chosen filters, the same inputs that htmx sends when the list refreshes. ?>
        <form method="post" action="<?= url_to($resource->slug . '-export') ?>" class="d-inline" data-export-form
            x-data @submit="$el.querySelectorAll('[data-copied]').forEach(input => input.remove());
                document.querySelectorAll('.bf-toolbar-form [name], .filters .filter-check:checked').forEach(input => {
                    const copy = Object.assign(document.createElement('input'), {type: 'hidden', name: input.name, value: input.value});
                    copy.dataset.copied = '';
                    $el.append(copy);
                })">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-file-csv" aria-hidden="true"></i> <?= lang('Resources.exportCsv') ?></button>
        </form>
    <?php endif ?>
    <?php if ($canCreate) : ?>
        <a href="<?= url_to($resource->slug . '-new') ?>" class="btn btn-primary" data-new-record><i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Resources.new') ?></a>
    <?php endif ?>
</x-page-head>

<x-admin-box>
    <div x-data="{filtered: false}" hx-target="#content-list" hx-include=".bf-toolbar-form, .filters .filter-check">
        <?php if ($hasSearch || $hasFilters) : ?>
            <div class="bf-toolbar">
                <?php if ($hasSearch) : ?>
                    <form class="bf-toolbar-form" action="<?= current_url() ?>" method="get"
                        hx-get="<?= current_url() ?>"
                        hx-trigger="input delay:300ms from:[name=search]">
                        <div class="bf-toolbar-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="search" class="form-control" value="<?= esc($search) ?>"
                                placeholder="<?= esc(lang('Resources.search'), 'attr') ?>" aria-label="<?= esc(lang('Resources.search'), 'attr') ?>">
                        </div>
                    </form>
                <?php endif ?>
                <?php if ($hasFilters) : ?>
                    <x-filter-link />
                <?php endif ?>
            </div>
        <?php endif ?>

        <div class="bf-list-with-filters">
            <div id="content-list" data-resource-list="<?= esc($resource->slug, 'attr') ?>">
                <?= $this->include('Bonfire\Resources\Views\_table') ?>
            </div>

            <?php if ($hasFilters) : ?>
                <div class="bf-filter-panel" x-cloak x-show="filtered" x-transition.duration.240ms>
                    <?= view_cell('Bonfire\Core\Cells\Filters::renderList', ['resource' => $resource->slug, 'target' => '#content-list']) ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
