<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class OnlineCheck implements FilterInterface
{
    /**
     * Checks the Site.siteOnline setting (via site_offline()). While the
     * site is offline, redirects to /site-offline, which renders
     * Site::$siteOfflineView. Superadmins and users with the
     * site.viewOffline permission get through.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['setting', 'auth']);

        if (site_offline()) {
            $user = auth()->user();

            if ($user === null || (
                $user !== null
                && ! $user->inGroup('superadmin')
                && ! $user->hasPermission('site.viewOffline')
            )) {
                return redirect()->to('/site-offline');
            }
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
