<?= $this->extend('Email/master') ?>

<?= $this->section('main') ?>

<div class="container-fluid">

  <div class="row">

      <div class="col-md-2"></div>

      <div class="col-md-10">
              <?= $body; ?>
      </div>

  </div>

</div>


<?= $this->endSection() ?>
