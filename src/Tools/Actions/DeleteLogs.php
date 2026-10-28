<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Actions;

use Bonfire\Actions\Confirm;
use Bonfire\Actions\DeleteAction;
use Bonfire\Actions\Placement;
use Bonfire\Tools\Libraries\LogStore;
use RuntimeException;

/**
 * Deletes the checked log files.
 */
class DeleteLogs extends DeleteAction
{
    public string $permission   = 'logs.manage';
    public Placement $placement = Placement::Bulk;

    public function confirm(): ?Confirm
    {
        return Confirm::make(lang('Tools.deleteSelectedConfirm'))
            ->button(lang('Bonfire.delete'));
    }

    protected function delete(array $records): void
    {
        $names = array_map(static fn (object $log) => $log->id, $records);

        if ((new LogStore())->delete($names) !== count($names)) {
            throw new RuntimeException(lang('Tools.deleteError'));
        }
    }

    protected function deleted(): string
    {
        return lang('Tools.deleteSuccess');
    }
}
