<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <form action="<?= site_url(ADMIN_AREA . '/settings/consent') ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="<?= esc(lang('Consent.consentModTitle'), 'attr') ?>" description="<?= esc(lang('Consent.consentDesc'), 'attr') ?>">
            <x-setting-row label="<?= esc(lang('Consent.requireConsent'), 'attr') ?>" for="requireConsent" description="<?= esc(lang('Consent.requireConsentDesc'), 'attr') ?>">
                <x-switch name="requireConsent" id="requireConsent" checked="<?= setting('Consent.requireConsent') ? '1' : '0' ?>" />
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Consent.consentLength'), 'attr') ?>" for="consentLength" description="<?= esc(lang('Consent.consentLengthDesc'), 'attr') ?>">
                <input type="number" min="0" step="1" class="form-control bf-input-narrow" name="consentLength" id="consentLength"
                       value="<?= esc(old('consentLength', setting('Consent.consentLength'))) ?>">
                <?php if (has_error('consentLength')) : ?>
                    <p class="text-danger small mb-0"><?= error('consentLength') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Consent.policyUrl'), 'attr') ?>" for="policyUrl" description="<?= esc(lang('Consent.policyUrlDesc'), 'attr') ?>">
                <input type="text" class="form-control" name="policyUrl" id="policyUrl"
                       value="<?= esc(old('policyUrl', setting('Consent.policyUrl'))) ?>">
                <?php if (has_error('policyUrl')) : ?>
                    <p class="text-danger small mb-0"><?= error('policyUrl') ?></p>
                <?php endif ?>
            </x-setting-row>
        </x-admin-box>

        <x-save-bar save="<?= esc(lang('Bonfire.saveSettings'), 'attr') ?>" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
