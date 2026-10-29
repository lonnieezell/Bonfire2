<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions\Models;

use Bonfire\Actions\RunStatus;
use CodeIgniter\Model;

/**
 * Queued action runs. `status` is a RunStatus; `message` is what the run
 * reported when it ended.
 */
class ActionRunModel extends Model
{
    protected $table         = 'action_runs';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['list', 'action', 'user_id', 'ids', 'total', 'processed', 'skipped', 'status', 'message'];
    protected array $casts   = [
        'status' => 'enum[' . RunStatus::class . ']',
    ];
}
