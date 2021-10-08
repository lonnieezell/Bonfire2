<?php

namespace Bonfire\Modules\Users\Libraries;

/**
 * Provides view cells for Users
 */
class UserCells
{
    protected $viewPrefix = 'Bonfire\Modules\Users\Views\\';

    /**
     * Displays the form fields for user meta fields.
     */
    public function metaFormFields()
    {
        return view($this->viewPrefix .'meta/list', [
            'fieldGroups' => setting('Users.metaFields'),
        ]);
    }
}
