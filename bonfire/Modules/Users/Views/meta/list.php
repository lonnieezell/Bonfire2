<?php if (isset($fieldGroups) && count($fieldGroups)) : ?>
    <?php foreach ($fieldGroups as $group => $fields) : ?>
        <fieldset>
            <legend><?= esc($group) ?></legend>

            <?php foreach ($fields as $field => $info) : ?>
                <?php if ($info['type'] === 'textarea') : ?>

                <?php elseif ($info['type'] === 'number') : ?>

                <?php else : ?>
                    <div class="form-group col-12 col-sm-6">
                        <?php if (! isset($info['label'])) : ?>
                            <label for="meta[<?= $field ?>]" class="form-label"><?= esc(ucwords(strtolower(str_replace(['-', '_'], ' ', $field)))) ?></label>
                        <?php else : ?>
                            <label for="meta[<?= $field ?>]" class="form-label"><?= esc($info['label']) ?></label>
                        <?php endif ?>
                        <input type="text" name="meta[<?= strtolower($field) ?>]" class="form-control" autocomplete="<?= strtolower($field) ?>"
                               value="<?= old('meta['.strtolower($field).']', $user->meta(strtolower($field)) ?? '') ?>">
                        <?php if (has_error('meta.'. $field)) : ?>
                            <p class="text-danger"><?= error('meta.'. $field) ?></p>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            <?php endforeach ?>
        </fieldset>
    <?php endforeach ?>
<?php endif ?>
