<?php
/**
 * An action's fields for the confirm dialog, which swaps them in. The token is the current one, since a post that was
 * refused rotated it, and the dialog copies it to the page's forms.
 *
 * @var list<array> $sections what Bonfire\Fields\Form::sections() returns
 */
?>
<input type="hidden" name="<?= esc(csrf_token(), 'attr') ?>" value="<?= esc(csrf_hash(), 'attr') ?>" data-csrf>
<?= view('Bonfire\Fields\Views\form_fields', ['sections' => $sections], ['saveData' => false]) ?>
