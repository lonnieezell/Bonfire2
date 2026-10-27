<button
    <?= isset($onclick) ? 'onclick="' . esc($onclick) . '"' : '' ?>
    type="<?= esc($type ?? 'submit') ?>"
    class="btn btn-<?= esc($color ?? 'primary') ?>"
    <?= isset($id) ? 'id="' . esc($id) . '"' : '' ?>
    <?= isset($name) ? 'name="' . esc($name) . '"' : '' ?>
>
    <?= $slot ?? '' ?>
</button>
