<a href="<?= $href ?>" <?= isset($onclick) ? 'onclick="' . $onclick . '"' : '' ?> class="btn btn-<?= $color ?? 'primary' ?>">
    <?= $slot ?? '' ?>
</a>