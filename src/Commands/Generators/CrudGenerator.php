<?php

namespace Bonfire\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;
use CodeIgniter\Controller;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\RESTful\ResourcePresenter;

/**
 * Generates a controller and view files for a CRUD.
 */
class CrudGenerator extends BaseCommand
{
    use GeneratorTrait;

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
    protected $name = 'make:crud';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a controller and view for a single module.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:crud <name> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The resource name.',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--force'     => 'Force overwrite existing file.',
        '--model'     => 'Set the model name used.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->makeController($params);
    }

    public function makeController(array $params)
    {
        dd($params);

        $this->component = 'Controller';
        $this->directory = 'Controllers';
        $this->template  = 'controller.tpl.php';

        $this->classNameLang = 'CLI.generator.className.controller';
        $this->execute($params);
    }
}
