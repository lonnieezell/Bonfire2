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

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Model;

/**
 * Generates a resource for an existing model, with a field for each column of its table, and the module
 * files that wire the resource into the admin.
 */
class ResourceGenerator extends BaseCommand
{
    use FindsNamespaceFolders;
    use RendersTemplates;

    private const PAGES = ['list', 'create', 'edit'];

    protected $group       = 'Bonfire';
    protected $name        = 'bf:resource';
    protected $description = 'Generates a resource, with its module, routes and permissions, for a model.';
    protected $usage       = 'bf:resource <name> --model <model class> [options]';
    protected $arguments   = [
        'name' => 'The record in singular: "Product".',
    ];
    protected $options = [
        '--model'     => 'The model class whose table the resource describes. Required. A name with no namespace is looked up in App\Models.',
        '--namespace' => 'The module\'s namespace. Default: "App\Modules\{plural name}".',
        '--pages'     => 'The pages the resource has, separated by commas. Default: "list,create,edit".',
    ];

    public function run(array $params)
    {
        helper('inflector');

        $options = $this->options($params);
        $name    = ucfirst((string) ($params[0] ?? ''));

        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            CLI::error('Give the resource a name made of letters and digits: bf:resource Product --model=ProductModel');

            return 1;
        }

        $modelClass = $this->modelClass((string) ($options['model'] ?? ''));
        if (! is_subclass_of($modelClass, Model::class)) {
            CLI::error("--model must name a model class, and \"{$modelClass}\" is not one.");

            return 1;
        }

        $model = new $modelClass();
        // The list of tables may be from before the table was made.
        $model->db->resetDataCache();

        if ($model->table === '' || ! $model->db->tableExists($model->table)) {
            CLI::error("{$modelClass} has no table: \"{$model->table}\" does not exist in the database.");

            return 1;
        }

        $pages = $this->pages((string) ($options['pages'] ?? implode(',', self::PAGES)));
        if ($pages === []) {
            CLI::error('--pages takes one or more of ' . implode(', ', self::PAGES) . ', separated by commas.');

            return 1;
        }

        [$columns, $skipped] = $this->columns($model);
        if ($columns === []) {
            CLI::error("{$modelClass}'s table has no column to put in a form.");

            return 1;
        }

        $entity    = $name;
        $module    = plural($entity);
        $namespace = trim((string) ($options['namespace'] ?? APP_NAMESPACE . '\Modules\\' . $module), '\\');
        $path      = $this->namespacePath($namespace);

        if ($path === null) {
            CLI::error("No folder is mapped to the namespace {$namespace}. Add its root to \$psr4 in app/Config/Autoload.php.");

            return 1;
        }

        $data  = $this->templateData($entity, $module, $namespace, $model, $columns, $pages);
        $files = [
            $path . $data['class'] . '.php' => 'resource',
            $path . 'Module.php'            => 'module',
            $path . 'Config/Routes.php'     => 'routes',
            $path . 'Config/Registrar.php'  => 'registrar',
        ];

        $byHand = [];

        foreach ($files as $file => $template) {
            if (is_file($file)) {
                CLI::write('Left ' . clean_path($file) . ' alone, because it exists.', 'yellow');
                $byHand[$template] = $file;

                continue;
            }

            if (! is_dir(dirname($file)) && ! @mkdir(dirname($file), 0775, true) && ! is_dir(dirname($file))) {
                CLI::error('Could not create ' . clean_path(dirname($file)) . '.');

                return 1;
            }

            if (@file_put_contents($file, $this->renderTemplate(__DIR__ . "/Views/resource/{$template}.tpl.php", $data)) === false) {
                CLI::error('Could not write ' . clean_path($file) . '.');

                return 1;
            }

            CLI::write('Created ' . clean_path($file), 'green');
        }

        $this->byHand($byHand, $data);
        $this->nextSteps($namespace, $module, $model, $skipped);

