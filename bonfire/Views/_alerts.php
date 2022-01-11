
    <?php foreach ($alerts as $alert): ?>
    <?php [$class, $content] = $alert; ?>
        <div role="alert" class="alert alert-<?= $class ?> alert-dismissible fade show shadow">
            <?php if ($class === 'info'): ?>
                <i class="fas fa-info-circle text-info mx-2"></i>
            <?php elseif ($class === 'warning'): ?>
                <i class="fas fa-exclamation-circle text-warning mx-2"></i>
            <?php elseif ($class === 'danger'): ?>
                <i class="fas fa-exclamation-triangle text-danger mx-2"></i>
            <?php elseif ($class === 'success'): ?>
                <i class="fas fa-check-circle text-success mx-2"></i>
            <?php else: ?>
                <i class="fas fa-check-circle text-<?= $class ?> mx-2"></i>
            <?php endif ?>

            <?= $content ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
