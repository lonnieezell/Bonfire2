<?php $this->extend('master'); ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>
<x-unsplash>
    <div class="container d-flex justify-content-center p-5">
        <x-auth-card>
            <div class="card-body">
                <h5 class="card-title mb-5"><?= lang('Auth.register') ?></h5>

                <form action="<?= url_to('register') ?>" method="post" x-data="{ showPassword: false, password: '' }">
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
                        <div class="col pass-eye-parent">
                            <div class="pass-eye pass-eye-register" x-on:click="showPassword = !showPassword">
                                <i x-bind:class="showPassword ? 'fa-eye-slash' : 'fa-eye'" class="fa fa-regular"></i>
                            </div>
                            <input type="password" class="form-control" name="password" id="password" autocomplete="password"
                                placeholder="<?= lang('Auth.password') ?>" value=""
                                x-on:keyup="checkStrength(); debouncedCheckPasswordMatch()" x-model:value="password" x-bind:type="showPassword ? 'text' : 'password'" required
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
                    <div class="row mb-5" x-show="!showPassword">
                        <div class="col">
                            <input x-bind:disabled="showPassword" type="password" class="form-control" name="password_confirm" id="pass_confirm" 
                                autocomplete="password_confirm" placeholder="<?= lang('Auth.passwordConfirm') ?>" required x-on:keyup="debouncedCheckPasswordMatch()"
                            />
                            <!--hidden input in case the first one is disabled-->
                            <input type="hidden" name="password_confirm" value="" x-bind:disabled="!showPassword" x-model:value="password">
                        </div>
                        <div class="col-auto pass-match-wrap">
                            <div class="pass-match" id="pass-match" style="display:none"><i class="fa-regular fa-circle-check"></i></div>
                            <div class="pass-not-match" id="pass-not-match" style="display:none"><i class="fa-regular fa-circle-xmark"></i></div>
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
