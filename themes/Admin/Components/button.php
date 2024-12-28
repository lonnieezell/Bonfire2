<button <?= isset($onclick) ? 'onclick="' . $onclick . '"' : '' ?> type="<?= $type ?? 'submit' ?>" class="btn btn-<?= $color ?? 'primary' ?> btn-lg">
    <?= $slot ?? '' ?>
</button>
