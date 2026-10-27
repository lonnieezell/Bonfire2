<section class="bf-card">
    <?php if (isset($title)) : ?>
        <header class="bf-card-head">
            <h2><?= esc($title) ?></h2>
            <?php if (isset($description)) : ?><p><?= esc($description) ?></p><?php endif ?>
        </header>
    <?php endif ?>
    <div class="bf-card-body">
        <?= $slot ?>
    </div>
</section>
