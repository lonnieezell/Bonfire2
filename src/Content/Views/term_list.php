<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc($taxonomy->title, 'attr') ?>" count="<?= count($terms) ?>">
    <a href="<?= url_to('content-term-new', $taxonomy->handle) ?>" class="btn btn-primary" data-new><i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Content.newTerm') ?></a>
</x-page-head>

<x-admin-box>
    <?php if ($terms === []) : ?>
        <x-empty-state title="<?= esc(lang('Content.noTerms'), 'attr') ?>" hint="<?= esc(lang('Content.termsEmptyHint'), 'attr') ?>" />
    <?php else : ?>
        <div class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <th><?= lang('Content.colTitle') ?></th>
                        <th><?= lang('Content.colSlug') ?></th>
                        <th><?= lang('Content.colEntries') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $term) : ?>
                        <tr data-term="<?= esc($term->slug, 'attr') ?>">
                            <td data-title><a href="<?= url_to('content-term-edit', $taxonomy->handle, $term->slug) ?>"><?= esc($term->title) ?></a></td>
                            <td class="bf-muted" data-slug><?= esc($term->slug) ?></td>
                            <td class="bf-muted" data-count><?= $term->count ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</x-admin-box>
<?php $this->endSection() ?>
