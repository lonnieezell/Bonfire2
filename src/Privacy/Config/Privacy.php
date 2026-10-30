<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Privacy\Config;

use CodeIgniter\Config\BaseConfig;

class Privacy extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Retention
     * --------------------------------------------------------------------------
     *
     * How many days `spark bf:privacy:prune` keeps personal data for,
     * or null to keep it forever.
     *
     * - logins:      login attempts (auth_logins), with their IP address
     * - tokenLogins: access token logins (auth_token_logins)
     * - consents:    consent records replaced by a newer answer, or whose
     *                consent has expired (see Consent::$consentLength)
     *
     * @var array<string, int|null>
     */
    public array $retention = [
        'logins'      => 90,
        'tokenLogins' => 90,
        'consents'    => 365,
    ];
}
