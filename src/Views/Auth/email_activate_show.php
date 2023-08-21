<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.emailActivateTitle') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>
<x-unsplash>
    <div class="container d-flex justify-content-center p-5">
        <x-auth-card>
            <div class="card-body">
                <h5 class="card-title mb-5"><?= lang('Auth.emailActivateTitle') ?></h5>

                <p><?= lang('Auth.emailActivateBody') ?></p>

                <form action="<?= site_url('auth/a/verify') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Code -->
                    <div class="mb-2">
                        <input type="text" class="form-control" name="token" placeholder="000000" inputmode="numeric"
                            pattern="[0-9]*" autocomplete="one-time-code" value="<?= old('token') ?>" required />
                    </div>

                    <div class="d-grid col-8 mx-auto m-3">
                        <button type="submit" class="btn btn-primary btn-block"><?= lang('Auth.send') ?></button>
                    </div>

                </form>
            </div>
        </x-auth-card>
    </div>
</x-unsplash>

<?= $this->endSection() ?>
