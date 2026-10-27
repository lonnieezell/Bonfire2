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

        <x-admin-box title="From" description="The default name and address emails are sent from.">
            <x-setting-row label="Name" for="fromName">
                <input type="text" class="form-control" name="fromName" id="fromName" value="<?= esc(old('fromName', setting('Email.fromName'))) ?>">
                <?php if (has_error('fromName')) : ?>
                    <p class="text-danger small mb-0"><?= error('fromName') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="Email" for="fromEmail">
                <input type="email" class="form-control" name="fromEmail" id="fromEmail" value="<?= esc(old('fromEmail', setting('Email.fromEmail'))) ?>" />
                <?php if (has_error('fromEmail')) : ?>
                    <p class="text-danger small mb-0"><?= error('fromEmail') ?></p>
                <?php endif ?>
            </x-setting-row>
        </x-admin-box>

        <x-admin-box title="Delivery" description="How mail is sent. The most common choice is SMTP.">
            <div x-data="{ protocol: '<?= esc(old('protocol', $activeTab), 'js') ?>', otherPort: <?= $other ? 'true' : 'false' ?> }">
                <x-setting-row label="Protocol">
                    <div @change="protocol = $event.target.value">
                        <x-segmented name="protocol" label="Protocol" value="<?= esc(old('protocol', setting('Email.protocol'))) ?>" options='{"smtp":"SMTP","mail":"Mail","sendmail":"Sendmail"}' />
                    </div>
                </x-setting-row>

                <div x-show="protocol === 'mail'" x-cloak>
                    <div class="bf-callout bf-callout-info">Mail is only available on Linux servers. It has no options.</div>
                </div>

                <div x-show="protocol === 'sendmail'" x-cloak>
                    <x-setting-row label="Sendmail path" for="mailPath" description="Path to the sendmail executable.">
                        <input type="text" name="mailPath" id="mailPath" class="form-control" value="<?= esc(old('mailPath', setting('Email.mailPath'))) ?>">
                        <?php if (has_error('mailPath')) : ?>
                            <p class="text-danger small mb-0"><?= error('mailPath') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                </div>

                <div x-show="protocol === 'smtp'">
                    <x-setting-row label="Host" for="SMTPHost">
                        <input type="text" name="SMTPHost" id="SMTPHost" class="form-control" value="<?= esc(old('SMTPHost', setting('Email.SMTPHost'))) ?>">
                        <?php if (has_error('SMTPHost')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPHost') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="Port" for="SMTPPort">
                        <div class="d-flex gap-2">
                            <select name="SMTPPort" id="SMTPPort" class="form-select bf-input-narrow" @change="otherPort = $event.target.value === 'other'">
                                <?php foreach ($ports as $option) : ?>
                                    <option value="<?= $option ?>"<?= ! $other && $port === $option ? ' selected' : '' ?>><?= $option ?></option>
                                <?php endforeach ?>
                                <option value="other"<?= $other ? ' selected' : '' ?>>other</option>
                            </select>
                            <input type="text" name="SMTPPortOther" class="form-control bf-input-narrow" aria-label="Other port" x-show="otherPort"
                                   value="<?= esc(old('SMTPPortOther', $other && $port !== 'other' ? $port : '')) ?>">
                        </div>
                        <?php if (has_error('SMTPPort') || has_error('SMTPPortOther')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPPort') ?: error('SMTPPortOther') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="Username" for="SMTPUser">
                        <input type="text" name="SMTPUser" id="SMTPUser" class="form-control" value="<?= esc(old('SMTPUser', setting('Email.SMTPUser'))) ?>">
                        <?php if (has_error('SMTPUser')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPUser') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="Password" for="SMTPPass">
                        <input type="password" name="SMTPPass" id="SMTPPass" class="form-control" value="<?= esc(old('SMTPPass', setting('Email.SMTPPass'))) ?>">
                        <?php if (has_error('SMTPPass')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPPass') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="Encryption">
                        <x-segmented name="SMTPCrypto" label="Encryption" value="<?= esc((string) old('SMTPCrypto', setting('Email.SMTPCrypto'))) ?>" options='{"":"None","tls":"TLS","ssl":"SSL"}' />
                        <?php if (has_error('SMTPCrypto')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPCrypto') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="Timeout" for="SMTPTimeout" description="In seconds.">
                        <input type="number" name="SMTPTimeout" id="SMTPTimeout" class="form-control bf-input-narrow" value="<?= esc(old('SMTPTimeout', setting('Email.SMTPTimeout'))) ?>">
                        <?php if (has_error('SMTPTimeout')) : ?>
                            <p class="text-danger small mb-0"><?= error('SMTPTimeout') ?></p>
                        <?php endif ?>
                    </x-setting-row>
                    <x-setting-row label="Persistent connection" for="SMTPKeepAlive">
                        <x-switch name="SMTPKeepAlive" id="SMTPKeepAlive" checked="<?= old('SMTPKeepAlive', setting('Email.SMTPKeepAlive')) ? '1' : '0' ?>" />
                    </x-setting-row>
                </div>
            </div>
        </x-admin-box>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
