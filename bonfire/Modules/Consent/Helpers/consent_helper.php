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

        if (! function_exists('get_cookie')) {
            helper('cookie');
        }

        $cookie = get_cookie('bf_consent');

        if (empty($cookie)) {
            return false;
        }

        $permissions = json_decode($cookie, true);

        if (! is_array($permissions) || ! array_key_exists($group, $permissions)) {
            return false;
        }

        // They must have given permission at one time...
        if (! isset($permissions['consent']) || $permissions['consent'] === 0) {
            return false;
        }

        return (bool) $permissions[$group];
    }
}
