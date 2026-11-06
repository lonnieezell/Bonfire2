<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Commands\Generators;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Model;

/**
 * Finds the folder a generator writes a module into, and says what the module needs once it is there.
 */
trait FindsNamespaceFolders
{
    /**
     * What a module that a generator wrote needs before Bonfire finds it.
     *
     * @param string $subject what the Recycler restores, such as the module's name
     *
     * @return list<string>
     */
    private function setupSteps(string $namespace, Model $model, string $subject): array
    {
        $steps = [];

        $modulesNamespace = substr($namespace, 0, (int) strrpos($namespace, '\\'));
        if (! array_key_exists($modulesNamespace, (array) config('Bonfire')->appModules)) {
            $steps[] = "Add '{$modulesNamespace}' to \$appModules in app/Config/Bonfire.php, so Bonfire finds the module.";
        }

        if (! isset(service('autoloader')->getNamespace()[$namespace])) {
            $steps[] = "Add '{$namespace}' to \$psr4 in app/Config/Autoload.php, so CodeIgniter reads the module's Config/Registrar.php.";
        }

        if ($model->useSoftDeletes && ! in_array($model->deletedField, $model->allowedFields, true)) {
            $steps[] = "Add '{$model->deletedField}' to the model's \$allowedFields, so the Recycler can restore {$subject}.";
        }

        return $steps;
    }

    /**
     * @param list<string> $steps
     */
    private function writeSteps(array $steps): void
    {
        CLI::newLine();
        CLI::write('Next steps:', 'yellow');

        foreach ($steps as $i => $step) {
            CLI::write(($i + 1) . '. ' . $step);
        }
    }

    /**
     * The folder of a namespace, through the closest namespace the
     * autoloader maps to a folder.
     */
    private function namespacePath(string $namespace): ?string
    {
        $namespaces = service('autoloader')->getNamespace();
        $prefix     = $namespace;
        $rest       = '';

        while (! isset($namespaces[$prefix])) {
            $pos = strrpos($prefix, '\\');
            if ($pos === false) {
                return null;
            }

            $rest   = substr($prefix, $pos) . $rest;
            $prefix = substr($prefix, 0, $pos);
        }

        return rtrim($namespaces[$prefix][0], '\/') . str_replace('\\', '/', $rest) . '/';
    }
}
