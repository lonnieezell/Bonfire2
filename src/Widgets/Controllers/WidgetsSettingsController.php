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
use CodeIgniter\HTTP\RedirectResponse;

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
        ]);
    }

    public function show(string $alias)
    {
        if (! auth()->user()->can('widgets.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        return $this->render($this->viewPrefix . '_' . $alias, [
            'tab' => $alias,
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

        $req = 'null';

        if ($this->request->getVar('bar_colorScheme')) {
            $req = $this->request->getVar('bar_colorScheme');
        }
        if ($this->request->getVar('doughnut_colorScheme')) {
            $req = $this->request->getVar('doughnut_colorScheme');
        }
        if ($this->request->getVar('pie_colorScheme')) {
            $req = $this->request->getVar('pie_colorScheme');
        }
        if ($this->request->getVar('polarArea_colorScheme')) {
            $req = $this->request->getVar('polarArea_colorScheme');
        }

        if ($req !== 'null') {
            return '<img src="/assets/admin/img/color_scheme/' . $req . '.png" style="height:40px !important; width:300px; ';
        }

        return '';
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
}
