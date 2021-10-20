<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="/<?= ADMIN_AREA ?>/settings/groups" class="back">&larr; Groups</a>
    <h2>Edit Groups &amp; Permissions</h2>
</x-page-head>

<?= view('Bonfire\Modules\Groups\Views\_tabs', ['tab' => 'basics', 'group' => $groupAlias]) ?>

<x-admin-box>

    <form action="<?= current_url() ?>" method="post">
        <?= csrf_field() ?>

        <fieldset>
            <legend><?= $group['title'] ?></legend>

            <p>Tell us about the group.</p>

            <div class="row">
                <div class="col-12 col-sm-6">

                    <!-- First Name -->
                    <div class="form-group">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" autocomplete="title"
                               value="<?= old('title', $group['title'] ?? '') ?>">
                        <?php if (has_error('title')) : ?>
                            <p class="text-danger"><?= error('title') ?></p>
                        <?php endif ?>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control"><?= old('description', $group['description'] ?? '') ?></textarea>
                        <?php if (has_error('description')) : ?>
                            <p class="text-danger"><?= error('description') ?></p>
                        <?php endif ?>
                    </div>
                </div>
            </div>

        </fieldset>

        <div class="text-end">
            <input type="submit" class="btn btn-primary" value="Save Group">
        </div>

    </form>

</x-admin-box>

<?php $this->endSection() ?>
