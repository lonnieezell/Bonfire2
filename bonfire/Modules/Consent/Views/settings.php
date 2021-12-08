<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <x-module-title><i class="fas fa-handshake"></i> Consent</x-module-title>
        <h2>Settings</h2>
    </x-page-head>

    <x-admin-box>
        <form action="/admin/settings/consent" method="post">
            <?= csrf_field() ?>

            <fieldset>
                <legend>Basic Details</legend>

                <p>The Consent module asks users to provide consent before you set various cookies.</p>

                <div class="form-check form-switch mt-6  mb-3">
                    <input class="form-check-input" type="checkbox" name="requireConsent" role="switch" id="requireConsent"
                        <?php if(setting('Consent.requireConsent')) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="requireConsent">Require Consent form visitors?</label>
                </div>

                <!-- Consent Length -->
                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="consentLength">Days consent is valid</label>
                        <div class="row">
                            <div class="col-3">
                                <input type="number" min="0" step="1" class="form-control col-2" name="consentLength"
                                       value="<?= old('consentLength', setting('Consent.consentLength')) ?>">
                            </div>
                        </div>
                        <?php if (has_error('consentLength')) : ?>
                            <p class="text-danger"><?= error('consentLength') ?></p>
                        <?php endif ?>
                    </div>
                    <div class="col">
                        <p class="text-muted small pt-3">After days are up, will ask the user for consent again.</p>
                    </div>
                </div>

                <!-- Policy URL -->
                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="policyUrl">URL to the Cookie Policy</label>
                        <input type="text" class="form-control" name="policyUrl" value="<?= old('policyUrl', setting('Consent.policyUrl')) ?>">
                        <?php if (has_error('policyUrl')) : ?>
                            <p class="text-danger"><?= error('policyUrl') ?></p>
                        <?php endif ?>
                    </div>
                    <div class="col">
                        <p class="text-muted small pt-5">May be either a full or relative URL</p>
                    </div>
                </div>

                <!-- Consent Message -->
                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="consentMessage">Initial Message on Consent Form</label>
                        <textarea name="consentMessage" rows="4" class="form-control"><?= old('consentMessage', setting('Consent.consentMessage')) ?></textarea>
                        <?php if (has_error('consentMessage')) : ?>
                            <p class="text-danger"><?= error('consentMessage') ?></p>
                        <?php endif ?>
                    </div>
                    <div class="col">
                        <p class="text-muted small pt-5">May be either a full or relative URL. Will replace {policy_url} with the correct URL.</p>
                    </div>
                </div>
            </fieldset>



            <fieldset>
                <legend>Consents</legend>

                <p>The available Consents that the user may agree to.</p>

                <?php if(isset($consents) && count($consents)) : ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-3">Alias</th>
                            <th class="col-3">Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach(setting('Consent.consents') as $alias => $info) : ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control" disabled readonly value="<?= esc($alias) ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="consents[<?= $alias ?>][name]" value="<?= old("consents[{$alias}][name]", $info['name']) ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="consents[<?= $alias ?>][desc]" value="<?= old("consents[{$alias}][desc]", $info['desc']) ?>">
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </fieldset>

            <div class="text-end px-5 py-3">
                <input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
            </div>
        </form>
    </x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>

</script>
<?php $this->endSection() ?>
