<?php
/**
 * The code and who asked for it, shared by the Markdown auth emails. PHP drops a
 * newline right after a closing tag, so blank lines after one are doubled.
 *
 * @var string                $code
 * @var array<string, string> $details Labels with their values as Markdown code spans
 */
?>
<mail-panel color="#f4f4f5">
# <?= $code ?>


</mail-panel>

**<?= lang('Auth.emailInfo') ?>**

<?php foreach ($details as $label => $value) : ?>
- **<?= $label ?>** <?= $value ?>

<?php endforeach ?>
