<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<div x-data="{ copied: false, copy() { navigator.clipboard?.writeText(this.$refs.info.innerText).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1500) }) } }">
    <x-page-head title="<?= esc(lang('Tools.systemInfoModTitle'), 'attr') ?>">
        <a href="<?= site_url(ADMIN_AREA . '/tools/php-info') ?>" class="btn btn-outline-secondary" target="_blank"><?= lang('Tools.viewPhpInfo') ?></a>
        <button type="button" class="btn btn-outline-secondary" @click="copy()">
            <i class="far fa-copy" aria-hidden="true"></i> <span x-text="copied ? <?= esc(json_encode(lang('Tools.copied')), 'attr') ?> : <?= esc(json_encode(lang('Tools.copyAsText')), 'attr') ?>"><?= lang('Tools.copyAsText') ?></span>
        </button>
    </x-page-head>

    <?php if ($pruneStale) : ?>
        <div class="bf-callout bf-callout-warn" id="privacy-prune-warning">
            <?= $lastPruned === null ? lang('Tools.pruneNeverRun') : lang('Tools.pruneStale', [$lastPruned->humanize()]) ?>
        </div>
    <?php endif ?>

    <div class="bf-sysinfo" x-ref="info">
        <x-admin-box title="<?= esc(lang('Tools.server'), 'attr') ?>">
            <?php foreach ($server as $key => $value) : ?>
                <div class="bf-kv">
                    <div class="bf-kv-key"><?= esc($key) ?></div>
                    <div class="bf-kv-value bf-mono"><?= esc($value) ?></div>
                </div>
            <?php endforeach ?>
        </x-admin-box>

        <x-admin-box title="<?= esc(lang('Tools.filesystem'), 'attr') ?>">
            <?php if (! $allWritable) : ?>
                <div class="bf-callout bf-callout-warn bf-callout-flush">
                    <?= lang('Tools.notWritableWarning') ?>
                </div>
            <?php endif ?>
            <div class="bf-kv">
                <div class="bf-kv-key"><i class="far fa-file" aria-hidden="true"></i> <span class="bf-mono">.env</span></div>
                <div class="bf-kv-value">
                    <x-status tone="<?= $envPresent ? 'ok' : 'warn' ?>" label="<?= esc(lang($envPresent ? 'Tools.present' : 'Tools.missing'), 'attr') ?>" />
                </div>
            </div>
            <?php foreach ($files as $file) : ?>
                <div class="bf-kv">
                    <div class="bf-kv-key">
                        <i class="far <?= $file['dir'] ? 'fa-folder' : 'fa-file' ?>" aria-hidden="true"></i> <span class="bf-mono"><?= esc($file['name']) ?></span>
                        <?php if (! $file['dir']) : ?>
                            <div class="bf-muted small"><?= number_to_size($file['size']) ?> &middot; <?= lang('Bonfire.lastModified') ?> <?= date('Y-m-d H:i', $file['modified']) ?></div>
                        <?php endif ?>
                    </div>
                    <div class="bf-kv-value">
                        <x-status tone="<?= $file['writable'] ? 'ok' : 'danger' ?>" label="<?= esc(lang($file['writable'] ? 'Tools.writable' : 'Tools.notWritable'), 'attr') ?>" />
                    </div>
                </div>
            <?php endforeach ?>
        </x-admin-box>
    </div>
</div>
<?php $this->endSection() ?>
