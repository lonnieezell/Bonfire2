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
            'dateFormat' => setting('App.dateFormat') ?: 'M j, Y',
            'timeFormat' => setting('App.timeFormat') ?: 'g:i A',
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
            'dateFormat' => 'required|string',
            'timeFormat' => 'required|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('App.siteName', $this->request->getPost('siteName', FILTER_SANITIZE_STRING));
        setting('App.siteOnline', $this->request->getPost('siteOnline') === '1');
        setting('App.appTimezone', $this->request->getPost('timezone'));

        setting('App.dateFormat', $this->request->getPost('dateFormat'));
        setting('App.timeFormat', $this->request->getPost('timeFormat'));

        // Check for an logo to upload
        if ($file = $this->request->getFile('siteLogo')) {

            if ($file->isValid()) {

                $filename = 'custom.'. $file->getExtension();
                $logo_path = WRITEPATH .'uploads/logo';

                if ($file->move($logo_path, $filename, true)) {

                          service('image')
                          ->withFile($logo_path .'/'.$filename)
                          ->fit(300, 300, 'center')
                          ->save($logo_path .'/med_'.$filename);

                          service('image')
                          ->withFile($logo_path .'/'.$filename)
                          ->fit(60, 60, 'center')
                          ->save($logo_path .'/thumb_'.$filename);

                    setting('App.siteLogo', $filename);
                }
            }else{
              return redirect()->back()->with('error', lang('Bonfire.resourcesSaved', ['settings']). ' '. $file->getErrorString());
            }
        }

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
