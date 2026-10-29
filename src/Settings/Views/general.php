<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <form action="<?= site_url(ADMIN_AREA . '/settings/general') ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="General">
            <x-setting-row label="Site Name" for="siteName" description="Appears in admin, and is available throughout the site.">
                <input type="text" class="form-control" name="siteName" id="siteName" value="<?= esc(old('siteName', setting('Site.siteName')), 'attr') ?>" />
            </x-setting-row>
            <x-setting-row label="Site Online" for="siteOnline" description="When off, only Superadmin and user groups with permission can access the site.">
                <x-switch name="siteOnline" id="siteOnline" checked="<?= old('siteOnline', setting('Site.siteOnline') ?? false) ? '1' : '0' ?>" />
            </x-setting-row>
        </x-admin-box>

        <x-admin-box title="Appearance">
            <div x-data="{ accent: '<?= admin_accent() ?>' }"
                 x-effect="document.documentElement.style.setProperty('--bf-accent', accent)">
                <x-setting-row label="Accent Color" for="adminAccent" description="The highlight color used for buttons, links and active items throughout the admin.">
                    <div class="d-flex align-items-center gap-2">
                        <?php foreach (['#D9622B', '#2F6FEB', '#0F9F6E', '#7C5CE0'] as $preset) : ?>
                            <button type="button" class="bf-swatch" style="background: <?= $preset ?>"
                                    x-bind:class="accent.toLowerCase() === '<?= strtolower($preset) ?>' && 'active'"
                                    x-on:click="$refs.picker.value = '<?= $preset ?>'; $refs.picker.dispatchEvent(new Event('input', { bubbles: true }))"
                                    aria-label="<?= $preset ?>"></button>
                        <?php endforeach ?>
                        <input type="color" class="form-control form-control-color" name="adminAccent" id="adminAccent" value="<?= admin_accent() ?>" x-ref="picker" x-model="accent" />
                    </div>
                    <?php if (has_error('adminAccent')) : ?>
                        <p class="text-danger small mb-0"><?= error('adminAccent') ?></p>
                    <?php endif ?>
                </x-setting-row>
            </div>
        </x-admin-box>

        <x-admin-box title="Date and Time">
            <x-setting-row label="Timezone" for="timezone">
                <div class="d-flex gap-2" x-data="{ area: '<?= esc($currentTZArea, 'js') ?>' }">
                    <select name="timezoneArea" class="form-select" aria-label="Timezone region" x-model="area"
                            hx-get="/<?= ADMIN_AREA ?>/settings/timezones"
                            hx-target="#timezone"
                            hx-include="[name='timezoneArea']"
                    >
                        <option value="">Select region...</option>
                        <?php foreach ($timezones as $timezone) : ?>
                            <option value="<?= $timezone ?>" <?php if ($currentTZArea === $timezone) : ?> selected <?php endif ?>>
                                <?= $timezone ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <select name="timezone" id="timezone" class="form-select" aria-label="Timezone" x-show="area && area !== 'UTC'" x-cloak>
                        <?php if (isset($timezoneOptions) && ! empty($timezoneOptions)) : ?>
                            <?= $timezoneOptions ?>
                        <?php else : ?>
                            <option value="0">No timezones</option>
                        <?php endif ?>
                    </select>
                </div>
            </x-setting-row>

            <div class="bf-setting-block">
                <div class="bf-setting-label">Date Format</div>
                <div class="bf-choice-cards" role="radiogroup" aria-label="Date Format">
                    <?php foreach (['m/d/Y' => 'mm/dd/yyyy', 'd/m/Y' => 'dd/mm/yyyy', 'd-m-Y' => 'dd-mm-yyyy', 'Y-m-d' => 'yyyy-mm-dd', 'M j, Y' => 'mmm dd, yyyy', 'd.m.Y' => 'dd.mm.yyyy'] as $format => $pattern) : ?>
                        <label class="bf-choice-card">
                            <input type="radio" name="dateFormat" value="<?= $format ?>"<?= old('dateFormat', $dateFormat) === $format ? ' checked' : '' ?>>
                            <span class="bf-choice-card-title"><?= date($format) ?></span>
                            <span class="bf-choice-card-desc bf-mono"><?= $pattern ?></span>
                        </label>
                    <?php endforeach ?>
                </div>
            </div>

            <x-setting-row label="Time Format">
                <x-segmented name="timeFormat" label="Time Format" value="<?= esc(old('timeFormat', $timeFormat)) ?>" options='{"g:i A":"12-hour","H:i":"24-hour"}' />
            </x-setting-row>
        </x-admin-box>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
