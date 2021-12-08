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
    public bool $requireConsent = true;

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
    public string $consentForm = 'Bonfire\Modules\Consent\Views\consent_form';

    public string $consentFormStyles  = 'Bonfire\Modules\Consent\Views\consent_styles';
    public string $consentFormScripts = 'Bonfire\Modules\Consent\Views\consent_scripts';

    /**
     * --------------------------------------------------------------------------
     * Cookie Policy URL
     * --------------------------------------------------------------------------
     *
     * A relative link on the site that displays the cookie policy.
     * This might be incorporated into the Privacy Policy,
     * but may also be separate.
     */
    public string $policyUrl = 'privacy';

    /**
     * --------------------------------------------------------------------------
     * Cookie Message
     * --------------------------------------------------------------------------
     *
     * The initial message displayed to the visitor in the consent form.
     */
    public string $consentMessage = 'This site uses cookies to distinguish you from other users. We may also use cookies to enhance your experience or provide more targeted advertising. For more information, please see our {policy_url}';

    /**
     * --------------------------------------------------------------------------
     * Available consents
     * --------------------------------------------------------------------------
     *
     * The list of the types of consents that the user has to provide answers
     * for. Each one can be accepted or rejected seaparte from the others.
     */
    public array $consents = [
        'required' => [
            'name' => 'Functionality',
            'desc' => 'These cookies are required for the normal operation of this website.',
        ],
        'performance' => [
            'name' => 'Performance',
            'desc' => 'These cookies help us measure how visitors use the site, what the traffic sources are,
                and which pages are popular. This helps to improve the website for everyone. If you reject these
                we will be unable to use your visits to make site improvements.',
        ],
        'targeting' => [
            'name' => 'Targeting',
            'desc' => 'These are usually placed by third-party advertising networks, which may use information about
                your visits to develop a profile of your interests. This information may be shared with other networks
                or sites to deliver more relevant advertising across multiple sites.',
        ],
    ];
}
