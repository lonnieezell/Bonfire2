<?php

namespace Bonfire\Consent\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ConsentFilter implements FilterInterface
{
    /**
     * Nothing to do prior to a controller running.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        //
    }

    /**
     * If enabled, insert the view and the styles/scripts
     * into the view file.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Consent system disabled?
        if (! setting('Consent.requireConsent')) {
            return;
        }

        // Get the existing consent cookie
        if (! function_exists('get_cookie')) {
            helper('cookie');
        }

        $cookie = get_cookie('bf_consent');
        $permissions = json_decode($cookie, true);

        // Do we already have consent from the visitor?
        // then nothing to do here...
        if ($permissions['consent'] ?? false) {
            return;
        }

        // Insert the consent form
        $html = view(setting('Consent.consentForm'), [
            'consents' => setting('Consent.consents'),
            'message' => setting('Consent.consentMessage'),
        ]);
        // Replace {policy_url} with the actual link.
        $link = setting('Consent.policyUrl');
        $link = strpos('http', (string)$link) === 0
            ? $link
            : site_url($link);
        $html = str_ireplace('{policy_url}', "<a href='{$link}' target='_blank'>Cookie policy</a>", $html);

        $cssFile = setting('Consent.consentFormStyles');
        $jsFile = setting('Consent.consentFormScripts');

        $css = ! empty($cssFile) ? view($cssFile) : null;
        $js = ! empty($jsFile) ? view($jsFile) : null;

        $output = $response->getBody();

        $output = str_replace('</head>', "{$css}\n</head>", $output);
        $output = str_replace('</body>', "{$html}\n</body>", $output);
        $output = str_replace('</body>', "{$js}\n</body>", $output);

        $response->setBody($output);
        return $response;
    }
}
