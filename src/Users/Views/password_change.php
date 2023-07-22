<form action="<?= $user->adminLink('/changePassword') ?>" method="post">

            <?= csrf_field() ?>

                <input type="hidden" name="id" value="<?= $user->id ?>">

                <div id="pass-suggestions"></div>

                <div class="row mb-2">
                    <!-- Password -->
                    <div class="col">
                      <div class="form-group">
                        <label for="password" class="form-label"><?= lang('Auth.password') ?></label>
                        <input type="password" class="form-control" name="password" id="password" autocomplete="password"
                               placeholder="<?= lang('Auth.password') ?>"
                               onkeyup="checkStrength()" required
                        />
                      </div>
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
                      <div class="form-group">
                        <label for="pass_confirm" class="form-label"><?= lang('Auth.passwordConfirm') ?></label>
                        <input type="password" class="form-control" name="pass_confirm" id="pass_confirm" autocomplete="pass_confirm"
                               placeholder="<?= lang('Auth.passwordConfirm') ?>" required onkeyup="checkPasswordMatch()" />
                     </div>
                    </div>
                    <div class="col-auto pass-match-wrap">
                        <div class="pass-match" id="pass-match" style="display:none"><span>&check;</span></div>
                        <div class="pass-not-match" id="pass-not-match" style="display:none"><span>&times;</span></div>
                    </div>
                </div>

            <div class="text-end py-3">
                <input type="submit" value="Update Password" class="btn btn-primary btn-lg">
            </div>

        </form>
