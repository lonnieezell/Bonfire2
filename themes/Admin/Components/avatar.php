<div style="width: 120px; height: 120px" class="border border-3 rounded-circle">
    <?php if(isset($user) && $user->avatarLink() !== '') : ?>
        <img src="<?= $user->avatarLink(120) ?>" alt="<?= $user->name() ?>">
    <?php endif ?>
</div>
