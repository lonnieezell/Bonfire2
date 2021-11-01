

<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <a href="/<?= ADMIN_AREA ?>/content/email" class="back">&larr; E-mail queue</a>
        <h2>E-mail preview</h2>
    </x-page-head>

    <x-admin-box>

      <dl class="row">
        <dt class="col-sm-2">Created</dt>
        <dd class="col-sm-4"><?= $email['created_at']; ?></dd>
        <dt class="col-sm-2">Sent</dt>
        <dd class="col-sm-4"><?= ($email['sent'] == 1)? '<span class="badge rounded-pill bg-success">'.$email['sent_at'].'</span>':'<span class="badge rounded-pill bg-secondary">No</span>'; ?></dd>
        <dt class="col-sm-2">From</dt>
        <dd class="col-sm-10"><?= esc($email['from_email']); ?></dd>
        <dt class="col-sm-2">Subject</dt>
        <dd class="col-sm-10"><?= esc($email['subject']); ?></dd>
        <dt class="col-sm-2">To</dt>
        <dd class="col-sm-10"><?= esc($email['email']); ?></dd>
      </dl>

      <?= $email['message']; ?>

    </x-admin-box>


    <?php $this->endSection() ?>

    <?php $this->section('scripts') ?>

    <?php $this->endSection() ?>
