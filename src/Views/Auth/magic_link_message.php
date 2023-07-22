<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.useMagicLink') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

<x-unsplash>
    <div class="container d-flex justify-content-center p-5">
        <x-auth-card>
            <div class="card-body">
                <h5 class="card-title mb-5"><?= lang('Auth.useMagicLink') ?></h5>

                <p><b><?= lang('Auth.checkYourEmail') ?></b>!</p>

                <p><?= lang('Auth.magicLinkDetails', [setting('Auth.magicLinkLifetime') / 60]) ?></p>

                </form>
            </div>
        </x-auth-card>
    </div>
</x-unsplash>

<?= $this->endSection() ?>
