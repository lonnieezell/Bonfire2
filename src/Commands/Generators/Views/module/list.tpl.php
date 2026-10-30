<@php $this->extend('master') ?>

<@php $this->section('main') ?>
<x-page-head title="<?= $title ?>" count="<@= $total ?>">
    <@php if (auth()->user()->can('<?= $slug ?>.create')) : ?>
        <a href="<@= url_to('<?= $slug ?>-new') ?>" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> New <?= $singular ?></a>
    <@php endif ?>
</x-page-head>

<x-admin-box>
    <div hx-target="#content-list" hx-include=".bf-toolbar-form">
        <div class="bf-toolbar">
            <form class="bf-toolbar-form" action="<@= current_url() ?>" method="get"
                hx-get="<@= current_url() ?>"
                hx-trigger="input delay:300ms from:[name=search]">
                <div class="bf-toolbar-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="search" class="form-control" value="<@= esc($search) ?>"
                        placeholder="Search <?= $plural ?>" aria-label="Search <?= $plural ?>">
                </div>
            </form>
        </div>

        <div id="content-list">
            <@= $this->include('<?= $namespace ?>\Views\_table') ?>
        </div>
    </div>
</x-admin-box>
<@php $this->endSection() ?>
