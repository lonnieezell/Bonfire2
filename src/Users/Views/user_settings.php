<?php
use Bonfire\Auth\Actions\Email2FA;
use Bonfire\Auth\Actions\EmailActivator;
use CodeIgniter\Shield\Authentication\Passwords\CompositionValidator;
use CodeIgniter\Shield\Authentication\Passwords\DictionaryValidator;
use CodeIgniter\Shield\Authentication\Passwords\NothingPersonalValidator;
use CodeIgniter\Shield\Authentication\Passwords\PwnedValidator;

$this->extend('master');

$validators = [
    CompositionValidator::class     => ['Composition Validator', 'Primarily checks the password length requirements.'],
    NothingPersonalValidator::class => ['Nothing Personal Validator', 'Checks the password for similarities with the email, username and other personal fields.'],
    DictionaryValidator::class      => ['Dictionary Validator', 'Checks the password against nearly 600,000 leaked passwords.'],
    PwnedValidator::class           => ['Pwned Validator', 'Uses a third-party site (haveibeenpwned.com) to check the password against millions of leaked passwords.'],
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

        <x-admin-box title="Registration">
            <x-setting-row label="Allow registration" for="allow-registration" description="Let users register themselves on the site. When off, an admin needs to create users.">
                <x-switch name="allowRegistration" id="allow-registration" checked="<?= old('allowRegistration', setting('Auth.allowRegistration')) ? '1' : '0' ?>" />
            </x-setting-row>
            <x-setting-row label="Force email verification" for="email-activation" description="Send new users a code by email to confirm their address.">
                <x-switch name="emailActivation" offValue id="email-activation" value="<?= esc(EmailActivator::class) ?>"
                    checked="<?= old('emailActivation', setting('Auth.actions')['register']) === EmailActivator::class ? '1' : '0' ?>" />
            </x-setting-row>
            <?php if (isset($groups) && count($groups)) : ?>
                <x-setting-row label="Default user group" for="defaultGroup" description="The group newly registered users join.">
                    <select name="defaultGroup" id="defaultGroup" class="form-select">
                        <?php foreach ($groups as $group => $info) : ?>
                            <option value="<?= esc($group) ?>"<?= $group === $defaultGroup ? ' selected' : '' ?>><?= esc($info['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                </x-setting-row>
            <?php endif ?>
        </x-admin-box>

        <x-admin-box title="Login">
            <div x-data="{ remember: <?= $remember ? 'true' : 'false' ?> }">
                <x-setting-row label="Remember users" for="allow-remember" description="Users don't have to log in on every visit.">
                    <x-switch name="allowRemember" id="allow-remember" x-model="remember"
                        checked="<?= $remember ? '1' : '0' ?>" />
                </x-setting-row>
                <div x-show="remember">
                    <x-setting-row label="Remember for" for="rememberLength">
                        <select name="rememberLength" id="rememberLength" class="form-select">
                            <option value="0">How long to remember...</option>
                            <?php foreach ($rememberOptions ?? [] as $title => $seconds) : ?>
                                <option value="<?= $seconds ?>"<?= old('rememberLength', setting('Auth.sessionConfig')['rememberLength']) === (string) $seconds ? ' selected' : '' ?>><?= $title ?></option>
                            <?php endforeach ?>
                        </select>
                    </x-setting-row>
                </div>
            </div>
            <x-setting-row label="Force 2FA after login" for="email-2fa" description="Send users a code by email to confirm each login.">
                <x-switch name="email2FA" offValue id="email-2fa" value="<?= esc(Email2FA::class) ?>"
                    checked="<?= old('email2FA', setting('Auth.actions')['login']) === Email2FA::class ? '1' : '0' ?>" />
            </x-setting-row>
        </x-admin-box>

        <x-admin-box title="Passwords" description="These rules determine how secure a password must be.">
            <x-setting-row label="Minimum length" for="minimumPasswordLength" description="A minimum of 8 is suggested. 10 is recommended.">
                <input type="number" name="minimumPasswordLength" id="minimumPasswordLength" class="form-control bf-input-narrow" min="8" step="1"
                       value="<?= old('minimumPasswordLength', setting('Auth.minimumPasswordLength')) ?>">
                <?php if (has_error('minimumPasswordLength')) : ?>
                    <p class="text-danger small mb-0"><?= error('minimumPasswordLength') ?></p>
                <?php endif ?>
            </x-setting-row>
            <div x-data="{ validators: <?= esc(json_encode(array_values($activeValidators)), 'attr') ?>, get overlap() { return this.validators.includes(<?= esc(json_encode(DictionaryValidator::class), 'attr') ?>) && this.validators.includes(<?= esc(json_encode(PwnedValidator::class), 'attr') ?>) } }">
                <?php foreach ($validators as $class => [$title, $description]) : ?>
                    <?php $id = 'validator-' . strtolower(substr(strrchr($class, '\\'), 1)) ?>
                    <x-setting-row label="<?= $title ?>" for="<?= $id ?>" description="<?= esc($description) ?>">
                        <x-switch name="validators[]" offValue id="<?= $id ?>" value="<?= esc($class) ?>" x-model="validators"
                            checked="<?= in_array($class, $activeValidators, true) ? '1' : '0' ?>" />
                    </x-setting-row>
                <?php endforeach ?>
                <div class="bf-callout bf-callout-warn" x-show="validators.length === 0" x-cloak>
                    No validators are on: passwords only need to meet the minimum length.
                </div>
                <div class="bf-callout bf-callout-warn" x-show="overlap" x-cloak>
                    The Dictionary and Pwned validators overlap. You only need one of them.
                </div>
            </div>
        </x-admin-box>

        <x-admin-box title="Avatars">
            <div x-data="{ useGravatar: <?= $useGravatar ? 'true' : 'false' ?>, avatarResize: <?= $avatarResize ? 'true' : 'false' ?> }">
                <x-setting-row label="Initials from" for="avatarNameBasis" description="Used for the initials in an avatar when there is no image.">
                    <select name="avatarNameBasis" id="avatarNameBasis" class="form-select">
                        <option value="name" <?= old('avatarNameBasis', setting('Users.avatarNameBasis')) === 'name' ? 'selected' : '' ?>>Full Name</option>
                        <option value="username" <?= old('avatarNameBasis', setting('Users.avatarNameBasis')) === 'username' ? 'selected' : '' ?>>Username</option>
                    </select>
                </x-setting-row>
                <x-setting-row label="Use Gravatar" for="use-gravatar" description="Use Gravatar (gravatar.com) when a user has not uploaded an avatar.">
                    <x-switch name="useGravatar" id="use-gravatar" x-model="useGravatar"
                        checked="<?= $useGravatar ? '1' : '0' ?>" />
                </x-setting-row>
                <div x-show="useGravatar">
                    <x-setting-row label="Gravatar default style" for="gravatarDefault" description="See gravatar.com for examples of each style.">
                        <select name="gravatarDefault" id="gravatarDefault" class="form-select">
                            <option value="">Select default style....</option>
                            <?php foreach (['mp' => 'mystery person', 'identicon' => 'identicon', 'monsterid' => 'monsterid', 'wavatar' => 'wavatar', 'retro' => 'retro', 'robohash' => 'robohash', 'blank' => 'blank'] as $style => $label) : ?>
                                <option value="<?= $style ?>" <?= old('gravatarDefault', setting('Users.gravatarDefault')) === $style ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </x-setting-row>
                </div>
                <x-setting-row label="Downsize on upload" for="avatar-resize" description="Downsize user-uploaded avatar images.">
                    <x-switch name="avatarResize" id="avatar-resize" x-model="avatarResize"
                        checked="<?= $avatarResize ? '1' : '0' ?>" />
                </x-setting-row>
                <div x-show="avatarResize">
                    <x-setting-row label="Max dimensions" for="avatar-size" description="In pixels; no less than <?= $avatarSizeFloor ?>.">
                        <input placeholder="<?= $avatarSizeFloor ?>" name="avatarSize" type="number" min="<?= $avatarSizeFloor ?>" class="form-control bf-input-narrow" value="<?= old('avatarSize', setting('Users.avatarSize')) ?>" id="avatar-size">
                    </x-setting-row>
                </div>
            </div>
        </x-admin-box>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
