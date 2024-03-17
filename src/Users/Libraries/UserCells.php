<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Libraries;

/**
 * Provides view cells for Users
 */
class UserCells
{
    protected $viewPrefix = 'Bonfire\Users\Views\\';

    public string $configClass = 'Users';

    /**
     * Displays the form fields for user meta fields.
     */
    public function metaFormFields()
    {
        return view($this->viewPrefix . 'meta/list', [
            'fieldGroups' => setting("{$this->configClass}.metaFields"),
        ]);
    }
}
