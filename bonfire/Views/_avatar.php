<span style="width: <?= $size ?>px; height: <?= $size ?>px; font-size: <?= $fontSize ?>px; background-color: <?= $background ?>" class="avatar" title="<?= $user->name() ?>">
    <?php if($user->avatarLink() !== '') : ?>
        <img src="<?= $user->avatarLink(120) ?>" alt="<?= $user->name() ?>">
    <?php else :?>
        <?= $idString ?>
    <?php endif ?>
</span>
