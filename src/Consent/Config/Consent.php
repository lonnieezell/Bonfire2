<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent\Config;

use CodeIgniter\Config\BaseConfig;

class Consent extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Require Consent?
     * --------------------------------------------------------------------------
     *
     * If true, will ask the users in the frontend to opt-in to different
     * cookie consent types (required, performance, tracking, etc)
     *
     * If false, then those cookies won't be set.
     */
    public bool $requireConsent = false;

    /**
     * --------------------------------------------------------------------------
     * How Long Consent Lasts?
     * --------------------------------------------------------------------------
     *
     * This determines how long a consent will be considered approved for, in days.
     */
    public int $consentLength = 180;

    /**
     * --------------------------------------------------------------------------
     * Consent Form View
     * --------------------------------------------------------------------------
     *
     * The view and  CSS/JS that will be used that displays the consent form.
     *
     * Both $consentFormStyles and $consentFormScripts can be left null to
     * have them not included, in case you would rather include them in a
     * build system, etc.
     */
    public string $consentForm = 'Bonfire\Consent\Views\consent_form';

    public string $consentFormStyles  = 'Bonfire\Consent\Views\consent_styles';
    public string $consentFormScripts = 'Bonfire\Consent\Views\consent_scripts';

    /**
     * --------------------------------------------------------------------------
     * Cookie Policy URL
     * --------------------------------------------------------------------------
     *
     * A full URL, or a relative link on the site, that displays the cookie policy.
     * This might be incorporated into the Privacy Policy,
     * but may also be separate.
     */
    public string $policyUrl = 'privacy';

    /**
     * --------------------------------------------------------------------------
     * Cookie Policy Document Title
     * --------------------------------------------------------------------------
     *
     * A title of the document that contains the cookie policy.
     * For example, 'Privacy Policy' or 'Cookie Policy'.
     *
     * This and the other text below may be a language key (File.key),
     * which is translated, or literal text.
     */
    public string $policyTitle = 'Consent.policyLinkTitle';

    /**
     * --------------------------------------------------------------------------
     * Cookie Message
     * --------------------------------------------------------------------------
     *
     * The initial message displayed to the visitor in the consent form.
     */
    public string $consentMessage = 'Consent.message';

    /**
     * --------------------------------------------------------------------------
     * Available consents
     * --------------------------------------------------------------------------
     *
     * The list of the types of consents that the user has to provide answers
     * for. Each one can be accepted or rejected separately from the others,
     * except a `locked` one, which is always accepted.
     *
     * Visitors who answered before are asked again when a new key is added.
     * To change what an existing consent is for, give it a new key.
     */
    public array $consents = [
        'required' => [
            'locked' => true,
            'name'   => 'Consent.requiredName',
            'desc'   => 'Consent.requiredDesc',
        ],
        'performance' => [
            'name' => 'Consent.performanceName',
            'desc' => 'Consent.performanceDesc',
        ],
        'targeting' => [
            'name' => 'Consent.targetingName',
            'desc' => 'Consent.targetingDesc',
        ],
    ];
}