        return 0;
    }

    /**
     * The options by name. CodeIgniter keeps `--model=ProductModel` as an option named `model=ProductModel`.
     *
     * @return array<string, string|null>
     */
    private function options(array $params): array
    {
        $options = [];

        foreach ($params as $key => $value) {
            if (is_string($key) && str_contains($key, '=')) {
                [$key, $value] = explode('=', $key, 2);
            }

            if (is_string($key)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    private function modelClass(string $model): string
    {
        $model = ltrim($model, '\\');

        return $model !== '' && ! str_contains($model, '\\') ? APP_NAMESPACE . '\Models\\' . $model : $model;
    }

    /**
     * @return list<string> the pages named, or none when one isn't a page
     */
    private function pages(string $pages): array
    {
        $pages = array_values(array_unique(array_filter(array_map(trim(...), explode(',', $pages)))));

        return array_diff($pages, self::PAGES) === [] ? $pages : [];
    }

    /**
     * The columns that get a field: those the model can save, except its key and its timestamps.
     *
     * @return array{list<object>, list<string>} the columns, and the names of those left out because they aren't in `$allowedFields`
     */
    private function columns(Model $model): array
    {
        $columns = [];
        $skipped = [];
        $own     = [$model->createdField, $model->updatedField, $model->deletedField];

        foreach ($model->db->getFieldData($model->table) as $column) {
            if ($column->primary_key || in_array($column->name, $own, true)) {
                continue;
            }

            if ($model->protectFields && ! in_array($column->name, $model->allowedFields, true)) {
                $skipped[] = $column->name;

                continue;
            }

            $columns[] = $column;
        }

        return [$columns, $skipped];
    }

    /**
     * @param list<object> $columns
     * @param list<string> $pages
     */
    private function templateData(string $entity, string $module, string $namespace, Model $model, array $columns, array $pages): array
    {
        $slug    = dasherize(decamelize($module));
        $plural  = strtolower(humanize(decamelize($module)));
        $imports = ['Bonfire\Resources\Resource', $model::class];
        $fields  = [];

        foreach ($columns as $column) {
            [$lines, $import] = $this->field($column);

            array_push($fields, ...$lines);

            if ($import !== null) {
                $imports[] = 'Bonfire\Resources\Fields\\' . $import;
            }
        }

        $imports = array_values(array_unique($imports));
        sort($imports);

        $permissions = [
            'list'   => "Can view the list of {$plural}",
            'create' => "Can create {$plural}",
            'edit'   => "Can edit {$plural}",
            'delete' => "Can delete {$plural}",
        ];

        // Deleting isn't a page, so every resource may have it.
        $permissions = array_filter($permissions, static fn (string $verb): bool => $verb === 'delete' || in_array($verb, $pages, true), ARRAY_FILTER_USE_KEY);

        return [
            'namespace'   => $namespace,
            'class'       => $entity . 'Resource',
            'modelShort'  => substr(strrchr('\\' . $model::class, '\\'), 1),
            'slug'        => $slug,
            'pages'       => $pages,
            'imports'     => $imports,
            'fields'      => $fields,
            'permissions' => array_combine(array_map(static fn (string $verb): string => "{$slug}.{$verb}", array_keys($permissions)), $permissions),
        ];
    }

    /**
     * The lines of PHP that declare a column's field, and the builder they use.
     *
     * @return array{list<string>, string|null}
     */
    private function field(object $column): array
    {
        $handle = $column->name;

        if (str_ends_with($handle, '_id')) {
            $related = str_replace(' ', '', ucwords(str_replace('_', ' ', substr($handle, 0, -3)))) . 'Model';
            $label   = ucfirst(str_replace('_', ' ', substr($handle, 0, -3)));

            $stub = "// BelongsTo::make('{$handle}')->label('{$label}')->model({$related}::class)->title('name'),";

            // A column the database needs a value for still gets an input, until the stub replaces it.
            if (! $column->nullable && $column->default === null) {
                return [[
                    "// {$handle} looks like a relation. To pick the record from a list, import BelongsTo and {$related}, and replace the field with:",
                    $stub,
                    "Integer::make('{$handle}')->validate('required'),",
                ], 'Integer'];
            }

            return [[
                "// {$handle} looks like a relation. To use it, import BelongsTo and {$related}, and uncomment:",
                $stub,
            ], null];
        }

        preg_match('/^\s*([a-z]+)(?:\s*\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\))?/i', (string) $column->type, $match);

        $type   = strtolower($match[1] ?? '');
        $length = (int) ($match[2] ?? $column->max_length ?? 0);
        $scale  = isset($match[3]) ? (int) $match[3] : null;

        $builder = match (true) {
            in_array($type, ['tinytext', 'text', 'mediumtext', 'longtext'], true)                   => 'Textarea',
            in_array($type, ['bool', 'boolean'], true), $type === 'tinyint' && $length === 1        => 'Toggle',
            in_array($type, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true) => 'Integer',
            in_array($type, ['decimal', 'numeric'], true)                                           => 'Decimal',
            $type === 'date'                                                                        => 'Date',
            in_array($type, ['datetime', 'timestamp'], true)                                        => 'Datetime',
            default                                                                                 => 'Text',
        };

        $rules = [];

        if ($builder !== 'Toggle' && ! $column->nullable && $column->default === null) {
            $rules[] = 'required';
        }

        if ($builder === 'Text' && in_array($type, ['varchar', 'char'], true) && $length > 0) {
            $rules[] = "max_length[{$length}]";
        }

        $call = "{$builder}::make('{$handle}')";

        if ($builder === 'Decimal' && $scale !== null) {
            $call .= "->places({$scale})";
        }

        if ($rules !== []) {
            $call .= "->validate('" . implode('|', $rules) . "')";
        }

        return [[$call . ','], $builder];
    }

    /**
     * Says what to add to the files that were already there.
     *
     * @param array<string, string> $byHand the files left alone, by template
     */
    private function byHand(array $byHand, array $data): void
    {
        $steps = [
            'module'    => "Add this to initAdmin() in Module.php:\n    service('resources')->register({$data['class']}::class);",
            'routes'    => "Add this to Config/Routes.php:\n    resource_routes(\$routes, {$data['class']}::class);",
            'registrar' => "Add these to the 'permissions' of AuthGroups() in Config/Registrar.php, and a 'moduleGrants' entry for '{$data['slug']}' like it:\n"
                . implode("\n", array_map(
                    static fn (string $permission, string $description): string => "    '{$permission}' => '{$description}',",
                    array_keys($data['permissions']),
                    $data['permissions'],
                )),
        ];

        foreach ($steps as $template => $step) {
            if (isset($byHand[$template])) {
                CLI::newLine();
                CLI::write($step);
            }
        }

        if (isset($byHand['resource'])) {
            CLI::newLine();
            CLI::write("{$data['class']} was left as it is, so check that its fields match the table.");
        }
    }

    /**
     * Tells the developer what the resource still needs before it shows up.
     *
     * @param list<string> $skipped the columns that got no field
     */
    private function nextSteps(string $namespace, string $module, Model $model, array $skipped): void
    {
        $steps = [];

        if ($skipped !== []) {
            $steps[] = 'These columns have no field, because they are not in the model\'s $allowedFields: ' . implode(', ', $skipped) . '.';
        }

        array_push($steps, ...$this->setupSteps($namespace, $model, $module));
        $steps[] = 'Run php spark bonfire:permissions to grant the resource\'s permissions to the superadmin and admin groups.';

        $this->writeSteps($steps);
    }
}
