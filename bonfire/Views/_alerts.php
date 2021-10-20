<aside id="<?= $prefix ?>wrapper">
    <?php
    foreach ($alerts as $alert):
        ?>
        <div role="alert" class="alert alert-<?= $alert['class'] ?> alert-dismissible fade show shadow">
            <?php if ($alert['class'] === 'info') : ?>
                <i class="fas fa-info-circle text-info mx-2"></i>
            <?php elseif ($alert['class'] === 'warning') : ?>
                <i class="fas fa-exclamation-circle text-warning mx-2"></i>
            <?php elseif ($alert['class'] === 'danger') : ?>
                <i class="fas fa-exclamation-triangle text-danger mx-2"></i>
            <?php elseif ($alert['class'] === 'success') : ?>
                <i class="fas fa-check-circle text-success mx-2"></i>
            <?php endif ?>
            <?= $alert['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
    endforeach;
    ?>
</aside>
