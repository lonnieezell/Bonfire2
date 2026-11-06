<?php

/**
 * The list of a resource. A list.php beside the resource's class replaces this view.
 *
 * @var Bonfire\Resources\Resource $resource
 * @var string                     $search
 * @var int                        $total
 * @var bool                       $canCreate whether to offer the new record button
 */
$hasSearch  = $resource->search !== [];
$hasFilters = $resource->filters() !== [];

$this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc($resource->title(), 'attr') ?>" count="<?= $total ?>">
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
