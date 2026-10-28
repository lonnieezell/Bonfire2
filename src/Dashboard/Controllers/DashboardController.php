<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Dashboard\LayoutResolver;
use Bonfire\Dashboard\LayoutValidator;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;
use JsonException;

/**
 * Class Dashboard
 *
 * The primary entry-point to the Bonfire admin area.
 */
class DashboardController extends AdminController
{
    /**
     * Displays the site's initial page.
     */
    public function index()
    {
        service('viewMeta')->addScript([
            'src'            => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js',
            'integrity'      => 'sha256-bQqDH8GbS66FF5etM5MVfoYa+3hiRZwRImNZsn4sQzc=',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ]);

        $dashboard = service('dashboard');
        $resolver  = new LayoutResolver($dashboard);

        echo $this->render('Bonfire\Dashboard\Views\dashboard', [
            'layout'        => $resolver->resolve(),
            'defaultLayout' => $resolver->defaultLayout(),
            'widgets'       => $dashboard->available(),
            'palette'       => $dashboard->grouped(),
        ]);
    }

    /**
     * Stores the posted layout (JSON) as the current user's own. The
     * default layout is not stored, so they keep following the default.
     */
    public function saveLayout(): ResponseInterface
    {
        $dashboard = service('dashboard');

        try {
            $payload = json_decode($this->request->getBody() ?? '', true, 512, JSON_THROW_ON_ERROR);
            $layout  = (new LayoutValidator($dashboard))->validate($payload);
        } catch (InvalidArgumentException|JsonException) {
            // The CSRF filter has rotated the token; the editor needs it to try again
            return $this->response->setStatusCode(400)->setJSON(['saved' => false, 'csrf' => csrf_hash()]);
        }

        if ($layout->toArray() === (new LayoutResolver($dashboard))->defaultLayout()->toArray()) {
            setting()->forget('Dashboard.layout', LayoutResolver::userContext());
        } else {
            setting()->set('Dashboard.layout', $layout->toArray(), LayoutResolver::userContext());
        }

        return $this->response->setJSON(['saved' => true]);
    }
}
