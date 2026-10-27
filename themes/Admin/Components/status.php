<?php $tone = in_array($tone ?? '', ['ok', 'warn', 'danger', 'info', 'muted'], true) ? $tone : 'muted' ?>
<span class="bf-status bf-status-<?= $tone ?>"><span class="bf-dot" aria-hidden="true"></span><?= esc($label) ?></span>
