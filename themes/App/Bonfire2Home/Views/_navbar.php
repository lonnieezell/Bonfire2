<!-- Navbar -->
<nav class="navbar sticky-top navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="<?= base_url('bonfire2_for_ci4.svg')?>" height=64 alt="Site Logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav nav-pills ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-2 active" aria-current="page" href="#welcome">Welcome</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" href="#philosophy">Philosophy</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" href="#installation">Installation</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" href="#third-party">Software Used</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2" href="<?= route_to('login') ?>">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>