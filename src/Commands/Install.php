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

use Bonfire\Assets\Config\Assets;
use Bonfire\Auth\Config\Auth;
use Bonfire\Auth\Config\AuthGroups;
use Bonfire\Auth\Config\AuthToken;
use Bonfire\Commands\Install\Publisher;
use Bonfire\Config\Alerts;
use Bonfire\Config\Bonfire;
use Bonfire\Config\Site;
use Bonfire\Config\Themes;
use Bonfire\Consent\Config\Consent;
use Bonfire\Dashboard\Config\Dashboard;
use Bonfire\Recycler\Config\Recycler;
use Bonfire\Users\Config\Users;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Encryption\Encryption;
use Config\Autoload as AutoloadConfig;
use ReflectionException;

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
     * @var array<string, string>
     */
    protected $arguments = [];

    /**
     * Is it a fresh install?
     *
     * @var bool
     */
    protected $isFreshInstall = true;

    /**
     * The Command's Options
     *
     * @var array<string, string>
     */
    protected $options = [
        '--continue' => 'Execute the second install step.',
    ];

    private array $configFiles = [
        Alerts::class,
        Assets::class,
        Auth::class,
        AuthGroups::class,
        AuthToken::class,
        Bonfire::class,
        Site::class,
        Themes::class,
        Consent::class,
        Dashboard::class,
        Recycler::class,
        Users::class,
    ];

    /**
     * Actually execute a command.
     *
     * @throws ReflectionException
     */
    public function run(array $params)
    {
        helper('filesystem');

        if (! CLI::getOption('continue')) {
            $this->ensureEnvFile();
            $this->setAppUrl();
            $this->removeIndexDotPhp();
            $this->setEncryptionKey();
            $this->setDatabase();
            $this->publishConfigFiles();
            $this->setAutoloadHelpers();
            $this->setSecurityCSRF();
            $this->publishThemes();
            if ($this->isFreshInstall) {
                $this->updateWelcomeMessage();
            }

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

        $this->updateComposerJson();

        CLI::newLine();
    }

    /**
     * Copies the env file, if .env does not exist
     */
    private function ensureEnvFile()
    {
        CLI::print('Creating .env file...', 'yellow');

        if (file_exists(ROOTPATH . '.env')) {
            // Note that this is not a fresh install for use later
            $this->isFreshInstall = false;

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
        $url = CLI::prompt('What URL are you running Bonfire under locally? Default: ', 'http://localhost:8080');

        if (! str_contains($url, 'http://') && ! str_contains($url, 'https://')) {
            $url = 'http://' . $url;
        }

        $this->updateEnvFile("# app.baseURL = ''", "app.baseURL = '{$url}'");
    }

    private function setDatabase()
    {
        $host = '';
        $user = '';
        $pass = '';

        $options = [
            '1' => 'MySQLi',
            '2' => 'Postgre',
            '3' => 'SQLite3',
            '4' => 'SQLSRV',
        ];
        $driverKey = CLI::promptByKey('Database driver:', $options, 'required|in_list[1,2,3,4]');
        $driver    = $options[$driverKey];

        $name = CLI::prompt('Database name:', 'bonfire');
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
        } else {
            CLI::newLine();
            CLI::write('Updating SQLite3 database config', 'yellow');

            $orig = "'port'     => 3306," . PHP_EOL . '    ];';
            $new  = "'port'     => 3306,"
                . PHP_EOL . "        'foreignKeys' => true,"
                . PHP_EOL . "        'busyTimeout' => 1000,"
                . PHP_EOL . '    ];';
            $this->updateConfigFile('Database', $orig, $new);

            $orig2 = '# database.default.port = 3306';
            $new2  = '# database.default.port = 3306'
                . PHP_EOL . 'database.default.foreignKeys = true'
                . PHP_EOL . 'database.default.busyTimeout = 1000';
            $this->updateEnvFile($orig2, $new2);
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
        $key = bin2hex(Encryption::createKey());
        $this->updateEnvFile('# encryption.key =', "encryption.key = hex2bin:{$key}");
        CLI::write('Encryption key saved to .env file', 'green');
        CLI::newLine();
    }

    private function migrate()
    {
        command('migrate --all');
        CLI::newLine();
    }

    /**
     * @throws ReflectionException
     */
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
            'active'     => 1,
        ]);
        $users->save($user);

        /** @var User $user */
        $user = $users->where('username', $username)->first();
        $user->createEmailIdentity([
            'email'    => $email,
            'password' => $password,
        ]);

        $user->addGroup('superadmin');

        CLI::newLine();
        CLI::write('DONE. Assuming you took care of the database, you can now start the app using' . PHP_EOL . "\tphp spark serve " . PHP_EOL . 'command and open the website in browser.', 'green');
        CLI::newLine();
        CLI::write("Application front-page URL: {$this->getAppUrl()}", 'green');
        CLI::write('To login as superadmin, visit the ' . $this->getAppUrl() . '/login page.', 'green');
        CLI::newLine();
        CLI::write('If you want to create some data for testing, you can insert 100 users by runing ', 'yellow');

        CLI::write("\tphp spark db:seed Bonfire\\\\Users\\\\Database\\\\Seeds\\\\Seed100Users", 'yellow');
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

    /**
     * Replaces text within the config file.
     */
    private function updateConfigFile(string $filename, string $find, string $replace)
    {
        $file = file_get_contents(APPPATH . 'Config/' . $filename . '.php');
        $conf = str_replace($find, $replace, $file);
        write_file(APPPATH . 'Config/' . $filename . '.php', $conf);
    }

    private function setAutoloadHelpers(): void
    {
        $file = 'Config/Autoload.php';

        $path      = APPPATH . $file;
        $cleanPath = clean_path($path);

        $config     = new AutoloadConfig();
        $helpers    = $config->helpers;
        $newHelpers = array_unique(array_merge($helpers, ['auth', 'setting']));

        $pattern = '/^ {4}public \$helpers = \[.*];/mu';
        $replace = '    public $helpers = [\'' . implode("', '", $newHelpers) . "'];";
        $content = file_get_contents($path);
        $output  = preg_replace($pattern, $replace, $content);

        // check if the content is updated
        if ($output === $content) {
            CLI::write(CLI::color('  Autoload Setup: ', 'green') . 'Everything is fine.');

            return;
        }

        if (write_file($path, $output)) {
            CLI::write(CLI::color('  Updated: ', 'green') . $cleanPath);

            $this->removeHelperLoadingInBaseController();
        } else {
            error("  Error updating file '{$cleanPath}'.");
        }
    }

    private function removeHelperLoadingInBaseController(): void
    {
        $file = 'Controllers/BaseController.php';

        $check = '        $this->helpers = array_merge($this->helpers, [\'setting\']);';

        // Replace old helper setup
        $replaces = [
            '$this->helpers = array_merge($this->helpers, [\'auth\', \'setting\']);' => $check,
        ];
        $this->replace($file, $replaces);

        // Remove helper setup
        $replaces = [
            "\n" . $check . "\n" => '',
        ];
        $this->replace($file, $replaces);
    }

    private function setSecurityCSRF(): void
    {
        $file     = 'Config/Security.php';
        $replaces = [
            '$csrfProtection = \'cookie\';' => '$csrfProtection = \'session\';',
        ];

        $path      = APPPATH . $file;
        $cleanPath = clean_path($path);

        if (! is_file($path)) {
            error("  Not found file '{$cleanPath}'.");

            return;
        }

        $this->replace($file, $replaces);
    }

    /**
     * Replace for setupHelper()
     *
     * @param string $file     Relative file path like 'Controllers/BaseController.php'.
     * @param array  $replaces [search => replace]
     */
    private function replace(string $file, array $replaces): void
    {
        $path      = APPPATH . $file;
        $cleanPath = clean_path($path);

        $content = file_get_contents($path);

        $output = strtr($content, $replaces);

        if ($output === $content) {
            return;
        }

        if (write_file($path, $output)) {
            CLI::write(CLI::color('  Updated: ', 'green') . $cleanPath);

            return;
        }

        error("  Error updating {$cleanPath}.");
    }

    /**
     * Updates the user's composer.json to include Bonfire update scripts.
     */
    private function updateComposerJson()
    {
        $composerJsonPath = ROOTPATH . 'composer.json';

        if (! file_exists($composerJsonPath)) {
            CLI::write('composer.json not found.', 'red');

            return;
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            CLI::write('Error decoding composer.json: ' . json_last_error_msg(), 'red');

            return;
        }

        // Ensure the scripts section exists
        if (! isset($composerJson['scripts'])) {
            $composerJson['scripts'] = [];
        }

        // Ensure the post-update-cmd section exists
        if (! isset($composerJson['scripts']['post-update-cmd'])) {
            $composerJson['scripts']['post-update-cmd'] = [];
        }

        // Add the Bonfire update script if it's not already present
        $bonfireUpdateScript = 'php spark notify:breaking-changes';
        if (! in_array($bonfireUpdateScript, $composerJson['scripts']['post-update-cmd'], true)) {
            $composerJson['scripts']['post-update-cmd'][] = $bonfireUpdateScript;
        }

        // Save the updated composer.json
        file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        CLI::write('composer.json updated successfully.', 'green');
    }

    /**
     * Updates the welcome_message.php file to include the Bonfire links.
     */
    private function updateWelcomeMessage()
    {
        $filePath      = APPPATH . 'Views/welcome_message.php';
        $searchString  = '<!-- CONTENT -->';
        $replaceString = "<?php require_once(ROOTPATH . 'themes/App/_tmp_bonfire_links_include.php'); ?>";

        if (! file_exists($filePath)) {
            // this can happen if the themes were not copied
            return;
        }

        $content = file_get_contents($filePath);
        if (! str_contains($content, $searchString)) {
            // this can happen if the content of the file were changed upstream
            return;
        }

        $updatedContent = str_replace($searchString, $replaceString, $content);
        if (write_file($filePath, $updatedContent)) {
            CLI::write('Updated welcome_message.php successfully.', 'green');
        } else {
            CLI::error('Error updating welcome_message.php.');
        }
    }

    private function removeIndexDotPhp()
    {
        $response = CLI::prompt('Do you want to delete index.php from the baseURL?', ['y', 'n']);

        if (strtolower($response) !== 'y') {
            CLI::write('Skipping removal of index.php from baseURL.', 'yellow');

            return;
        }
        $envFile = ROOTPATH . '.env';
        $lines   = file($envFile, FILE_IGNORE_NEW_LINES);

        $newLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'app.baseURL')) {
                $newLines[] = "app.indexPage = ''";
            }
            $newLines[] = $line;
        }

        file_put_contents($envFile, implode(PHP_EOL, $newLines));
    }

    private function getAppUrl(): string
    {
        $env = file_get_contents(ROOTPATH . '.env');
        preg_match('/^app\.baseURL\s*=\s*[\'"]([^\'"]+)[\'"]/m', $env, $matches);

        return $matches[1] ?? 'http://localhost:8080';
    }
}
