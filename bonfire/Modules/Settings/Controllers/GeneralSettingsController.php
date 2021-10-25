<?php

namespace Bonfire\Modules\Settings\Controllers;

use App\Controllers\AdminController;
use DateTimeZone;

/**
 * General Site Settings
 */
class GeneralSettingsController extends AdminController
{
    /**
     * The theme to use.
     *
     * @var string
     */
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Settings\Views\\';

    /**
     * Displays the site's general settings.
     */
    public function general()
    {
        $timezoneAreas = [];
        foreach(timezone_identifiers_list() as $timezone) {
            if (strpos($timezone, '/') === false) {
                $timezoneAreas[] = $timezone;
                continue;
            }

            [$area, $zone] = explode('/', $timezone);
            if (! in_array($area, $timezoneAreas)) {
                $timezoneAreas[] = $area;
            }
        }

        $currentTZ = setting('App.appTimezone');
        $currentTZArea = strpos($currentTZ, '/') === false
            ? $currentTZ
            : substr($currentTZ, 0, strpos($currentTZ, '/'));

        echo $this->render($this->viewPrefix .'general', [
            'timezones' => $timezoneAreas,
            'currentTZArea' => $currentTZArea,
            'timezoneOptions' => $this->getTimezones($currentTZArea),
        ]);
    }

    /**
     * Saves the general settings
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function saveGeneral()
    {
        $rules = [
            'siteName' => 'required|string',
            'timezone' => 'required|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('App.siteName', $this->request->getPost('siteName', FILTER_SANITIZE_STRING));
        setting('App.siteOnline', $this->request->getPost('siteOnline') === '1');
        setting('App.appTimezone', $this->request->getPost('timezone'));

        return redirect()->to(ADMIN_AREA .'/settings/general')->with('message', lang('Bonfire.resourcesSaved', ['settings']));
    }

    /**
     * AJAX method to list available timezones within
     * a single timezone area  (AMERICA, AFRICA, etc)
     *
     * @param string|null $area
     *
     * @return string
     */
    public function getTimezones(string $area=null): string
    {
        $area = $area === null
            ? $this->request->getVar('timezoneArea')
            : $area;
        $ids = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Arctic' => DateTimeZone::ARCTIC,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
        ];

        $options = [];

        if($area === 'UTC') {
            $options[] = ['UTC' => 'UTC'];
        }
        else {
            foreach(timezone_identifiers_list($ids[$area]) as $timezone) {
                $formattedTimezone = str_replace('_', ' ', $timezone);
                $formattedTimezone = str_replace($area.'/', '', $formattedTimezone);
                $options[$timezone] = $formattedTimezone;
            }
        }

        return view($this->viewPrefix .'_timezones', [
            'options' => $options,
            'selectedTZ' => setting('App.appTimezone'),
        ]);
    }
}
