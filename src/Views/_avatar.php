<span style="width: <?= $size ?>px; height: <?= $size ?>px; font-size: <?= $fontSize ?>px; background-color: <?= $background ?>"
      class="avatar overflow-hidden" title="<?= $user->name() ?>"
>
    <?php if ($user->avatarLink() !== '') : ?>
        <img class="img-fluid" src="<?= $user->avatarLink($size) ?>" alt="<?= $user->name() ?>">
    <?php else :?>
        <?= $idString ?>
    <?php endif ?>
</span>
