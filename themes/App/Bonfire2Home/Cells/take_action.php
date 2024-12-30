<?php /* the space in the next line before closing ` >` is needed as a workaround for bug #480 */ ?>
<x-sidebar-card title="What Are You Waiting For?" >
    <div class="row">
        <div class="col-12 mb-2">
            <a href="<?= route_to('register') ?>" class="btn btn-danger w-100 text-center">
                <i class="fas fa-user-plus me-2"></i> Register
            </a>
        </div>
        <div class="col-12">
            <a href="<?= route_to('login') ?>" class="btn btn-success w-100 text-center">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </a>
        </div>
    </div>
    <p class="fw-bold text-center my-3">... or continue exploring ...</p>
    <ul class="list">
        <li>Read Bonfire2 <a href="https://lonnieezell.github.io/Bonfire2/">documentation</a></li>
        <li>Get acquainted with <a href="https://codeigniter.com">Codeigniter4</a></li>
    </ul>
</x-sidebar-card>