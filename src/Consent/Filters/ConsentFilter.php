<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ConsentFilter implements FilterInterface
{
    /**
     * Nothing to do prior to a controller running.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    /**
     * If enabled, insert the view and the styles/scripts
     * into the view file.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        helper('setting');

        // Consent system disabled?
        if (! setting('Consent.requireConsent')) {
            return;
        }

        helper('consent');

        // Shown until the visitor has answered every consent;
        // otherwise hidden, for consent_settings_link() to open.
        $choices  = consent_choices();
        $consents = setting('Consent.consents');

        foreach ($consents as &$consent) {
            $consent['name'] = $this->text($consent['name']);
            $consent['desc'] = $this->text($consent['desc']);
        }
        unset($consent);

        $html = view(setting('Consent.consentForm'), [
            'consents' => $consents,
            'choices'  => $choices,
            'show'     => empty($choices['consent']) || array_diff_key($consents, $choices) !== [],
            'message'  => $this->text(setting('Consent.consentMessage')),
        ]);
        // Replace {policy_url} with the actual link.
        $link = setting('Consent.policyUrl');
        $link = str_starts_with((string) $link, 'http')
            ? $link
            : site_url($link);
        $policyTitle = esc($this->text(setting('Consent.policyTitle')));
        $html        = str_ireplace('{policy_url}', "<a href='{$link}' target='_blank'>{$policyTitle}</a>", $html);

        $cssFile = setting('Consent.consentFormStyles');
        $jsFile  = setting('Consent.consentFormScripts');

        $css = empty($cssFile) ? null : view($cssFile);
        $js  = empty($jsFile) ? null : view($jsFile);

        $output = $response->getBody();

        $output = str_replace('</head>', "{$css}\n</head>", $output);
        $output = str_replace('</body>', "{$html}\n</body>", $output);
        $output = str_replace('</body>', "{$js}\n</body>", $output);

        $response->setBody($output);

        return $response;
    }

    /**
     * A value shaped like a language key (File.key) is translated;
     * anything else is literal text from a published config.
     */
    private function text(string $value): string
    {
        return preg_match('/^\w+(\.\w+)+$/', $value) ? lang($value) : $value;
    }
}
