<?php if (isset($fieldGroups) && count($fieldGroups)) : ?>
    <?php foreach ($fieldGroups as $group => $fields) : ?>
        <x-admin-box title="<?= esc($group) ?>">
        <div class="row">

            <?php foreach ($fields as $field => $info) : ?>
                <?php if ($info['type'] === 'checkbox') : ?>
                    <div class="form-check col-12 col-md-6 mt-3">
                        <input type="hidden" name="meta[<?= strtolower((string) $field) ?>]" value="false">
                        <input type="checkbox" class="form-check-input" name="meta[<?= strtolower((string) $field) ?>]"
                            value="true" <?= set_checkbox('meta.' . strtolower((string) $field), 'true', ($user->meta(strtolower((string) $field))) === 'true') ?>>
                        <?php if (!isset($info['label'])) : ?>
                            <label for="meta[<?= $field ?>]"
                                   class="form-check-label"><?= esc(ucwords(strtolower(str_replace(['-', '_'], ' ', $field)))) ?></label>
                        <?php else : ?>
                            <label for="meta[<?= $field ?>]" class="form-check-label"><?= esc($info['label']) ?></label>
                        <?php endif ?>
                    </div>
                <?php elseif ($info['type'] === 'textarea') : ?>
                    <div class="form-group col-12 col-lg-6">
                        <?php if (! isset($info['label'])) : ?>
                            <label for="meta[<?= $field ?>]" class="form-label"><?= esc(ucwords(strtolower(str_replace(['-', '_'], ' ', $field)))) ?></label>
                        <?php else : ?>
                            <label for="meta[<?= $field ?>]" class="form-label"><?= esc($info['label']) ?></label>
                        <?php endif ?>
                        <textarea class="form-control" rows="3" name="meta[<?= strtolower((string) $field) ?>]"
                            ><?= esc(old('meta.' . strtolower((string) $field), $user->meta(strtolower((string) $field)) ?? '')) ?></textarea>
                        <?php if (has_error('meta.' . $field)) : ?>
                            <p class="text-danger"><?= error('meta.' . $field) ?></p>
                        <?php endif ?>
                    </div>
                <?php elseif (in_array($info['type'], ['text', 'number', 'password', 'email', 'tel', 'url', 'date', 'time', 'week', 'month', 'color'])) : ?>
                    <div class="form-group col-12 col-md-6">
                        <?php if (! isset($info['label'])) : ?>
                            <label for="meta[<?= $field ?>]" class="form-label"><?= esc(ucwords(strtolower(str_replace(['-', '_'], ' ', $field)))) ?></label>
                        <?php else : ?>
                            <label for="meta[<?= $field ?>]" class="form-label"><?= esc($info['label']) ?></label>
                        <?php endif ?>
                            <input type="<?= $info['type'] ?>" name="meta[<?= strtolower((string) $field) ?>]" class="form-control" autocomplete="<?= strtolower((string) $field) ?>"
                                value="<?= esc(old('meta.' . strtolower((string) $field), $user->meta(strtolower((string) $field)) ?? '')) ?>">
                        <?php if (has_error('meta.' . $field)) : ?>
                            <p class="text-danger"><?= error('meta.' . $field) ?></p>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            <?php endforeach ?>
        </div>
        </x-admin-box>
    <?php endforeach ?>
<?php endif ?>
