<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head back="<?= esc(url_to('mail-previews'), 'attr') ?>" backLabel="<?= esc(lang('Email.previewsModTitle'), 'attr') ?>" title="<?= esc($preview->subject ?? $preview->class, 'attr') ?>" subtitle="<?= esc($preview->class, 'attr') ?>"></x-page-head>

<?php if ($preview->error !== null) : ?>
    <x-admin-box>
        <div class="bf-callout bf-callout-warn" data-preview-error><?= esc($preview->error->getMessage()) ?></div>
    </x-admin-box>
<?php else : ?>
    <x-admin-box title="<?= esc(lang('Email.previewSendToMe'), 'attr') ?>" description="<?= esc(lang('Email.previewSendToMeDesc', [$address]), 'attr') ?>">
        <form action="<?= url_to('mail-preview-send', $slug) ?>" method="post" class="d-flex gap-2" data-send-to-me>
            <?= csrf_field() ?>
            <select name="mailer" class="form-select bf-input-narrow" aria-label="<?= esc(lang('Email.previewMailer'), 'attr') ?>">
                <?php foreach ($mailers as $mailer) : ?>
                    <option value="<?= esc($mailer, 'attr') ?>"<?= $mailer === $defaultMailer ? ' selected' : '' ?>><?= esc($mailer) ?></option>
                <?php endforeach ?>
            </select>
            <x-button><?= lang('Email.previewSendToMe') ?></x-button>
        </form>
    </x-admin-box>

    <div x-data="{ tab: '<?= $preview->htmlBody === null ? 'text' : 'html' ?>' }">
        <x-tabs>
            <?php if ($preview->htmlBody !== null) : ?>
                <li class="nav-item"><a class="nav-link" href="#" :class="{ active: tab === 'html' }" @click.prevent="tab = 'html'"><?= lang('Email.previewTabHtml') ?></a></li>
            <?php endif ?>
            <li class="nav-item"><a class="nav-link" href="#" :class="{ active: tab === 'text' }" @click.prevent="tab = 'text'"><?= lang('Email.previewTabText') ?></a></li>
            <li class="nav-item"><a class="nav-link" href="#" :class="{ active: tab === 'raw' }" @click.prevent="tab = 'raw'"><?= lang('Email.previewTabRaw') ?></a></li>
        </x-tabs>

        <x-admin-box>
            <?php if ($preview->htmlBody !== null) : ?>
                <div x-show="tab === 'html'">
                    <iframe sandbox="" srcdoc="<?= esc($preview->htmlBody, 'attr') ?>" title="<?= esc(lang('Email.previewTabHtml'), 'attr') ?>" class="w-100 border-0" style="height: 70vh"></iframe>
                </div>
            <?php endif ?>
            <pre class="mb-0" x-show="tab === 'text'" x-cloak data-tab-panel="text"><?= esc($preview->text) ?></pre>
            <pre class="mb-0" x-show="tab === 'raw'" x-cloak data-tab-panel="raw"><?= esc($preview->rawMime) ?></pre>
        </x-admin-box>
    </div>
<?php endif ?>
<?php $this->endSection() ?>
