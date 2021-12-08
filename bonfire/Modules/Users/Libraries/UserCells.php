<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
        return view($this->viewPrefix . 'meta/list', [
            'fieldGroups' => setting('Users.metaFields'),
        ]);
    }
}
