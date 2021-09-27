<div style="width: <?= $size ?>px; height: <?= $size ?>px; font-size: <?= $fontSize ?>px; background-color: <?= $background ?>" class="avatar">
    <?php if($user->avatarLink() !== '') : ?>
        <img src="<?= $user->avatarLink(120) ?>" alt="<?= $user->name() ?>">
    <?php else :?>
        <?= $idString ?>
    <?php endif ?>
</div>
