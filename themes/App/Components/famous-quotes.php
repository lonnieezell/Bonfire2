<div class="card mb-3">
    <div class="card-header">
        <?= esc($slot ?? 'Some Quotes') ?>
    </div>
    <div class="card-body mt-3">
        <p class="card-text text-center"><?= esc($quote['text']) ?></p>
        <footer class="blockquote-footer text-center"><em><?= esc($quote['author']) ?></em></footer>
    </div>
    <div class="card-footer" style="font-size:10px;">Cached for <?= esc($seconds) ?> seconds</div>
</div>