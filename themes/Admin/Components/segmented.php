<?php
// A radio group styled as a segmented control. `options` is a JSON object of value => label.
$choices = json_decode($options ?? '{}', true) ?? [];
?>
<div class="bf-segmented" role="radiogroup"<?php if (isset($label)) : ?> aria-label="<?= esc($label) ?>"<?php endif ?>>
    <?php foreach ($choices as $choiceValue => $choiceLabel) : ?>
        <label class="bf-segment">
            <input type="radio" name="<?= esc($name) ?>" value="<?= esc($choiceValue) ?>"<?= (string) $choiceValue === ($value ?? null) ? ' checked' : '' ?>>
            <span><?= esc($choiceLabel) ?></span>
        </label>
    <?php endforeach ?>
</div>
