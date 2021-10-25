<?php

namespace Bonfire\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class OnlineCheck implements FilterInterface
{
    /**
     * Checks the App.siteOnline setting. If not `true`, will
     * stop script execution and display the Site Offline page.
     * This view is expected to be found in:
     *      app/Views/errors/offline.php
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['setting', 'auth']);

        if (! setting('App.siteOnline')) {
            $user = auth()->user();

            if ($user !== null && ! $user->inGroup('superadmin') && ! $user->hasPermission('site.viewOffline')) {
                return redirect()->to('site-offline');
            }
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
