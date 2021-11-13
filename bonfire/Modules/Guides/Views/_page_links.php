<div class="row guide-pages my-4">
    <div class="col text-left">
    <?php if (! empty($previousLink)) : ?>
        <a href="<?= esc($previousLink, 'attr') ?>">&larr; <?= $previousTitle ?></a>
    <?php endif ?>
    </div>
    <div class="col d-flex justify-content-end">
    <?php if (! empty($nextLink)) : ?>
        <a href="<?= esc($nextLink, 'attr') ?>"><?= $nextTitle ?> &rarr;</a>
    <?php endif ?>
    </div>
</div>
