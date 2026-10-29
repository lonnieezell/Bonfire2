<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Adds the default grants modules declare in AuthGroups::$moduleGrants
 * to the permissions matrix, each one once.
 */
class Permissions extends BaseCommand
{
    protected $group       = 'Bonfire';
    protected $name        = 'bonfire:permissions';
    protected $description = "Adds modules' default permission grants to the groups.";
    protected $usage       = 'bonfire:permissions';

    public function run(array $params)
    {
        $matrix  = setting('AuthGroups.matrix') ?? [];
        $applied = $before = setting('AuthGroups.appliedGrants') ?? [];
        $groups  = setting('AuthGroups.groups') ?? [];
        $added   = 0;

        foreach (config('AuthGroups')->moduleGrants as $module => $grants) {
            foreach ($grants as $group => $permissions) {
                if (! isset($groups[$group])) {
                    CLI::write("Skipped {$module} grants for unknown group \"{$group}\".", 'yellow');

                    continue;
                }

                // A grant applied before stays out if an admin has since removed it
                $new   = array_diff($permissions, $applied[$group] ?? []);
                $toAdd = array_diff($new, $matrix[$group] ?? []);

                $matrix[$group]  = [...$matrix[$group] ?? [], ...$toAdd];
                $applied[$group] = [...$applied[$group] ?? [], ...$new];
                $added += count($toAdd);
            }
        }

        // Saving stores the whole matrix, after which config changes to it are ignored
        $changes = $added > 0 ? ['AuthGroups.matrix' => $matrix] : [];

        if ($applied !== $before) {
            $changes['AuthGroups.appliedGrants'] = $applied;
        }

        service('settings')->setMany($changes);

        CLI::write("Added {$added} permission grant(s).", 'green');
    }
}
