<?= $this->extend('master') ?>

<?= $this->section('main') ?>

    <div class="p-5 mb-4 bg-light border rounded">
        <div class="py-5">
            <h1 class="display-5 fw-bold">Welcome to Bonfire</h1>

            <p class="col-md-8 fs-4">
                A pre-made admin area with tools to accelerate your web application development.
                Made with <span class="text-danger">&hearts;</span> by developers, for developers.
            </p>

            <a href="https://github.com/lonnieezell/Bonfire2/" class="btn btn-lg btn-primary" target="_blank">
                <svg height="32" viewBox="0 0 32 32" width="32" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M16.003 0C7.17 0 .008 7.162.008 15.997c0 7.067 4.582 13.063 10.94 15.179.8.146 1.052-.328 1.052-.752 0-.38.008-1.442 0-2.777-4.449.967-5.371-2.107-5.371-2.107-.727-1.848-1.775-2.34-1.775-2.34-1.452-.992.109-.973.109-.973 1.605.113 2.451 1.649 2.451 1.649 1.427 2.443 3.743 1.737 4.654 1.329.146-1.034.56-1.739 1.017-2.139-3.552-.404-7.286-1.776-7.286-7.906 0-1.747.623-3.174 1.646-4.292-.165-.404-.715-2.031.157-4.234 0 0 1.343-.43 4.398 1.641a15.31 15.31 0 0 1 4.005-.538c1.359.006 2.727.183 4.005.538 3.055-2.07 4.396-1.641 4.396-1.641.872 2.203.323 3.83.159 4.234 1.023 1.118 1.644 2.545 1.644 4.292 0 6.146-3.74 7.498-7.304 7.893C19.479 23.548 20 24.508 20 26v4.428c0 .428.258.901 1.07.746C27.422 29.055 32 23.062 32 15.997 32 7.162 24.838 0 16.003 0z" fill="#ffffff" fill-rule="evenodd"/></svg>
                <span class="mx-2">Visit us at GitHub</span>
            </a>
        </div>
    </div>

    <div class="row align-items-md-stretch">
        <div class="col-md-6">
            <div class="h-100 p-5 text-white bg-dark rounded-3">
                <h2 class="mb-3">How to Install</h2>
                <p class="font-monospace text-success"><span class="text-light">&rang;</span> composer create-project lonnieezell/Bonfire2 app</p>
                <p class="font-monospace text-success"><span class="text-light">&rang;</span> cd app</p>
                <p class="font-monospace text-success"><span class="text-light">&rang;</span> php spark bf:install</p>
                <p class="font-monospace text-success"><span class="text-light">&rang;</span> php spark serve</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="h-100 p-5 bg-light border rounded-3">
                <?php if (auth()->loggedIn()) : ?>
                    <h2>Done in the Admin?</h2>

                    <p>Sign out and clean up your tracks.</p>

                    <a href="/logout" class="btn btn-outline-secondary">Sign Out</a>
                <?php else : ?>
                    <h2>Jump On In</h2>

                    <p>Your admin area is expecting you. <br>You don't want to keep it waiting, do you?</p>

                    <a href="/login" class="btn btn-outline-secondary">Sign In</a>
                <?php endif ?>

            </div>
        </div>
    </div>

<?= $this->endSection() ?>

