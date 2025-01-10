<div class="card mb-3">
    <div class="card-header">Famous quotes, refreshed every <?= esc($seconds) ?> seconds</div>
    <div class="card-body mt-3">
        <p class="card-text text-center"><?= esc($quote) ?></p>
        <footer class="blockquote-footer text-center"><em><?= esc($author) ?></em></footer>
    </div>
</div>