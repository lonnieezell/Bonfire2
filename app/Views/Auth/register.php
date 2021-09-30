<?= $this->extend('master') ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-5"><?= lang('Auth.register') ?></h5>

            <form action="<?= route_to('register') ?>" method="post">
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

                <div id="pass-meter">
                    <div class="segment segment-1"></div>
                    <div class="segment segment-2"></div>
                    <div class="segment segment-3"></div>
                    <div class="segment segment-4"></div>
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <input type="password" class="form-control" name="password" id="password" autocomplete="password"
                           placeholder="<?= lang('Auth.password') ?>"
                           onkeyup="checkStrength()" required
                    />
                </div>

                <!-- Password (Again) -->
                <div class="mb-5">
                    <input type="password" class="form-control" name="pass_confirm" autocomplete="pass_confirm" placeholder="<?= lang('Auth.passwordConfirm') ?>" required />
                </div>

                <div class="d-grid col-23 mx-auto m-3">
                    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= lang('Auth.register') ?></button>
                </div>

                <p class="text-center"><?= lang('Auth.haveAccount') ?> <a href="<?= route_to('login') ?>"><?= lang('Auth.login') ?></a></p>

            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function checkStrength() {
        let input = document.getElementById('password');
        let meter = document.getElementById('pass-meter');
        let suggestBox = document.getElementById('pass-suggestions');
        let info = zxcvbn(input.value);
        let state = null;

        // Remove previous states
        meter.classList.remove('bad', 'warn', 'good', 'str-1', 'str-2', 'str-3', 'str-4');

        switch(info.score) {
            case 1:
                state = 'bad';
                break;
            case 2:
            case 3:
                state = 'warn';
                break;
            case 4:
                state = 'good';
        }

        let score = 'str-'+ info.score.toString();

        meter.classList.add(state);
        meter.classList.add(score);
        suggestBox.innerText = info.feedback.suggestions.join(' ');

        console.log(info, state);
    }
</script>
<script src="/zxcvbn.js"></script>
<?= $this->endSection() ?>
