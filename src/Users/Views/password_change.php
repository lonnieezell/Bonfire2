<form action="<?= $user->adminLink('/changePassword') ?>" method="post">

            <?= csrf_field() ?>

                <input type="hidden" name="id" value="<?= $user->id ?>">

                <div id="pass-suggestions"></div>

                <div class="row mb-2">
                    <!-- Password -->
                    <div class="col">
                        <label for="password" class="form-label"><?= lang('Auth.password') ?></label>
                        <input type="password" class="form-control" name="password" id="password" autocomplete="password"
                               placeholder="<?= lang('Auth.password') ?>"
                               onkeyup="checkStrength(); debouncedCheckPasswordMatch()" required
                        />
                    </div>
                    <!-- Password Meter -->
                    <div class="col-auto" style="margin-left: 0;padding-top: 2.5rem">
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
                        <label for="pass_confirm" class="form-label"><?= lang('Auth.passwordConfirm') ?></label>
                        <input type="password" class="form-control" name="pass_confirm" id="pass_confirm" autocomplete="pass_confirm"
                               placeholder="<?= lang('Auth.passwordConfirm') ?>" required onkeyup="debouncedCheckPasswordMatch()" />
                    </div>
                    <div class="col-auto pass-match-wrap">
                        <div class="pass-match" id="pass-match" style="display:none; padding-top:2.5rem;"><span>&check;</span></div>
                        <div class="pass-not-match" id="pass-not-match" style="display:none; padding-top:2.5rem;"><span>&times;</span></div>
                    </div>
                </div>

                <x-button-container>
                    <x-button>Update Password</x-button>
                </x-button-container>

        </form>
