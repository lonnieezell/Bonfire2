<span style="width: <?= $size ?>px; height: <?= $size ?>px; font-size: <?= $fontSize ?>px; background-color: <?= $background ?>"
      class="avatar overflow-hidden" title="<?= esc($user->name()) ?>"
>
    <?php if ($user->avatarLink() !== '') : ?>
        <img class="img-fluid" src="<?= $user->avatarLink($size) ?>" alt="<?= esc($user->name()) ?>">
    <?php else :?>
        <?= esc($idString) ?>
    <?php endif ?>
</span>
