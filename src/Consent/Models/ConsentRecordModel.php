<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent\Models;

use CodeIgniter\Model;

/**
 * One row per consent saved: the cookie's consent_id, the choices
 * (JSON, alias => 0|1) and the user, when one was signed in.
 */
class ConsentRecordModel extends Model
{
    protected $table         = 'consent_records';
    protected $returnType    = 'array';
    protected $allowedFields = ['consent_id', 'user_id', 'consents'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
