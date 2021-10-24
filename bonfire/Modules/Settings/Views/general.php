<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <h2>Settings</h2>
</x-page-head>

<x-admin-box>
    <form action="/admin/settings/general" method="post">
        <?= csrf_field() ?>

        <fieldset>
            <legend>General</legend>

            <!-- Site Name -->
            <div class="row">
                <div class="col-12 col-sm-4">
                    <div class="form-check">
                        <label class="form-label" for="siteName">Site Name</label>
                        <input type="text"  class="form-control" name="siteName" value="<?= esc(old('siteName', setting('App.siteName')), 'attr') ?>" />
                    </div>
                </div>
                <div class="col px-5">
                    <p class="text-muted small">Appears in admin, and is available throughout the site.</p>
                </div>
            </div>

            <!-- Site Online? -->
            <div class="row mt-3">
                <div class="col-12 col-sm-4">
                    <div class="form-check py-3 px-5">
                        <input class="form-check-input" type="checkbox" name="siteOnline"
                               value="1"
                            <?php if (old('siteOnline', setting('App.siteOnline') ?? false)) : ?> checked <?php endif ?> />
                        <label class="form-check-label" for="siteOnline">
                            Site Online?
                        </label>
                    </div>
                </div>
                <div class="col px-5 pt-3">
                    <p class="text-muted small">If unchecked, only Superadmin and user groups with permission can access the site.</p>
                </div>
            </div>

        </fieldset>

        <br><hr>

        <div class="text-end px-5 py-3">
            <input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
        </div>
    </form>
</x-admin-box>
<?php $this->endSection() ?>
