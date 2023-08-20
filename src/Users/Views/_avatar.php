<div class="row">
    <div class="col-12 d-flex justify-content-center">
        <?= isset($user) ? $user->renderAvatar(140) : (new \Bonfire\Users\User())->renderAvatar(140) ?>
    </div>
    <div class="col-12 mt-4">
        <?php if (!empty($user->avatar)) : ?>
            <p class="small"><a href="<?= url_to('user-avatar-delete', $user->id) ?>" hx-confirm="Are you sure you wish to remove the image? It cannot be restored" hx-get="<?= url_to('user-avatar-delete', $user->id) ?>" hx-target="#avatar-place"> Delete uploaded image</a>
        <?php endif; ?>
        <input type="file" class="form-control btn-upload" name="avatar" accept="image/*" />
    </div>
</div>
