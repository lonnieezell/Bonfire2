<aside id="<?= $prefix ?>wrapper">
    <?php
    foreach ($alerts as $alert):
        ?>
        <div role="alert" class="alert alert-<?= $alert['class'] ?> alert-dismissible fade show shadow-sm">
            <?= $alert['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
    endforeach;
    ?>
</aside>
