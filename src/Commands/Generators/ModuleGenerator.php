<?php

namespace Bonfire\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Model;

/**
 * Generates an admin module that lists, creates, edits and deletes
 * the records of an existing model, and a feature test for it.
 */
class ModuleGenerator extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Bonfire';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:module';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates an admin module to list, create, edit and delete the records of a model.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:module <name> --model <model class> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The module name, in plural: "Articles".',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--model'     => 'The model class whose records the module manages. Required.',
        '--namespace' => 'The module\'s namespace. Default: "App\Modules\{name}".',
        '--force'     => 'Overwrite existing files.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        helper('inflector');

        $name = ucfirst((string) ($params[0] ?? ''));
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            CLI::error('Give the module a name made of letters and digits: make:module Articles --model App\Models\ArticleModel');

            return 1;
        }

        $modelClass = ltrim((string) ($params['model'] ?? ''), '\\');
        if (! is_subclass_of($modelClass, Model::class)) {
            CLI::error("--model must name a model class, and \"{$modelClass}\" is not one.");

            return 1;
        }

        $model = new $modelClass();
        if ($model->returnType === 'array') {
            CLI::error("{$modelClass} returns arrays. Set its \$returnType to 'object' or an entity class: Bonfire's actions work on objects.");

            return 1;
        }

        $fields = array_values(array_diff($model->allowedFields, [$model->createdField, $model->updatedField, $model->deletedField]));
        if ($fields === []) {
            CLI::error("{$modelClass} has no \$allowedFields to put in the form.");

            return 1;
        }

        $namespace = trim((string) ($params['namespace'] ?? APP_NAMESPACE . '\Modules\\' . $name), '\\');
        $path      = $this->namespacePath($namespace);
        if ($path === null) {
            CLI::error("No folder is mapped to the namespace {$namespace}. Add its root to \$psr4 in app/Config/Autoload.php.");

            return 1;
        }

        $data  = $this->templateData($name, $namespace, $model, $fields);
        $tests = $this->rootPath() . 'tests/';
        $files = [
            $path . 'Module.php'                            => 'module',
            $path . 'Config/Routes.php'                     => 'routes',
            $path . 'Config/Registrar.php'                  => 'registrar',
            $path . "Controllers/{$data['controller']}.php" => 'controller',
            $path . "Actions/{$data['action']}.php"         => 'action',
            $path . 'Views/list.php'                        => 'list',
            $path . 'Views/_table.php'                      => 'table',
            $path . 'Views/form.php'                        => 'form',
            $tests . "feature/{$name}ModuleTest.php"        => 'test',
        ];

        $existing = array_filter(array_keys($files), is_file(...));
        if ($existing !== [] && ! array_key_exists('force', $params)) {
            CLI::error("Nothing was written. These files exist, so pass --force to overwrite them:\n" . implode("\n", $existing));

            return 1;
        }

        // The generated test extends it, but the app may have its own already.
        if (! is_file($tests . '_support/AdminTestCase.php')) {
            $files[$tests . '_support/AdminTestCase.php'] = 'admin_test_case';
        }

        foreach ($files as $file => $template) {
            if (! is_dir(dirname($file))) {
                mkdir(dirname($file), 0775, true);
            }
            file_put_contents($file, $this->render($template, $data));
            CLI::write('Created ' . clean_path($file), 'green');
        }

        $this->nextSteps($name, $namespace, $model);

        return 0;
    }

    /**
     * The project root, where the tests folder lives.
     */
    protected function rootPath(): string
    {
        return ROOTPATH;
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

    /**
     * @param list<string> $fields
     */
    private function templateData(string $name, string $namespace, Model $model, array $fields): array
    {
        $entity = singular($name);

        return [
            'namespace'   => $namespace,
            'name'        => $name,
            'entity'      => $entity,
            'title'       => humanize(decamelize($name)),
            'entityTitle' => humanize(decamelize($entity)),
            'plural'      => strtolower(humanize(decamelize($name))),
            'singular'    => strtolower(humanize(decamelize($entity))),
            'var'         => lcfirst($entity),
            'listVar'     => lcfirst($name),
            'slug'        => dasherize(decamelize($name)),
            'controller'  => $entity . 'Controller',
            'action'      => 'Delete' . $name,
            'modelClass'  => $model::class,
            'modelShort'  => substr(strrchr('\\' . $model::class, '\\'), 1),
            'primaryKey'  => $model->primaryKey,
            'fields'      => $fields,
            'listFields'  => array_slice($fields, 0, 3),
            'recycler'    => $model->useSoftDeletes ? array_slice($fields, 0, 2) : null,
        ];
    }

    /**
     * Renders a template from Views/module. Templates write <@php and <@=
     * for the tags the generated file keeps.
     */
    private function render(string $template, array $data): string
    {
        $render = static function (string $__file, array $__data): string {
            extract($__data);
            ob_start();

            include $__file;

            return ob_get_clean();
        };

        return str_replace(['<@php', '<@='], ['<?php', '<?='], $render(__DIR__ . "/Views/module/{$template}.tpl.php", $data));
    }

    /**
     * Tells the developer what the module still needs before it shows up.
     */
    private function nextSteps(string $name, string $namespace, Model $model): void
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
            $steps[] = "Add '{$model->deletedField}' to the model's \$allowedFields, so the Recycler can restore {$name}.";
        }

        $steps[] = 'Run php spark bonfire:permissions to grant the module\'s permissions to the superadmin and admin groups.';

        CLI::newLine();
        CLI::write('Next steps:', 'yellow');

        foreach ($steps as $i => $step) {
            CLI::write(($i + 1) . '. ' . $step);
        }
    }
}
