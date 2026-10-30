<?php
use Bonfire\Auth\Actions\Email2FA;
use Bonfire\Auth\Actions\EmailActivator;
use CodeIgniter\Shield\Authentication\Passwords\CompositionValidator;
use CodeIgniter\Shield\Authentication\Passwords\DictionaryValidator;
use CodeIgniter\Shield\Authentication\Passwords\NothingPersonalValidator;
use CodeIgniter\Shield\Authentication\Passwords\PwnedValidator;

$this->extend('master');

$validators = [
    CompositionValidator::class     => [lang('Users.validatorComposition'), lang('Users.validatorCompositionDesc')],
    NothingPersonalValidator::class => [lang('Users.validatorNothingPersonal'), lang('Users.validatorNothingPersonalDesc')],
    DictionaryValidator::class      => [lang('Users.validatorDictionary'), lang('Users.validatorDictionaryDesc')],
    PwnedValidator::class           => [lang('Users.validatorPwned'), lang('Users.validatorPwnedDesc')],
];
$activeValidators = old('validators', setting('Auth.passwordValidators')) ?? [];
$remember         = (bool) old('allowRemember', setting('Auth.sessionConfig')['allowRemembering']);
$useGravatar      = (bool) old('useGravatar', setting('Users.useGravatar'));
$avatarResize     = (bool) old('avatarResize', setting('Users.avatarResize'));
$avatarSizeFloor  = setting('Users.avatarResizeFloor') ?? 32;
?>

