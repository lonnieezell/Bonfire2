<?php $this->extend('master') ?>

<?php
$warningText = [
    'bridge'           => lang('Email.warningBridge'),
    'leftoverRows'     => lang('Email.warningLeftoverRows'),
    'nullDefault'      => lang('Email.warningNullDefault'),
    'brokenDefault'    => lang('Email.warningBrokenDefault', [$defaultMailer]),
    'undefinedDefault' => lang('Email.warningUndefinedDefault', [$defaultMailer]),
];
?>

<?php $this->section('main') ?>
<x-settings-layout>
    <x-admin-box title="<?= esc(lang('Email.mailers'), 'attr') ?>" description="<?= esc(lang('Email.mailersDesc'), 'attr') ?>">
        <?php foreach ($warnings as $warning) : ?>
            <div class="bf-callout bf-callout-warn" data-warning="<?= esc($warning, 'attr') ?>"><?= esc($warningText[$warning]) ?></div>
        <?php endforeach ?>
        <div class="bf-callout bf-callout-info"><a href="https://lonnieezell.github.io/postal/" target="_blank" rel="noopener"><?= lang('Email.mailersDocs') ?></a></div>
    </x-admin-box>

    <?php foreach ($mailers as $mailer) : ?>
        <div id="mailer-<?= esc(rawurlencode($mailer['name']), 'attr') ?>" data-mailer="<?= esc($mailer['name'], 'attr') ?>">
            <x-admin-box title="<?= esc($mailer['name'], 'attr') ?>" description="<?= esc($mailer['transport'], 'attr') ?>">
                <?php if ($mailer['isDefault']) : ?>
                    <span data-default-mailer><x-status tone="ok" label="<?= esc(lang('Email.mailerDefault'), 'attr') ?>" /></span>
                <?php endif ?>
                <?php if ($mailer['bridged']) : ?>
                    <span data-bridged-mailer><x-status tone="warn" label="<?= esc(lang('Email.mailerBridged'), 'attr') ?>" /></span>
                <?php endif ?>

                <?php if ($mailer['unknownTransport']) : ?>
                    <div class="bf-callout bf-callout-warn" data-problem="unknownTransport"><?= lang('Email.mailerUnknownTransport') ?></div>
                <?php endif ?>
                <?php if ($mailer['brokenChain']) : ?>
                    <div class="bf-callout bf-callout-warn" data-problem="brokenChain"><?= lang('Email.mailerBrokenChain') ?></div>
                <?php endif ?>
                <?php if ($mailer['loops']) : ?>
                    <div class="bf-callout bf-callout-warn" data-problem="loops"><?= lang('Email.mailerLoops') ?></div>
                <?php endif ?>

                <?php if ($mailer['chain'] !== null) : ?>
                    <p class="mb-1"><?= lang('Email.mailerChain') ?></p>
                    <ol>
                        <?php foreach ($mailer['chain'] as $child) : ?>
                            <li>
                                <?php if ($child['defined']) : ?>
                                    <a href="#mailer-<?= esc(rawurlencode($child['name']), 'attr') ?>" data-chain-child="<?= esc($child['name'], 'attr') ?>"><?= esc($child['name']) ?></a>
                                <?php else : ?>
                                    <span data-chain-missing="<?= esc($child['name'], 'attr') ?>"><?= esc($child['name']) ?> (<?= lang('Email.mailerChainMissing') ?>)</span>
                                <?php endif ?>
                            </li>
                        <?php endforeach ?>
                    </ol>
                <?php endif ?>

                <?php if ($mailer['settings'] !== []) : ?>
                    <table class="table bf-table">
                        <tbody>
                            <?php foreach ($mailer['settings'] as $setting) : ?>
                                <tr data-setting="<?= esc($setting['key'], 'attr') ?>">
                                    <th scope="row"><?= esc($setting['key']) ?></th>
                                    <td>
                                        <?php if ($setting['masked']) : ?>
                                            <x-status tone="<?= $setting['value'] === 'set' ? 'ok' : 'muted' ?>" label="<?= esc(lang($setting['value'] === 'set' ? 'Email.maskSet' : 'Email.maskEmpty'), 'attr') ?>" />
                                        <?php else : ?>
                                            <?= esc($setting['value']) ?>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                <?php elseif ($mailer['chain'] === null) : ?>
                    <p class="mb-0"><?= lang('Email.mailerNoSettings') ?></p>
                <?php endif ?>
            </x-admin-box>
        </div>
    <?php endforeach ?>

    <x-admin-box title="<?= esc(lang('Email.testSend'), 'attr') ?>" description="<?= esc(lang('Email.testSendDesc'), 'attr') ?>">
        <?php if ($testResult !== null) : ?>
            <div class="bf-callout <?= $testResult['outcome'] === 'failed' ? 'bf-callout-warn' : 'bf-callout-info' ?>" data-test-result="<?= esc($testResult['outcome'], 'attr') ?>"><?= esc($testResult['message']) ?></div>
        <?php endif ?>
        <form action="<?= url_to('email-test-send') ?>" method="post">
            <?= csrf_field() ?>
            <x-setting-row label="<?= esc(lang('Email.testMailer'), 'attr') ?>" for="testMailer">
                <select name="mailer" id="testMailer" class="form-select bf-input-narrow">
                    <?php foreach ($mailers as $mailer) : ?>
                        <option value="<?= esc($mailer['name'], 'attr') ?>"<?= $mailer['name'] === old('mailer', $defaultMailer, false) ? ' selected' : '' ?>><?= esc($mailer['name']) ?></option>
                    <?php endforeach ?>
                </select>
                <?php if (has_error('mailer')) : ?>
                    <p class="text-danger small mb-0"><?= error('mailer') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Email.testRecipient'), 'attr') ?>" for="testRecipient">
                <input type="email" class="form-control" name="recipient" id="testRecipient" value="<?= esc(old('recipient', $address, false), 'attr') ?>">
                <?php if (has_error('recipient')) : ?>
                    <p class="text-danger small mb-0"><?= error('recipient') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-button><?= lang('Email.testButton') ?></x-button>
        </form>
    </x-admin-box>

    <form action="<?= site_url(ADMIN_AREA . '/settings/email') ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="<?= esc(lang('Email.from'), 'attr') ?>" description="<?= esc(lang('Email.fromDesc'), 'attr') ?>">
            <x-setting-row label="<?= esc(lang('Email.fromName'), 'attr') ?>" for="fromName">
                <input type="text" class="form-control" name="fromName" id="fromName" value="<?= esc(old('fromName', setting('Email.fromName'), false), 'attr') ?>">
                <?php if (has_error('fromName')) : ?>
                    <p class="text-danger small mb-0"><?= error('fromName') ?></p>
                <?php endif ?>
            </x-setting-row>
            <x-setting-row label="<?= esc(lang('Email.fromEmail'), 'attr') ?>" for="fromEmail">
                <input type="email" class="form-control" name="fromEmail" id="fromEmail" value="<?= esc(old('fromEmail', setting('Email.fromEmail'), false), 'attr') ?>" />
                <?php if (has_error('fromEmail')) : ?>
                    <p class="text-danger small mb-0"><?= error('fromEmail') ?></p>
                <?php endif ?>
            </x-setting-row>
        </x-admin-box>

        <x-save-bar save="<?= esc(lang('Bonfire.saveSettings'), 'attr') ?>" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
