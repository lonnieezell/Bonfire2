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

use Bonfire\Commands\Install\Publisher;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
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
    protected $options = [
        '--continue' => 'Execute the second install step.',
    ];

    private array $configFiles = [
        'Bonfire\Assets\Config\Assets',
        'Bonfire\Auth\Config\Auth',
        'Bonfire\Auth\Config\AuthGroups',
        'Bonfire\Config\Bonfire',
        'Bonfire\Config\Site',
        'Bonfire\Config\Themes',
        'Bonfire\Consent\Config\Consent',
        'Bonfire\Dashboard\Config\Dashboard',
        'Bonfire\Recycler\Config\Recycler',
        'Bonfire\Users\Config\Users',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        helper('filesystem');

        if (! CLI::getOption('continue')) {
            $this->ensureEnvFile();
            $this->setAppUrl();
            $this->setEncryptionKey();
            $this->setDatabase();
            $this->publishConfigFiles();
            $this->publishThemes();

            CLI::newLine();
            CLI::write('If you need to create your database, you may run:', 'yellow');
            CLI::write("\tphp spark db:create <database name>", 'green');
            CLI::write('If you chose SQLite3 as your database driver, the database will be created automatically on the next step (migration).', 'yellow');
            CLI::newLine();
            CLI::write('To migrate and create the initial user, please run: ', 'yellow');
            CLI::write("\tphp spark bf:install --continue", 'green');
        } else {
            $this->migrate();
            $this->createUser();
        }

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

        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
            $url = 'http://' . $url;
        }

        $this->updateEnvFile("# app.baseURL = ''", "app.baseURL = '{$url}'");
    }

    private function setDatabase()
    {
        $host = $user = $pass = '';
        $driver = CLI::prompt('Database driver:', ['MySQLi', 'Postgre', 'SQLite3', 'SQLSRV']);
        $name   = CLI::prompt('Database name:', 'bonfire');
        if ($driver !== 'SQLite3') {
            $host = CLI::prompt('Database host:', 'localhost');
            $user = CLI::prompt('Database username:', 'root');
            $pass = CLI::prompt('Database password:', 'root');
        }
        $prefix = CLI::prompt('Table prefix, if any (like bf_)');

        $this->updateEnvFile('# database.default.DBDriver = MySQLi', "database.default.DBDriver = {$driver}");
        $this->updateEnvFile('# database.default.database = ci4', "database.default.database = {$name}");
        if ($driver !== 'SQLite3') {
            $this->updateEnvFile('# database.default.hostname = localhost', "database.default.hostname = {$host}");
            $this->updateEnvFile('# database.default.username = root', "database.default.username = {$user}");
            $this->updateEnvFile('# database.default.password = root', "database.default.password = {$pass}");
        }
        $this->updateEnvFile('# database.default.DBPrefix =', "database.default.DBPrefix = {$prefix}");
    }

    private function publishConfigFiles()
    {
        $publisher = new Publisher();
        $publisher->setDestination(APPPATH . 'Config/');

        CLI::newLine();
        CLI::write('Publishing config files', 'yellow');

        foreach ($this->configFiles as $className) {
            $publisher->publishClass($className);
        }
    }

    private function publishThemes()
    {
        $source      = BFPATH . '../themes';
        $destination = APPPATH . '../themes';

        $publisher = new Publisher();
        $publisher->copyDirectory($source, $destination);
    }

    private function setEncryptionKey()
    {
        // generate a key using the out-of-the-box defaults for the Encryption library
        CLI::newLine();
        CLI::write('Generating encryption key', 'yellow');
        $key = bin2hex(\CodeIgniter\Encryption\Encryption::createKey());
        $this->updateEnvFile('# encryption.key =', "encryption.key = hex2bin:{$key}");
        CLI::write('Encryption key saved to .env file', 'green');
        CLI::newLine();
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

        /** @var \Bonfire\Users\User $user */
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