<?php $this->section('main') ?>
<x-settings-layout>
    <form action="<?= site_url(ADMIN_AREA . '/settings/users') ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="<?= esc(lang('Users.registration'), 'attr') ?>">
            <x-setting-row label="<?= esc(lang('Users.allowRegistration'), 'attr') ?>" for="allow-registration" description="<?= esc(lang('Users.allowRegistrationDesc'), 'attr') ?>">
                <x-switch name="allowRegistration" id="allow-registration" checked="<?= old('allowRegistration', setting('Auth.allowRegistration')) ? '1' : '0' ?>" />
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Users.emailActivation'), 'attr') ?>" for="email-activation" description="<?= esc(lang('Users.emailActivationDesc'), 'attr') ?>">
                <x-switch name="emailActivation" offValue id="email-activation" value="<?= esc(EmailActivator::class) ?>"
                    checked="<?= old('emailActivation', setting('Auth.actions')['register']) === EmailActivator::class ? '1' : '0' ?>" />
            </x-setting-row>
            <?php if (isset($groups) && count($groups)) : ?>
                <x-setting-row label="<?= esc(lang('Users.defaultGroup'), 'attr') ?>" for="defaultGroup" description="<?= esc(lang('Users.defaultGroupDesc'), 'attr') ?>">
                    <select name="defaultGroup" id="defaultGroup" class="form-select">
                        <?php foreach ($groups as $group => $info) : ?>
                            <option value="<?= esc($group) ?>"<?= $group === $defaultGroup ? ' selected' : '' ?>><?= esc($info['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                </x-setting-row>
            <?php endif ?>
        </x-admin-box>

        <x-admin-box title="<?= esc(lang('Users.login'), 'attr') ?>">
            <div x-data="{ remember: <?= $remember ? 'true' : 'false' ?> }">
                <x-setting-row label="<?= esc(lang('Users.rememberUsers'), 'attr') ?>" for="allow-remember" description="<?= esc(lang('Users.rememberUsersDesc'), 'attr') ?>">
                    <x-switch name="allowRemember" id="allow-remember" x-model="remember"
                        checked="<?= $remember ? '1' : '0' ?>" />
                </x-setting-row>
                <div x-show="remember">
                    <x-setting-row label="<?= esc(lang('Users.rememberFor'), 'attr') ?>" for="rememberLength">
                        <select name="rememberLength" id="rememberLength" class="form-select">
                            <option value="0"><?= lang('Users.rememberSelect') ?></option>
                            <?php foreach ($rememberOptions ?? [] as $title => $seconds) : ?>
                                <option value="<?= $seconds ?>"<?= old('rememberLength', setting('Auth.sessionConfig')['rememberLength']) === (string) $seconds ? ' selected' : '' ?>><?= $title ?></option>
                            <?php endforeach ?>
                        </select>
                    </x-setting-row>
                </div>
            </div>
            <x-setting-row label="<?= esc(lang('Users.email2fa'), 'attr') ?>" for="email-2fa" description="<?= esc(lang('Users.email2faDesc'), 'attr') ?>">
                <x-switch name="email2FA" offValue id="email-2fa" value="<?= esc(Email2FA::class) ?>"
                    checked="<?= old('email2FA', setting('Auth.actions')['login']) === Email2FA::class ? '1' : '0' ?>" />
            </x-setting-row>
        </x-admin-box>

        <x-admin-box title="<?= esc(lang('Users.passwords'), 'attr') ?>" description="<?= esc(lang('Users.passwordsDesc'), 'attr') ?>">
            <x-setting-row label="<?= esc(lang('Users.minimumPasswordLength'), 'attr') ?>" for="minimumPasswordLength" description="<?= esc(lang('Users.minimumPasswordLengthDesc'), 'attr') ?>">
                <input type="number" name="minimumPasswordLength" id="minimumPasswordLength" class="form-control bf-input-narrow" min="8" step="1"
                       value="<?= old('minimumPasswordLength', setting('Auth.minimumPasswordLength')) ?>">
                <?php if (has_error('minimumPasswordLength')) : ?>
                    <p class="text-danger small mb-0"><?= error('minimumPasswordLength') ?></p>
                <?php endif ?>
            </x-setting-row>
            <div x-data="{ validators: <?= esc(json_encode(array_values($activeValidators)), 'attr') ?>, get overlap() { return this.validators.includes(<?= esc(json_encode(DictionaryValidator::class), 'attr') ?>) && this.validators.includes(<?= esc(json_encode(PwnedValidator::class), 'attr') ?>) } }">
                <?php foreach ($validators as $class => [$title, $description]) : ?>
                    <?php $id = 'validator-' . strtolower(substr(strrchr($class, '\\'), 1)) ?>
                    <x-setting-row label="<?= esc($title, 'attr') ?>" for="<?= $id ?>" description="<?= esc($description, 'attr') ?>">
                        <x-switch name="validators[]" offValue id="<?= $id ?>" value="<?= esc($class) ?>" x-model="validators"
                            checked="<?= in_array($class, $activeValidators, true) ? '1' : '0' ?>" />
                    </x-setting-row>
                <?php endforeach ?>
                <div class="bf-callout bf-callout-warn" x-show="validators.length === 0" x-cloak>
                    <?= lang('Users.noValidators') ?>
                </div>
                <div class="bf-callout bf-callout-warn" x-show="overlap" x-cloak>
                    <?= lang('Users.validatorsOverlap') ?>
                </div>
            </div>
        </x-admin-box>

        <x-admin-box title="<?= esc(lang('Users.avatars'), 'attr') ?>">
            <div x-data="{ useGravatar: <?= $useGravatar ? 'true' : 'false' ?>, avatarResize: <?= $avatarResize ? 'true' : 'false' ?> }">
                <x-setting-row label="<?= esc(lang('Users.avatarNameBasis'), 'attr') ?>" for="avatarNameBasis" description="<?= esc(lang('Users.avatarNameBasisDesc'), 'attr') ?>">
                    <select name="avatarNameBasis" id="avatarNameBasis" class="form-select">
                        <option value="name" <?= old('avatarNameBasis', setting('Users.avatarNameBasis')) === 'name' ? 'selected' : '' ?>><?= lang('Users.fullName') ?></option>
                        <option value="username" <?= old('avatarNameBasis', setting('Users.avatarNameBasis')) === 'username' ? 'selected' : '' ?>><?= lang('Users.username') ?></option>
                    </select>
                </x-setting-row>
                <x-setting-row label="<?= esc(lang('Users.useGravatar'), 'attr') ?>" for="use-gravatar" description="<?= esc(lang('Users.useGravatarDesc'), 'attr') ?>">
                    <x-switch name="useGravatar" id="use-gravatar" x-model="useGravatar"
                        checked="<?= $useGravatar ? '1' : '0' ?>" />
                </x-setting-row>
                <div x-show="useGravatar">
                    <x-setting-row label="<?= esc(lang('Users.gravatarDefault'), 'attr') ?>" for="gravatarDefault" description="<?= esc(lang('Users.gravatarDefaultDesc'), 'attr') ?>">
                        <select name="gravatarDefault" id="gravatarDefault" class="form-select">
                            <option value=""><?= lang('Users.gravatarSelect') ?></option>
                            <?php foreach (['mp' => lang('Users.gravatarMysteryPerson'), 'identicon' => 'identicon', 'monsterid' => 'monsterid', 'wavatar' => 'wavatar', 'retro' => 'retro', 'robohash' => 'robohash', 'blank' => 'blank'] as $style => $label) : ?>
                                <option value="<?= $style ?>" <?= old('gravatarDefault', setting('Users.gravatarDefault')) === $style ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </x-setting-row>
                </div>
                <x-setting-row label="<?= esc(lang('Users.avatarResize'), 'attr') ?>" for="avatar-resize" description="<?= esc(lang('Users.avatarResizeDesc'), 'attr') ?>">
                    <x-switch name="avatarResize" id="avatar-resize" x-model="avatarResize"
                        checked="<?= $avatarResize ? '1' : '0' ?>" />
                </x-setting-row>
                <div x-show="avatarResize">
                    <x-setting-row label="<?= esc(lang('Users.avatarSize'), 'attr') ?>" for="avatar-size" description="<?= esc(lang('Users.avatarSizeDesc', [$avatarSizeFloor]), 'attr') ?>">
                        <input placeholder="<?= $avatarSizeFloor ?>" name="avatarSize" type="number" min="<?= $avatarSizeFloor ?>" class="form-control bf-input-narrow" value="<?= old('avatarSize', setting('Users.avatarSize')) ?>" id="avatar-size">
                    </x-setting-row>
                </div>
            </div>
        </x-admin-box>

        <x-save-bar save="<?= esc(lang('Bonfire.saveSettings'), 'attr') ?>" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
