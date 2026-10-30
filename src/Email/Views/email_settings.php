<?php
$this->extend('master');

$ports = ['25', '587', '465', '2525'];
$port  = (string) old('SMTPPort', setting('Email.SMTPPort'));
$other = $port === 'other' || ($port !== '' && ! in_array($port, $ports, true));
?>

<?php $this->section('main') ?>
<x-settings-layout>
    <form action="<?= site_url(ADMIN_AREA . '/settings/email') ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="<?= esc(lang('Email.from'), 'attr') ?>" description="<?= esc(lang('Email.fromDesc'), 'attr') ?>">
            <x-setting-row label="<?= esc(lang('Email.fromName'), 'attr') ?>" for="fromName">
                <input type="text" class="form-control" name="fromName" id="fromName" value="<?= esc(old('fromName', setting('Email.fromName'))) ?>">
                <?php if (has_error('fromName')) : ?>
                    <p class="text-danger small mb-0"><?= error('fromName') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Email.fromEmail'), 'attr') ?>" for="fromEmail">
                <input type="email" class="form-control" name="fromEmail" id="fromEmail" value="<?= esc(old('fromEmail', setting('Email.fromEmail'))) ?>" />
                <?php if (has_error('fromEmail')) : ?>
                    <p class="text-danger small mb-0"><?= error('fromEmail') ?></p>
                <?php endif ?>
            </x-setting-row>
        </x-admin-box>

        <x-admin-box title="<?= esc(lang('Email.delivery'), 'attr') ?>" description="<?= esc(lang('Email.deliveryDesc'), 'attr') ?>">
            <div x-data="{ protocol: '<?= esc(old('protocol', $activeTab), 'js') ?>', otherPort: <?= $other ? 'true' : 'false' ?> }">
                <x-setting-row label="<?= esc(lang('Email.protocol'), 'attr') ?>">
                    <div @change="protocol = $event.target.value">
                        <x-segmented name="protocol" label="<?= esc(lang('Email.protocol'), 'attr') ?>" value="<?= esc(old('protocol', setting('Email.protocol'))) ?>" options='{"smtp":"SMTP","mail":"Mail","sendmail":"Sendmail"}' />
                    </div>
                </x-setting-row>

                <div x-show="protocol === 'mail'" x-cloak>
                    <div class="bf-callout bf-callout-info"><?= lang('Email.mailInfo') ?></div>
                </div>

                <div x-show="protocol === 'sendmail'" x-cloak>
                    <x-setting-row label="<?= esc(lang('Email.mailPath'), 'attr') ?>" for="mailPath" description="<?= esc(lang('Email.mailPathDesc'), 'attr') ?>">
                        <input type="text" name="mailPath" id="mailPath" class="form-control" value="<?= esc(old('mailPath', setting('Email.mailPath'))) ?>">
                        <?php if (has_error('mailPath')) : ?>
                            <p class="text-danger small mb-0"><?= error('mailPath') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                </div>

                <div x-show="protocol === 'smtp'">
                    <x-setting-row label="<?= esc(lang('Email.host'), 'attr') ?>" for="SMTPHost">
                        <input type="text" name="SMTPHost" id="SMTPHost" class="form-control" value="<?= esc(old('SMTPHost', setting('Email.SMTPHost'))) ?>">
                        <?php if (has_error('SMTPHost')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPHost') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="<?= esc(lang('Email.port'), 'attr') ?>" for="SMTPPort">
                        <div class="d-flex gap-2">
                            <select name="SMTPPort" id="SMTPPort" class="form-select bf-input-narrow" @change="otherPort = $event.target.value === 'other'">
                                <?php foreach ($ports as $option) : ?>
                                    <option value="<?= $option ?>"<?= ! $other && $port === $option ? ' selected' : '' ?>><?= $option ?></option>
                                <?php endforeach ?>
                                <option value="other"<?= $other ? ' selected' : '' ?>><?= lang('Email.portOther') ?></option>
                            </select>
                            <input type="text" name="SMTPPortOther" class="form-control bf-input-narrow" aria-label="<?= esc(lang('Email.portOtherLabel'), 'attr') ?>" x-show="otherPort"
                                   value="<?= esc(old('SMTPPortOther', $other && $port !== 'other' ? $port : '')) ?>">
                        </div>
                        <?php if (has_error('SMTPPort') || has_error('SMTPPortOther')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPPort') ?: error('SMTPPortOther') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="<?= esc(lang('Email.username'), 'attr') ?>" for="SMTPUser">
                        <input type="text" name="SMTPUser" id="SMTPUser" class="form-control" value="<?= esc(old('SMTPUser', setting('Email.SMTPUser'))) ?>">
                        <?php if (has_error('SMTPUser')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPUser') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="<?= esc(lang('Email.password'), 'attr') ?>" for="SMTPPass">
                        <input type="password" name="SMTPPass" id="SMTPPass" class="form-control" value="<?= esc(old('SMTPPass', setting('Email.SMTPPass'))) ?>">
                        <?php if (has_error('SMTPPass')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPPass') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="<?= esc(lang('Email.encryption'), 'attr') ?>">
                        <x-segmented name="SMTPCrypto" label="<?= esc(lang('Email.encryption'), 'attr') ?>" value="<?= esc((string) old('SMTPCrypto', setting('Email.SMTPCrypto'))) ?>" options="<?= esc(json_encode(['' => lang('Email.encryptionNone'), 'tls' => 'TLS', 'ssl' => 'SSL']), 'attr') ?>" />
                        <?php if (has_error('SMTPCrypto')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPCrypto') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="<?= esc(lang('Email.timeout'), 'attr') ?>" for="SMTPTimeout" description="<?= esc(lang('Email.timeoutDesc'), 'attr') ?>">
                        <input type="number" name="SMTPTimeout" id="SMTPTimeout" class="form-control bf-input-narrow" value="<?= esc(old('SMTPTimeout', setting('Email.SMTPTimeout'))) ?>">
                        <?php if (has_error('SMTPTimeout')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPTimeout') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="<?= esc(lang('Email.keepAlive'), 'attr') ?>" for="SMTPKeepAlive">
                        <x-switch name="SMTPKeepAlive" id="SMTPKeepAlive" checked="<?= old('SMTPKeepAlive', setting('Email.SMTPKeepAlive')) ? '1' : '0' ?>" />
                    </x-setting-row>
                </div>
            </div>
        </x-admin-box>

        <x-save-bar save="<?= esc(lang('Bonfire.saveSettings'), 'attr') ?>" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
