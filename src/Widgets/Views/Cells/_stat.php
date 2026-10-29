<div class="bf-stat">
    <div class="bf-stat-label">
        <i class="<?= esc($stat->faIcon()) ?>" aria-hidden="true"></i>
        <h3><?= esc($stat->title()) ?></h3>
    </div>
    <p class="bf-stat-value"><?= $stat->value() ?></p>
	<?php if ($showLink) : ?>
        <a class="bf-stat-link" href="<?= site_url($stat->url()) ?>"><?= lang('Widgets.viewDetail') ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
	<?php endif?>
</div>
