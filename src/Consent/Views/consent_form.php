<form id="consent-popup" data-state="simple" method="post" action="<?= url_to('consent-save') ?>"<?= $show ? '' : ' hidden' ?>>
    <?= csrf_field() ?>
    <h1><?= lang('Consent.cookies') ?></h1>
    <button type="submit" name="choice" value="close" id="consent-close" aria-label="<?= esc(lang('Consent.close'), 'attr') ?>">X</button>

    <div class="show-simple">
        <p><?= esc($message) ?></p>

        <div>
            <button type="submit" name="choice" value="reject" id="consent-reject-all"><?= lang('Consent.rejectAll') ?></button>
            <button type="submit" name="choice" value="accept" id="consent-accept-all"><?= lang('Consent.acceptAll') ?></button>
        </div>
    </div>

    <div class="customize-btn">
        <button type="button" id="consent-customize" class="consent-customize">
            <span class="show-simple"><?= lang('Consent.customize') ?></span>
            <span class="show-custom"><?= lang('Consent.simplify') ?></span>
        </button>
    </div>

    <div id="consents" class="show-custom">
        <table>
            <tbody>
            <?php foreach ($consents as $alias => $consent) : ?>
                <tr>
                    <td>
                        <input type="checkbox" name="consents[<?= esc($alias, 'attr') ?>]" value="1" class="consent-check"
                            <?php if (! empty($consent['locked'])) : ?> checked disabled<?php elseif (! empty($choices[$alias])) : ?> checked<?php endif ?>
                        >
                    </td>
                    <td>
                        <p class="consent-title"><b><?= esc($consent['name']) ?></b></p>
                        <p class="consent-desc"><?= esc($consent['desc']) ?></p>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>

        <div style="text-align: center">
            <button type="submit" name="choice" value="custom" id="consent-accept-custom"><?= lang('Consent.acceptSelected') ?></button>
        </div>
    </div>
</form>
