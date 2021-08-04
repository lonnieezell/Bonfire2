<?php

if (! function_exists('has_error')) {
    /**
     * Determines whether an an error exists
     * for a form field. This requeires the errors
     * are passed back like:
     *  return redirect()->back()->with('errors', $this->validation->getErrors());
     *
     * @param string $field
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
     *
     *
     * @param string $field
     */
    function error(string $field)
    {
        return session('errors')[$field] ?? '';
    }
}

if (! function_exists('setting')) {
    /**
     * Provides a convenience interface to the
     * Bonfire/Settings/Settings class.
     *
     * @param string|null $class
     * @param string|null $field
     */
    function setting(string $class=null, string $field=null)
    {
        $setting = service('settings');

        if (empty($class) || empty($field)) {
            return $setting;
        }

        return $setting->get($class, $field);
    }
}
