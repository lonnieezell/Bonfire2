<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
if (! function_exists('has_consent')) {
    /**
     * Has the visitor given consent to
     * a certain group of cookies/functionality?
     *
     * The consent has been stored in the `bf_consent`
     * cookie which stores a 0/1 value for each
     * defined consent.
     */
    function has_consent(string $group): bool
    {
        // If the consent system is disabled,
        // then we always act as if consent is granted
        if (! setting('Consent.requireConsent')) {
            return true;
        }

        $choices = consent_choices();

        // They must have given permission at one time...
        return ! empty($choices['consent']) && ! empty($choices[$group]);
    }
}

if (! function_exists('consent_choices')) {
    /**
     * The visitor's saved answers from the `bf_consent` cookie:
     * `consent` (1 once answered), `consent_id`, and 0/1 per alias.
     * Empty when they haven't answered.
     *
     * @return array<string, int|string>
     */
    function consent_choices(): array
    {
        $values = json_decode((string) service('superglobals')->cookie(config('Cookie')->prefix . 'bf_consent'), true);

        if (! is_array($values)) {
            return [];
        }

        if (! preg_match('/^[0-9a-f]{32}$/', (string) ($values['consent_id'] ?? ''))) {
            unset($values['consent_id']);
        }

        return $values;
    }
}

if (! function_exists('consent_settings_link')) {
    /**
     * A link that reopens the consent form, so visitors can change
     * or withdraw their consent. Empty while consent is off.
     */
    function consent_settings_link(?string $label = null): string
    {
        if (! setting('Consent.requireConsent')) {
            return '';
        }

        return '<a href="#consent-popup" data-consent-open>' . esc($label ?? lang('Consent.settingsLink')) . '</a>';
    }
}
