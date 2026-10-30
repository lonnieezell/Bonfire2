<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <form action="<?= site_url(ADMIN_AREA . '/settings/consent') ?>" method="post"
          x-data="{
              message: <?= esc(json_encode((string) old('consentMessage', setting('Consent.consentMessage'))), 'attr') ?>,
              title: <?= esc(json_encode((string) old('policyTitle', setting('Consent.policyTitle'))), 'attr') ?>,
              url: <?= esc(json_encode((string) old('policyUrl', setting('Consent.policyUrl'))), 'attr') ?>,
              part(i) { return this.message.split('{policy_url}')[i] ?? '' },
          }">
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
                <input type="text" class="form-control" name="policyUrl" id="policyUrl" x-model="url"
                       value="<?= esc(old('policyUrl', setting('Consent.policyUrl'))) ?>">
                <?php if (has_error('policyUrl')) : ?>
                    <p class="text-danger small mb-0"><?= error('policyUrl') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Consent.policyTitle'), 'attr') ?>" for="policyTitle" description="<?= esc(lang('Consent.policyTitleDesc'), 'attr') ?>">
                <input type="text" class="form-control" name="policyTitle" id="policyTitle" x-model="title"
                       value="<?= esc(old('policyTitle', setting('Consent.policyTitle'))) ?>">
                <?php if (has_error('policyTitle')) : ?>
                    <p class="text-danger small mb-0"><?= error('policyTitle') ?></p>
                <?php endif ?>
            </x-setting-row>
            <div class="bf-setting-block">
                <label class="bf-setting-label" for="consentMessage"><?= lang('Consent.consentMessage') ?></label>
                <div class="bf-setting-desc"><?= lang('Consent.consentMessageDesc') ?></div>
                <textarea name="consentMessage" id="consentMessage" rows="3" class="form-control mt-2" x-model="message"><?= esc(old('consentMessage', setting('Consent.consentMessage'))) ?></textarea>
                <?php if (has_error('consentMessage')) : ?>
                    <p class="text-danger small mb-0"><?= error('consentMessage') ?></p>
                <?php endif ?>
                <div class="bf-setting-label mt-3"><?= lang('Consent.preview') ?></div>
                <div class="bf-consent-preview" x-cloak>
                    <span x-text="part(0)"></span><template x-if="message.includes('{policy_url}')"><a :href="url" x-text="title || url" target="_blank"></a></template><span x-text="part(1)"></span>
                </div>
            </div>
        </x-admin-box>

        <?php if (isset($consents) && count($consents)) : ?>
            <x-admin-box title="<?= esc(lang('Consent.consents'), 'attr') ?>" description="<?= esc(lang('Consent.consentsDesc'), 'attr') ?>">
                <div class="table-responsive">
                    <table class="table bf-table">
                        <thead>
                            <tr>
                                <th><?= lang('Consent.alias') ?></th>
                                <th><?= lang('Consent.name') ?></th>
                                <th><?= lang('Consent.description') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consents as $alias => $info) : ?>
                                <tr>
                                    <td class="bf-mono"><?= esc($alias) ?></td>
                                    <td>
                                        <input type="text" class="form-control" aria-label="<?= esc(lang('Consent.nameFor', [$alias]), 'attr') ?>" name="consents[<?= esc($alias) ?>][name]" value="<?= esc(old("consents.{$alias}.name", $info['name'])) ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" aria-label="<?= esc(lang('Consent.descriptionFor', [$alias]), 'attr') ?>" name="consents[<?= esc($alias) ?>][desc]" value="<?= esc(old("consents.{$alias}.desc", $info['desc'])) ?>">
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </x-admin-box>
        <?php endif ?>

        <x-save-bar save="<?= esc(lang('Bonfire.saveSettings'), 'attr') ?>" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
