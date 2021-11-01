<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2>E-mail queue</h2>
    </x-page-head>

    <x-admin-box>
      <?php if (count($queue)) { ?>

          <div class="row">
              <!-- List Emails in Queue -->
              <div class="col" id="queue-list">
                  <?= $this->include('Bonfire\Modules\Email\Views\_actions_queue') ?>
                  <?= $this->include('Bonfire\Modules\Email\Views\_table') ?>
              </div>

          </div>

          <?php } else { ?>
          <div class="text-center">
             <i class="fas fa-inbox fa-3x my-3"></i><br/> <?= lang('Email.empty'); ?>
          </div>
          <?php } ?>

    </x-admin-box>


    <?php $this->endSection() ?>

    <?php $this->section('scripts') ?>

    <?php $this->endSection() ?>
