<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent\Controllers;

use Bonfire\Consent\Models\ConsentRecordModel;
use Bonfire\Core\BaseController;

class ConsentController extends BaseController
{
    /**
     * Saves the visitor's answer to the consent form: sets the cookie,
     * records it, and redirects back so has_consent() checks take
     * effect on the reloaded page.
     */
    public function save()
    {
        $choice  = $this->request->getPost('choice');
        $checked = (array) $this->request->getPost('consents');
        $current = consent_choices();

        // Locked consents can't be rejected. Closing the form keeps
        // what the visitor accepted before and rejects the rest.
        $values = [];

        foreach (setting('Consent.consents') as $alias => $consent) {
            $values[$alias] = (int) (! empty($consent['locked']) || match ($choice) {
                'accept' => true,
                'custom' => ! empty($checked[$alias]),
                'close'  => ! empty($current[$alias]),
                default  => false,
            });
        }

        $consentId = $current['consent_id'] ?? bin2hex(random_bytes(16));

        model(ConsentRecordModel::class)->insert([
            'consent_id' => $consentId,
            'user_id'    => auth()->id(),
            'consents'   => json_encode($values),
        ]);

        return redirect()->back()->setCookie(
            'bf_consent',
            json_encode(['consent' => 1, 'consent_id' => $consentId] + $values),
            (int) setting('Consent.consentLength') * DAY,
            path: '/',
            secure: $this->request->isSecure(),
            httponly: true,
            samesite: 'Lax',
        );
    }
}
