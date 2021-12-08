<div id="consent-popup" data-state="simple">
    <h1>Cookies</h1>
    <a href="#" id="consent-close">X</a>

    <div class="show-simple">
        <p><?= esc($message) ?>

        <div>
            <a href="#" id="consent-reject-all">Reject Non-Essential</a>
            <a href="#" id="consent-accept-all">Accept All</a>
        </div>
    </div>

    <div class="customize-btn">
        <a href="#" id="consent-customize" class="consent-customize show-simple">
            <span class="show-simple">Customize Consent</span>
            <span class="show-custom">Simplify Consent</span>
        </a>
    </div>

    <div id="consents" class="show-custom">
        <table>
            <tbody>
            <?php foreach ($consents as $alias => $consent) : ?>
                <tr>
                    <td>
                        <input type="checkbox" name="<?= esc($alias, 'attr') ?>" class="consent-check"
                            <?php if ($alias === 'required') : ?> checked disabled <?php endif ?>
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
            <a href="#" id="consent-accept-custom">Accept Selected</a>
        </div>
    </div>
</div>
