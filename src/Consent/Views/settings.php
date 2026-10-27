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

        <x-admin-box title="Consent" description="Ask visitors for consent before setting cookies.">
            <x-setting-row label="Require consent" for="requireConsent" description="Show the consent banner to visitors.">
                <x-switch name="requireConsent" id="requireConsent" checked="<?= setting('Consent.requireConsent') ? '1' : '0' ?>" />
            </x-setting-row>
            <x-setting-row label="Days consent is valid" for="consentLength" description="After this, visitors are asked again.">
                <input type="number" min="0" step="1" class="form-control bf-input-narrow" name="consentLength" id="consentLength"
                       value="<?= esc(old('consentLength', setting('Consent.consentLength'))) ?>">
                <?php if (has_error('consentLength')) : ?>
                    <p class="text-danger small mb-0"><?= error('consentLength') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="Cookie policy URL" for="policyUrl" description="A full or relative URL.">
                <input type="text" class="form-control" name="policyUrl" id="policyUrl" x-model="url"
                       value="<?= esc(old('policyUrl', setting('Consent.policyUrl'))) ?>">
                <?php if (has_error('policyUrl')) : ?>
                    <p class="text-danger small mb-0"><?= error('policyUrl') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="Cookie policy title" for="policyTitle" description="Shown in the banner in place of {policy_url}.">
                <input type="text" class="form-control" name="policyTitle" id="policyTitle" x-model="title"
                       value="<?= esc(old('policyTitle', setting('Consent.policyTitle'))) ?>">
                <?php if (has_error('policyTitle')) : ?>
                    <p class="text-danger small mb-0"><?= error('policyTitle') ?></p>
                <?php endif ?>
            </x-setting-row>
            <div class="bf-setting-block">
                <label class="bf-setting-label" for="consentMessage">Banner message</label>
                <div class="bf-setting-desc">{policy_url} becomes a link to the cookie policy.</div>
                <textarea name="consentMessage" id="consentMessage" rows="3" class="form-control mt-2" x-model="message"><?= esc(old('consentMessage', setting('Consent.consentMessage'))) ?></textarea>
                <?php if (has_error('consentMessage')) : ?>
                    <p class="text-danger small mb-0"><?= error('consentMessage') ?></p>
                <?php endif ?>
                <div class="bf-setting-label mt-3">Preview</div>
                <div class="bf-consent-preview" x-cloak>
                    <span x-text="part(0)"></span><template x-if="message.includes('{policy_url}')"><a :href="url" x-text="title || url" target="_blank"></a></template><span x-text="part(1)"></span>
                </div>
            </div>
        </x-admin-box>

        <?php if (isset($consents) && count($consents)) : ?>
            <x-admin-box title="Consents" description="The consents a visitor may agree to.">
                <div class="table-responsive">
                    <table class="table bf-table">
                        <thead>
                            <tr>
                                <th>Alias</th>
                                <th>Name</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consents as $alias => $info) : ?>
                                <tr>
                                    <td class="bf-mono"><?= esc($alias) ?></td>
                                    <td>
                                        <input type="text" class="form-control" aria-label="<?= esc($alias) ?> name" name="consents[<?= esc($alias) ?>][name]" value="<?= esc(old("consents.{$alias}.name", $info['name'])) ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" aria-label="<?= esc($alias) ?> description" name="consents[<?= esc($alias) ?>][desc]" value="<?= esc(old("consents.{$alias}.desc", $info['desc'])) ?>">
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </x-admin-box>
        <?php endif ?>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
