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

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Install extends BaseCommand
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
    protected $name = 'bf:install';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Handles initial installation of Bonfire.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'bf:install';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        helper('filesystem');

        $this->ensureEnvFile();
        $this->setAppURL();
        $this->setDatabase();
        $this->migrate();
        $this->createUser();

        CLI::newLine();
    }

    /**
     * Copies the env file, if .env does not exist
     */
    private function ensureEnvFile()
    {
        CLI::print('Creating .env file...', 'yellow');

        if (file_exists(ROOTPATH . '.env')) {
            CLI::print('Exists', 'green');

            return;
        }

        if (! file_exists(ROOTPATH . 'env')) {
            CLI::error('The original `env` file is not found.');

            exit();
        }

        // Create the .env file
        if (! copy(ROOTPATH . 'env', ROOTPATH . '.env')) {
            CLI::error('Error copying the env file');
        }

        CLI::print('Done', 'green');

        CLI::write('Setting initial environment', 'yellow');

        // Set to development environment
        $this->updateEnvFile('# CI_ENVIRONMENT = production', 'CI_ENVIRONMENT = development');
    }

    private function setAppUrl()
    {
        CLI::newLine();
        $url = CLI::prompt('What URL are you running Bonfire under locally?');
        $this->updateEnvFile("# app.baseURL = ''", "app.baseURL = '{$url}'");
    }

    private function setDatabase()
    {
        $host   = CLI::prompt('Database host:', 'localhost');
        $name   = CLI::prompt('Database name:', 'bonfire');
        $user   = CLI::prompt('Database username:', 'root');
        $pass   = CLI::prompt('Database password:', 'root');
        $driver = CLI::prompt('Database driver:', ['MySQLi', 'Postgre', 'SQLite']);
        $prefix = CLI::prompt('Table prefix:');

        $this->updateEnvFile('# database.default.hostname = localhost', "database.default.hostname = {$host}");
        $this->updateEnvFile('# database.default.database = ci4', "database.default.database = {$name}");
        $this->updateEnvFile('# database.default.username = root', "database.default.username = {$user}");
        $this->updateEnvFile('# database.default.password = root', "database.default.password = {$pass}");
        $this->updateEnvFile('# database.default.DBDriver = MySQLi', "database.default.DBDriver = {$driver}");
        $this->updateEnvFile('# database.default.DBPrefix = ', "database.default.DBPrefix = {$prefix}");
    }

    private function migrate()
    {
        command('migrate --all');
        CLI::newLine();
    }

    private function createUser()
    {
        CLI::write('Create initial user', 'yellow');

        $email     = CLI::prompt('Email?');
        $firstName = CLI::prompt('First name?');
        $lastName  = CLI::prompt('Last name?');
        $username  = CLI::prompt('Username?');
        $password  = CLI::prompt('Password?');

        $users = model(UserModel::class);

        $user = new User([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'username'   => $username,
        ]);
        $users->save($user);

        $user = $users->where('username', $username)->first();
        $user->createEmailIdentity([
            'email'    => $email,
            'password' => $password,
        ]);

        $user->addGroup('superadmin');

        CLI::write('Done. You can now login as a superadmin.', 'green');
    }

    /**
     * Replaces text within the .env file.
     */
    private function updateEnvFile(string $find, string $replace)
    {
        $env = file_get_contents(ROOTPATH . '.env');
        $env = str_replace($find, $replace, $env);
        write_file(ROOTPATH . '.env', $env);
    }
}
