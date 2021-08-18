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
