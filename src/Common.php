<?php

use Bonfire\View\Theme;
use CodeIgniter\I18n\Time;

if (! function_exists('has_error')) {
    /**
     * Determines whether an error exists
     * for a form field. This requires the errors
     * are passed back like:
     *  return redirect()->back()->with('errors', $this->validation->getErrors());
     */
    function has_error(string $field): bool
    {
        if (! session()->has('errors')) {
            return false;
        }

        return isset(session('errors')[$field]);
    }
}

if (! function_exists('error')) {
    /**
     * Displays the error message for a single form field.
     */
    function error(string $field)
    {
        return session('errors')[$field] ?? '';
    }
}

if (! function_exists('app_date')) {
    /**
     * Formats a date according to the format
     * specified in the general settings page.
     *
     * It can take a string, a DateTime, or a Time instance.
     *
     * If $includeTimezone === true, will return the tz abbreviation
     * at the end of the date (i.e. CST, PST, etc)
     *
     * @param mixed $date
     *
     * @throws Exception
     */
    function app_date($date, bool $includeTime = false, bool $includeTimezone = false): string
    {
        $format = $includeTime
            ? [
                setting('App.dateFormat'),
                setting('App.timeFormat'),
                $includeTimezone ? 'T' : '',
            ]
            : [
                setting('App.dateFormat'),
                $includeTimezone ? 'T' : '',
            ];

        $format = trim(implode(' ', $format));

        if (is_string($date)) {
            $date = Time::parse($date);
        }

        $date->setTimezone(setting('App.appTimezone'));

        return $date->format($format);
    }
}

if (! function_exists('render')) {
    /**
     * Renders a view using the current theme.
     *
     * @param string $view
     * @param array  $data
     * @param array  $options
     *
     * @return string
     */
    function render(string $theme, string $view, array $data = [], array $options = [])
    {
        helper('assets');

        Theme::setTheme($theme);
        $path = Theme::path();

        $renderer = single_service('renderer', $path);

        $viewMeta         = service('viewMeta');
        $data['viewMeta'] = $viewMeta;

        return $renderer->setData($data)
            ->render($view, $options, true);
    }
}

if (! function_exists('site_offline')) {
    /**
     * Determines whether the site is offline.
     */
    function site_offline(): bool
    {
        return empty(setting('Site.siteOnline'));
    }
}
