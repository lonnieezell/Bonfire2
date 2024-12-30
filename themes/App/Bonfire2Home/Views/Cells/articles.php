<?php /* the space in the next line before closing ` >` is needed as a workaround for bug #480 */ ?>
<x-sidebar-card title="Random Article (demo)" >
    <h5 class="position-relative"><span class="badge bg-success">
        <?= $articleCategory ?>:</span>
        <?= $articleTitle ?>
    </h5>
    <p class="card-text"><?= $articleExcerpt ?></p>
    <p class="mb-2 text-muted text-small"><?= $articleDate ?></p>
    <x-button-link href="https://github.com/dgvirtual/bonfire2-pages-module" color="secondary">"Pages" module</x-button-link>
</x-sidebar-card>