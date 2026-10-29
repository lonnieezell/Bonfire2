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
use Bonfire\Dashboard\Layout;
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
            'canSetDefault' => auth()->user()->can('widgets.settings'),
        ]);
    }

    /**
     * Stores the posted layout (JSON) as the current user's own.
     */
    public function saveLayout(): ResponseInterface
    {
        $layout = $this->postedLayout();

        if ($layout === null) {
            return $this->rejected(400);
        }

        (new LayoutResolver(service('dashboard')))->saveForUser($layout);

        return $this->response->setJSON(['saved' => true]);
    }

    /**
     * Stores the posted layout (JSON) as the site default.
     */
    public function saveSiteLayout(): ResponseInterface
    {
        if (! auth()->user()->can('widgets.settings')) {
            return $this->rejected(403);
        }

        $layout = $this->postedLayout();

        if ($layout === null) {
            return $this->rejected(400);
        }

        (new LayoutResolver(service('dashboard')))->saveAsSiteDefault($layout);

        return $this->response->setJSON(['saved' => true]);
    }

    /**
     * Forgets the stored site default layout.
     */
    public function resetSiteLayout(): ResponseInterface
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        (new LayoutResolver(service('dashboard')))->resetSiteDefault();

        alert('success', lang('Bonfire.resetSiteDone'));

        return redirect()->to(ADMIN_AREA);
    }

    /**
     * The posted layout, validated, or null when it does not read.
     */
    private function postedLayout(): ?Layout
    {
        try {
            $payload = json_decode($this->request->getBody() ?? '', true, 512, JSON_THROW_ON_ERROR);

            return (new LayoutValidator(service('dashboard')))->validate($payload);
        } catch (InvalidArgumentException|JsonException) {
            return null;
        }
    }

    private function rejected(int $status): ResponseInterface
    {
        // The CSRF filter has rotated the token; the editor needs it to try again
        return $this->response->setStatusCode($status)->setJSON(['saved' => false, 'csrf' => csrf_hash()]);
    }
}
