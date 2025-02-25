<button
    <?= isset($onclick) ? 'onclick="' . $onclick . '"' : '' ?>
    type="<?= $type ?? 'submit' ?>"
    class="btn btn-<?= $color ?? 'primary' ?> btn-lg"
    <?= isset($id) ? 'id="' . $id . '"' : '' ?>
    <?= isset($name) ? 'name="' . $name . '"' : '' ?>
>
    <?= $slot ?? '' ?>
</button>
