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

use Bonfire\Content\Blueprint;
use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Field;
use Bonfire\Content\Fields\FieldTypes;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Writes starting views for a collection into the app, and a controller with --controller.
 * It never overwrites a file, and the files are the app's to change from then on.
 */
class ContentScaffold extends BaseCommand
{
    use RendersTemplates;

    /**
     * Names that tokenize as plain names but can't name a class.
     */
    private const RESERVED_CLASS_NAMES = ['bool', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null', 'object', 'parent', 'self', 'string', 'true', 'void'];

    protected $group       = 'Bonfire';
    protected $name        = 'content:scaffold';
    protected $description = 'Writes starting index and show views for a collection, from its blueprints.';
    protected $usage       = 'content:scaffold <collection> [--controller]';

    /**
     * @var array<string, string>
     */
    protected $arguments = [
        'collection' => 'The handle of the collection.',
    ];

    /**
     * @var array<string, string>
     */
    protected $options = [
        '--controller' => 'Write a controller for the collection to hand off to, as well as its views.',
    ];

    public function run(array $params)
    {
        helper('inflector');

        $config = config('Content');

        try {
            $collection = Collection::load($config->contentPath, (string) ($params[0] ?? ''));
            $fields     = $this->fields($collection, $config->blueprintPath);
        } catch (ContentException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        $handle     = $collection->handle;
        $controller = array_key_exists('controller', $params);
        $class      = pascalize(str_replace('-', '_', $handle));

        if ($controller && ! $this->isValidClassName($class)) {
            CLI::error("Can't name a controller {$class} after the collection {$handle}, because PHP doesn't allow that class name.");

            return EXIT_ERROR;
        }

        // The title and the body have their own places in the views.
        $body   = $fields === [] || isset($fields[Blueprint::BODY]);
        $fields = array_values(array_filter($fields, static fn (Field $field): bool => ! in_array($field->handle, ['title', Blueprint::BODY], true)
            && FieldTypes::get($field->type)->storedInFrontMatter()));

        $files = [
            APPPATH . "Views/{$handle}/index.php" => $this->render('index', [
                'title'  => $collection->title,
                'teaser' => $this->teaser($fields),
            ]),
            APPPATH . "Views/{$handle}/show.php" => $this->render('show', [
                'controller' => $controller,
                'fields'     => array_map(static fn (Field $field): array => [
                    'label' => $field->label,
                    'type'  => $field->type,
                    'key'   => var_export(FieldTypes::get($field->type)->key($field), true),
                ], $fields),
                'body' => $body,
            ]),
        ];

        $controllerClass   = APP_NAMESPACE . "\\Controllers\\{$class}";
        $controllerFile    = APPPATH . "Controllers/{$class}.php";
        $controllerSkipped = $controller && is_file($controllerFile);

        if ($controller) {
            $files[$controllerFile] = $this->render('controller', [
                'namespace' => APP_NAMESPACE . '\\Controllers',
                'class'     => $class,
                'handle'    => $handle,
            ]);
        }

        foreach ($files as $file => $contents) {
            if (is_file($file)) {
                CLI::write('Skipped ' . clean_path($file) . ', which exists.', 'yellow');

                continue;
            }

            if ((! is_dir(dirname($file)) && ! mkdir(dirname($file), 0775, true)) || file_put_contents($file, $contents) === false) {
                CLI::error("Couldn't write " . clean_path($file) . '.');

                return EXIT_ERROR;
            }

            CLI::write('Created ' . clean_path($file), 'green');
        }

        $this->nextSteps($collection, $controller ? $controllerClass : null, $controllerSkipped);

        return EXIT_SUCCESS;
    }

    /**
     * Whether PHP accepts the name for a class. The tokenizer catches a leading digit and keywords such as `list`.
     */
    private function isValidClassName(string $class): bool
    {
        return (token_get_all("<?php {$class};")[1][0] ?? null) === T_STRING
            && ! in_array(strtolower($class), self::RESERVED_CLASS_NAMES, true);
    }

    /**
     * The front matter key of the first text field, as PHP code, for the index view to show under each title.
     * Null when there is none.
     *
     * @param list<Field> $fields
     */
    private function teaser(array $fields): ?string
    {
        foreach ($fields as $field) {
            if (in_array($field->type, ['text', 'textarea'], true)) {
                return var_export(FieldTypes::get($field->type)->key($field), true);
            }
        }

        return null;
    }

    /**
     * Tells the developer how to reach the views: the collection doesn't know about them, and Bonfire doesn't add
     * an index route.
     *
     * @param string|null $controller        the class of the controller, with --controller
     * @param bool        $controllerSkipped whether the controller's file existed, so wasn't written
     */
    private function nextSteps(Collection $collection, ?string $controller, bool $controllerSkipped): void
    {
        $handle = $collection->handle;
        $steps  = [];

        if ($collection->route === null) {
            $steps[] = "Give the collection a `route`, such as `route: /{$handle}/{slug}`, then run php spark content:reindex, so its entries have URIs.";
        }

        if ($controllerSkipped) {
            $steps[] = "{$controller} already existed, so it was left alone. Give it the index() and show(Entry \$entry) methods that these steps use.";
        }

        if ($controller !== null) {
            $steps[] = "Set `controller: {$controller}::show` in content/collections/{$handle}.yaml, in place of any `template`, so the collection hands its entries to the controller.";
            $steps[] = "Add the index route to app/Config/Routes.php:\n\n\$routes->get('{$handle}', '\\{$controller}::index');";
        } else {
            $steps[] = "Set `template: {$handle}/show` in content/collections/{$handle}.yaml, in place of any `controller`, so its entries are shown with the show view.";
            $steps[] = <<<STEP
                Add the index route to app/Config/Routes.php:

                \$routes->get('{$handle}', static function (): string {
                    \$query = content()->collection('{$handle}')->published();

                    return render('App', '{$handle}/index', [
                        'entries' => \$query->paginate(10),
                        'pager'   => \$query->pager(),
                    ]);
                });
                STEP;
        }

        CLI::newLine();
        CLI::write('Next steps:', 'yellow');

        foreach ($steps as $i => $step) {
            CLI::write(($i + 1) . '. ' . $step);
        }
    }

    /**
     * The fields of every blueprint the collection lists, by handle. Where two blueprints have a field with the
     * same handle, the first one's is kept.
     *
     * @return array<string, Field>
     *
     * @throws ContentException when a blueprint is missing or invalid
     */
    private function fields(Collection $collection, string $blueprintPath): array
    {
        $fields = [];

        foreach ($collection->blueprints as $handle) {
            foreach (Blueprint::load($blueprintPath, $collection->handle, $handle)->fields as $field) {
                $fields[$field->handle] ??= $field;
            }
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $template, array $data): string
    {
        return $this->renderTemplate(__DIR__ . "/Views/content/{$template}.tpl.php", $data);
    }
}
