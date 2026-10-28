<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Widgets\WidgetOptions;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class WidgetsSettingsController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Widgets\Views\\';

    /**
     * Display the Widgets settings page.
     */
    public function index()
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        return $this->render($this->viewPrefix . 'settings', [
            'items' => service('widgets')->items(),
            'tabs'  => WidgetOptions::all(),
        ]);
    }

    public function show(string $alias)
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $tabs = WidgetOptions::all();

        if (! isset($tabs[$alias])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->render($this->viewPrefix . 'defaults', [
            'tabs'    => $tabs,
            'options' => $tabs[$alias],
        ]);
    }

    /**
     * Saves the Widgets settings to the config file, where it
     * is automatically saved by our dynamic configuration system.
     */
    public function save(): RedirectResponse
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $options = WidgetOptions::forAlias((string) $this->request->getVar('widget'));

        if ($options === null) {
            $this->saveWidgetSettings();
        } else {
            $options->save($this->request);
        }

        alert('success', 'The settings have been saved.');

        return redirect()->to($this->request->getUserAgent()->getReferrer());
    }

    /**
     * Return the image with preview scheme colors.
     */
    public function getColorSchemePreview()
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $scheme = (string) $this->request->getGet('colorScheme');

        if ($scheme === 'null' || ! array_key_exists($scheme, lang('Widgets.colorSchemes'))) {
            return '';
        }

        return '<img src="/assets/admin/img/color_scheme/' . $scheme . '.png" style="height:40px; width:300px;" alt="">';
    }

    /**
     * Saves which widgets are enabled, where it is automatically
     * saved by our dynamic configuration system.
     */
    private function saveWidgetSettings(): void
    {
        foreach (service('widgets')->items() as $item) {
            $settings = $item->settings();
            $settings->store($this->request->getPost($settings->fieldName()) !== null);
        }
    }

    /**
     * Reset all the widget settings to their default values
     */
    public function resetSettings(): RedirectResponse
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        foreach (service('widgets')->items() as $item) {
            $item->settings()->forget();
        }

        foreach (WidgetOptions::all() as $options) {
            $options->reset();
        }

        alert('success', 'The settings have been reset.');

        return redirect()->route('widgets-settings');
    }

    /**
     * Forgets the stored site default layout, so users who have not
     * saved their own see the configured Dashboard::$layout again.
     */
    public function resetSiteLayout(): ResponseInterface
    {
        if (! auth()->user()->can('widgets.settings')) {
            return $this->response->setStatusCode(403);
        }

        setting()->forget('Dashboard.layout');

        alert('success', 'The site default dashboard has been reset.');

        return redirect()->route('widgets-settings');
    }
}
