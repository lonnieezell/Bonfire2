<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.useMagicLink') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

<x-unsplash>
    <div class="container d-flex justify-content-center p-5">
        <x-auth-card>
            <div class="card-body">
                <h5 class="card-title mb-4"><?= lang('Auth.useMagicLink') ?></h5>

                <form action="<?= url_to('magic-link') ?>" method="post">
                    <?= csrf_field() ?>

                    <p class="text-muted mb-4"><?= lang('Bonfire.magicLinkInfo') ?></p>

                    <!-- Email -->
                    <div class="mb-5">
                        <input type="email" class="form-control" name="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>"
                            value="<?= old('email', auth()->user()->email ?? null) ?>" required />
                    </div>

                    <div class="d-grid col-8 mx-auto m-3">
                        <button type="submit" class="btn btn-primary btn-block"><?= lang('Auth.useMagicLink') ?></button>
                    </div>

                </form>
            </div>
        </x-auth-card>
    </div>
</x-unsplash>

<?= $this->endSection() ?>
