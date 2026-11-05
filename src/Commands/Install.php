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
use Bonfire\Commands\Install\ContentEdits;
use Bonfire\Commands\Install\Edit;
use Bonfire\Commands\Install\Publisher;
use Bonfire\Commands\Install\TasksConfig;
use Bonfire\Config\Alerts;
use Bonfire\Config\Bonfire;
use Bonfire\Config\Site;
use Bonfire\Config\Themes;
use Bonfire\Consent\Config\Consent;
use Bonfire\Content\Config\Content;
use Bonfire\Core\BreakingChanges;
use Bonfire\Dashboard\Config\Dashboard;
use Bonfire\Privacy\Config\Privacy;
use Bonfire\Recycler\Config\Recycler;
use Bonfire\Users\Config\Users;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Encryption\Encryption;
use Config\Autoload as AutoloadConfig;
use InvalidArgumentException;
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
        '-f'         => 'Overwrite theme files that already exist, discarding your changes to them.',
    ];

    /**
     * What this run could not do, shown again at the end.
     *
     * @var list<string>
     */
    private array $problems = [];

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
        Content::class,
        Dashboard::class,
        Privacy::class,
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
            if (! $this->ensureEnvFile()) {
                return EXIT_ERROR;
            }

            $this->setAppUrl();
            $this->removeIndexDotPhp();
            $this->setEncryptionKey();
            $this->setDatabase();
            $this->publishConfigFiles();
            $this->setAutoloadHelpers();
            $this->setScheduledTasks();
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
            command('bonfire:permissions');
            $this->createUser();
        }

        $this->updateComposerJson();
        $this->seedBreakingChangesState();

        CLI::newLine();

        if ($this->problems !== []) {
            CLI::error('Some changes could not be made, so the install is not complete:');

            foreach ($this->problems as $problem) {
                CLI::error('  - ' . $problem);
            }

            CLI::newLine();

            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }

    /**
     * Reads a file, lets `$transform` decide the edit, and writes the result
     * when it changed. Text the edit could not find is reported, not ignored.
     *
     * @param callable(string): Edit $transform
     */
    private function applyEdit(string $path, callable $transform): ?Edit
    {
        $cleanPath = clean_path($path);
        $content   = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            $this->fail("Could not read {$cleanPath}.");

            return null;
        }

        $edit = $transform($content);

        if (! $edit->complete()) {
            $this->fail("{$cleanPath}: did not find `" . implode('`, `', $edit->missing) . '`, so it was not updated. Make the change by hand.');

            return $edit;
        }

        if ($edit->changed() && ! write_file($path, $edit->content)) {
            $this->fail("Error updating {$cleanPath}.");
        }

        return $edit;
    }

    /**
     * Shows a problem now and again in the summary at the end.
     */
    private function fail(string $problem): void
    {
        $this->problems[] = $problem;

        CLI::error('  ' . $problem);
    }

    /**
     * Records the newest breaking change shipped with this version, so that the
     * first update after a fresh install does not warn about changes that came
     * before it.
     */
    private function seedBreakingChangesState(): void
    {
        $breakingChanges = new BreakingChanges();

        // Never overwrite a marker an earlier install already wrote.
        if ($breakingChanges->acknowledged() !== null) {
            return;
        }

        $latest = $breakingChanges->latest();

        if ($latest === null) {
            return;
        }

        if (! $breakingChanges->acknowledge($latest)) {
            CLI::write('Could not save the breaking changes marker to ' . $breakingChanges->statePath(), 'yellow');
        }
    }

    /**
     * Copies the env file, if .env does not exist
     *
     * @return bool False when there is no .env and no `env` to make it from.
     */
    private function ensureEnvFile(): bool
    {
        CLI::print('Creating .env file...', 'yellow');

        if (file_exists(ROOTPATH . '.env')) {
            // Note that this is not a fresh install for use later
            $this->isFreshInstall = false;

            CLI::print('Exists', 'green');

            return true;
        }

        if (! file_exists(ROOTPATH . 'env')) {
            CLI::error('The original `env` file is not found.');

            return false;
        }

        // Create the .env file
        if (! copy(ROOTPATH . 'env', ROOTPATH . '.env')) {
            CLI::error('Error copying the env file');

            return false;
        }

        CLI::print('Done', 'green');

        CLI::write('Setting initial environment', 'yellow');

        // Set to development environment
        $this->updateEnvFile('CI_ENVIRONMENT', 'development');

        return true;
    }

    private function setAppUrl()
    {
        CLI::newLine();
        $url = CLI::prompt('What URL are you running Bonfire under locally? Default: ', 'http://localhost:8080');

        if (! str_contains($url, 'http://') && ! str_contains($url, 'https://')) {
            $url = 'http://' . $url;
        }

        $this->updateEnvFile('app.baseURL', "'{$url}'");
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

        $this->updateEnvFile('database.default.DBDriver', $driver);
        $this->updateEnvFile('database.default.database', $name);
        if ($driver !== 'SQLite3') {
            $this->updateEnvFile('database.default.hostname', $host);
            $this->updateEnvFile('database.default.username', $user);
            $this->updateEnvFile('database.default.password', $pass);
        } else {
            CLI::newLine();
            CLI::write('Updating SQLite3 database config', 'yellow');

            $this->applyEdit(
                APPPATH . 'Config/Database.php',
                static fn (string $config): Edit => ContentEdits::sqliteDatabaseConfig($config),
            );

            $orig = '# database.default.port = 3306';
            $new  = $orig
                . PHP_EOL . 'database.default.foreignKeys = true'
                . PHP_EOL . 'database.default.busyTimeout = 1000';
            $this->applyEdit(
                ROOTPATH . '.env',
                static fn (string $env): Edit => ContentEdits::replaceText($env, $orig, $new),
            );
        }
        $this->updateEnvFile('database.default.DBPrefix', $prefix);
    }

    private function publishConfigFiles()
    {
        $publisher = new Publisher();

        try {
            $publisher->setDestination(APPPATH . 'Config/');
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage());

            return;
        }

        CLI::newLine();
        CLI::write('Publishing config files', 'yellow');

        foreach ($this->configFiles as $className) {
            array_push($this->problems, ...$publisher->publishClass($className));
        }
    }

    /**
     * Copies Bonfire's themes. Theme files you already have are yours, and
     * are left alone unless you ran the installer with -f.
     */
    private function publishThemes()
    {
        $source      = BFPATH . '../themes';
        $destination = APPPATH . '../themes';

        try {
            $result = (new Publisher())->copyDirectory($source, $destination, (bool) CLI::getOption('f'));
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage());

            return;
        }

        CLI::write("  Themes: {$result['copied']} files copied.", 'green');

        foreach ($result['failed'] as $path) {
            $this->fail('Could not copy ' . clean_path($path) . '.');
        }

        if ($result['skipped'] > 0) {
            CLI::write(
                "  Themes: {$result['skipped']} files already exist and were left as they are."
                . ' Run `php spark bf:install -f` to overwrite them, which discards your changes.',
                'yellow',
            );
        }
    }

    private function setEncryptionKey()
    {
        // generate a key using the out-of-the-box defaults for the Encryption library
        CLI::newLine();
        CLI::write('Generating encryption key', 'yellow');
        $key = bin2hex(Encryption::createKey());

        if ($this->updateEnvFile('encryption.key', "hex2bin:{$key}")?->changed()) {
            CLI::write('Encryption key saved to .env file', 'green');
        }

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
     * Sets a key in the .env file. A key that is set already keeps its value,
     * so that running the installer again does not, for one, replace the
     * encryption key; the installer says so rather than dropping your answer.
     */
    private function updateEnvFile(string $key, string $value): ?Edit
    {
        $edit = $this->applyEdit(
            ROOTPATH . '.env',
            static fn (string $env): Edit => ContentEdits::setEnv($env, $key, $value),
        );

        if ($edit?->complete() && ! $edit->changed()) {
            CLI::write("  {$key} is set in .env already, so it was kept. Edit .env to change it.", 'yellow');
        }

        return $edit;
    }

    private function setAutoloadHelpers(): void
    {
        $path      = APPPATH . 'Config/Autoload.php';
        $cleanPath = clean_path($path);

        $config     = new AutoloadConfig();
        $newHelpers = array_values(array_unique(array_merge($config->helpers, ['auth', 'setting'])));

        $edit = $this->applyEdit(
            $path,
            static fn (string $content): Edit => ContentEdits::autoloadHelpers($content, $newHelpers),
        );

        if ($edit === null || ! $edit->complete()) {
            return;
        }

        if (! $edit->changed()) {
            CLI::write(CLI::color('  Autoload Setup: ', 'green') . 'Everything is fine.');

            return;
        }

        CLI::write(CLI::color('  Updated: ', 'green') . $cleanPath);

        $this->removeHelperLoadingInBaseController();
    }

    /**
     * Adds Bonfire's scheduled tasks, like pruning old personal data,
     * to the app's Config\Tasks.
     */
    private function setScheduledTasks(): void
    {
        $path      = APPPATH . 'Config/Tasks.php';
        $cleanPath = clean_path($path);

        match ((new TasksConfig())->addSchedule($path)) {
            'created'   => CLI::write(CLI::color('  Created: ', 'green') . $cleanPath),
            'updated'   => CLI::write(CLI::color('  Updated: ', 'green') . $cleanPath),
            'unchanged' => CLI::write(CLI::color('  Tasks Setup: ', 'green') . 'Everything is fine.'),
            'manual'    => CLI::error("  Add `\\Bonfire\\Bonfire::schedule(\$schedule);` to init() in '{$cleanPath}'."),
        };

        CLI::write('  Run `php spark tasks:run` every minute from cron so scheduled tasks run.', 'yellow');
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
        $edit = $this->applyEdit(
            APPPATH . 'Config/Security.php',
            static fn (string $content): Edit => ContentEdits::replaceText(
                $content,
                '$csrfProtection = \'cookie\';',
                '$csrfProtection = \'session\';',
            ),
        );

        if ($edit?->changed()) {
            CLI::write(CLI::color('  Updated: ', 'green') . clean_path(APPPATH . 'Config/Security.php'));
        }
    }

    /**
     * Cleans up the helper loading older installs added to BaseController.
     * Most apps have none of it, so finding nothing to change is normal here.
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

        $this->fail("Error updating {$cleanPath}.");
    }

    /**
     * Updates the user's composer.json to include Bonfire update scripts.
     */
    private function updateComposerJson()
    {
        $path = ROOTPATH . 'composer.json';

        if (! file_exists($path)) {
            CLI::write('composer.json not found.', 'red');

            return;
        }

        $edit = $this->applyEdit(
            $path,
            static fn (string $json): Edit => ContentEdits::composerUpdateScript($json, 'php spark notify:breaking-changes'),
        );

        if ($edit?->changed()) {
            CLI::write('composer.json updated successfully.', 'green');
        }
    }

    /**
     * Updates the welcome_message.php file to include the Bonfire links.
     */
    private function updateWelcomeMessage()
    {
        $path = APPPATH . 'Views/welcome_message.php';

        if (! file_exists($path)) {
            // this can happen if the themes were not copied
            return;
        }

        $edit = $this->applyEdit(
            $path,
            static fn (string $content): Edit => ContentEdits::replaceText(
                $content,
                '<!-- CONTENT -->',
                "<?php require_once(ROOTPATH . 'themes/App/_tmp_bonfire_links_include.php'); ?>",
            ),
        );

        if ($edit?->changed()) {
            CLI::write('Updated welcome_message.php successfully.', 'green');
        }
    }

    private function removeIndexDotPhp()
    {
        $response = CLI::prompt('Do you want to delete index.php from the baseURL?', ['y', 'n']);

        if (strtolower($response) !== 'y') {
            CLI::write('Skipping removal of index.php from baseURL.', 'yellow');

            return;
        }

        $this->applyEdit(ROOTPATH . '.env', ContentEdits::removeIndexPage(...));
    }

    private function getAppUrl(): string
    {
        $env = file_get_contents(ROOTPATH . '.env');
        preg_match('/^app\.baseURL\s*=\s*[\'"]([^\'"]+)[\'"]/m', $env, $matches);

        return $matches[1] ?? 'http://localhost:8080';
    }
}
