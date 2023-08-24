<?php $this->extend('master'); ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>
<x-unsplash>
    <div class="container d-flex justify-content-center p-5">
        <x-auth-card>
            <div class="card-body">
                <h5 class="card-title mb-5"><?= lang('Auth.register') ?></h5>

                <form action="<?= url_to('register') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Email -->
                    <div class="mb-2">
                        <input type="email" class="form-control" name="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required />
                    </div>

                    <!-- Username -->
                    <div class="mb-4">
                        <input type="text" class="form-control" name="username" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" required />
                    </div>

                    <div id="pass-suggestions"></div>

                    <div class="row mb-2">
                        <!-- Password -->
                        <div class="col">
                            <input type="password" class="form-control" name="password" id="password" autocomplete="password"
                                placeholder="<?= lang('Auth.password') ?>"
                                onkeyup="checkStrength(); debounceCheckPasswordMatch()" required
                            />
                        </div>
                        <!-- Password Meter -->
                        <div class="col-auto" style="margin-left: 0">
                            <div id="pass-meter">
                                <div class="segment segment-4"></div>
                                <div class="segment segment-3"></div>
                                <div class="segment segment-2"></div>
                                <div class="segment segment-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Password (Again) -->
                    <div class="row mb-5">
                        <div class="col">
                            <input type="password" class="form-control" name="password_confirm" id="pass_confirm" autocomplete="password_confirm"
                                placeholder="<?= lang('Auth.passwordConfirm') ?>" required onkeyup="debouncedCheckPasswordMatch()" />
                        </div>
                        <div class="col-auto pass-match-wrap">
                            <div class="pass-match" id="pass-match" style="display:none"><span>&check;</span></div>
                            <div class="pass-not-match" id="pass-not-match" style="display:none"><span>&times;</span></div>
                        </div>
                    </div>

                    <div class="d-grid col-23 mx-auto m-3">
                        <button type="submit" class="btn btn-primary btn-block btn-lg"><?= lang('Auth.register') ?></button>
                    </div>

                    <p class="text-center"><?= lang('Auth.haveAccount') ?> <a href="<?= url_to('login') ?>"><?= lang('Auth.login') ?></a></p>

                </form>
            </div>
        </x-auth-card>
    </div>
</x-unsplash>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= asset_link('auth/js/passStrength.js', 'js') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<?= $this->endSection() ?>
