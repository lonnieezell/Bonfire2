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
use CodeIgniter\Database\BaseBuilder;
use Config\Services;
use Myth\Postal\Config\Mailer as PostalMailer;
use Myth\Postal\LegacyConfigMapper;

/**
 * Helps an install move its Email.* transport settings out of the Settings
 * database and into .env and app/Config/Mailer.php. It writes no files.
 */
class EmailEnv extends BaseCommand
{
    /**
     * The transport settings as a new CodeIgniter install's Config\Email has them.
     */
    private const FRAMEWORK_DEFAULTS = [
        'protocol'      => 'mail',
        'mailPath'      => '/usr/sbin/sendmail',
        'SMTPHost'      => '',
        'SMTPPort'      => 25,
        'SMTPUser'      => '',
        'SMTPPass'      => '',
        'SMTPCrypto'    => 'tls',
        'SMTPTimeout'   => 5,
        'SMTPKeepAlive' => false,
    ];

    /**
     * Other keys Postal's mapper reads. They are not ones the Settings page
     * saved, so they are neither labelled nor purged.
     */
    private const MAPPER_ONLY_KEYS = ['SMTPAuthMethod', 'DSN'];

    protected $group       = 'Bonfire';
    protected $name        = 'bonfire:email-env';
    protected $description = 'Prints .env lines and a Mailer.php snippet for the mail settings stored in the database.';
    protected $usage       = 'bonfire:email-env [--purge] [--force]';
    protected $options     = [
        '--purge' => 'Delete the transport settings from the database. Needs app/Config/Mailer.php.',
        '--force' => "Don't ask before purging.",
    ];

    public function run(array $params)
    {
        helper('setting');

        $sources = $this->sources();
        $values  = [];

        foreach ([...array_keys(self::FRAMEWORK_DEFAULTS), ...self::MAPPER_ONLY_KEYS] as $key) {
            $values[$key] = setting('Email.' . $key);
        }

        if ($this->nothingToMigrate($sources, $values)) {
            CLI::write('Nothing to migrate');

            return EXIT_SUCCESS;
        }

        $mailer = LegacyConfigMapper::toMailer($values);

        $this->printSources($sources);
        $this->printEnv($mailer);
        $this->printSnippet($mailer);

        if (! array_key_exists('purge', $params) && ! CLI::getOption('purge')) {
            return EXIT_SUCCESS;
        }

        return $this->purge(array_key_exists('force', $params) || CLI::getOption('force'));
    }

    /**
     * Where setting() gets each value from: a row wins, then .env, then the file.
     *
     * @return array<string, string>
     */
    private function sources(): array
    {
        $stored = array_column($this->rows()->where('context')->get()->getResultArray(), 'key');

        $sources = [];

        foreach (array_keys(self::FRAMEWORK_DEFAULTS) as $key) {
            $envKey = $this->envKey($key);

            $sources[$key] = match (true) {
                in_array($key, $stored, true) => 'database',
                $envKey !== null              => ".env ({$envKey})",
                default                       => 'Config\Email',
            };
        }

        return $sources;
    }

    /**
     * The name BaseConfig would find this property's override under, if any.
     */
    private function envKey(string $property): ?string
    {
        $class = config('Email')::class;

        foreach (["email.{$property}", "email_{$property}", "{$class}.{$property}", "{$class}_{$property}"] as $name) {
            if (env($name) !== null) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $sources
     * @param array<string, mixed>  $values
     */
    private function nothingToMigrate(array $sources, array $values): bool
    {
        foreach (self::FRAMEWORK_DEFAULTS as $key => $default) {
            if ($sources[$key] !== 'Config\Email' || $values[$key] !== $default) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string> $sources
     */
    private function printSources(array $sources): void
    {
        CLI::write('Where each value comes from (only database rows can be purged):');

        foreach ($sources as $key => $source) {
            CLI::write('  ' . str_pad($key, 15) . $source);
        }

        CLI::newLine();
    }

    /**
     * @param array<string, mixed> $mailer
     */
    private function printEnv(array $mailer): void
    {
        CLI::write('.env (replaces any email.* keys):');

        $name    = $mailer['transport'];
        $printed = false;

        foreach ($mailer as $key => $value) {
            if ($key === 'transport' || is_array($value)) {
                continue;
            }

            $envKey  = "mailer.mailers.{$name}.{$key}";
            $printed = true;

            CLI::write("{$envKey} = " . $this->envValue($value));

            if (is_string($value) && $this->changedByEnvRoute($value)) {
                CLI::write("# Warning: CodeIgniter would change this value when it loads {$envKey}. Change the value, or set it in Mailer.php from env('...') in the constructor.");
            }
        }

        if (! $printed) {
            CLI::write('# The mail transport needs no .env lines.');
        }

        CLI::newLine();
    }

    private function envValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    /**
     * BaseConfig trims quotes and turns the words true and false into booleans,
     * and DotEnv expands ${name} even inside quotes.
     */
    private function changedByEnvRoute(string $value): bool
    {
        return trim($value, '\'"') !== $value
            || $value === 'true'
            || $value === 'false'
            || str_contains($value, '${');
    }

    /**
     * BaseConfig only overrides keys the array already has, so every .env line
     * needs its key here. Credentials stay blank: they belong in .env alone.
     *
     * @param array<string, mixed> $mailer
     */
    private function printSnippet(array $mailer): void
    {
        $name = $mailer['transport'];

        foreach (['username', 'password'] as $secret) {
            if (isset($mailer[$secret])) {
                $mailer[$secret] = '';
            }
        }

        CLI::write('app/Config/Mailer.php (creating it turns off the legacy mail bridge):');
        CLI::write(<<<PHP
            <?php

            namespace Config;

            use Myth\\Postal\\Config\\Mailer as PostalMailer;

            class Mailer extends PostalMailer
            {
                public string \$default = '{$name}';

                public array \$mailers = [
                    '{$name}' => [
            {$this->export($mailer, 3)}        ],
                ];
            }
            PHP);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function export(array $values, int $depth): string
    {
        $indent = str_repeat('    ', $depth);
        $code   = '';

        foreach ($values as $key => $value) {
            $code .= $indent . var_export($key, true) . ' => ';
            $code .= is_array($value)
                ? "[\n" . $this->export($value, $depth + 1) . $indent . "],\n"
                : var_export($value, true) . ",\n";
        }

        return $code;
    }

    private function purge(bool $force): int
    {
        if (config('Mailer')::class === PostalMailer::class) {
            CLI::error('Not purging: the legacy mail bridge still reads these settings, and without them mail would go through PHP mail(). Create app/Config/Mailer.php from the snippet above first.');

            return EXIT_ERROR;
        }

        if ($this->rows()->countAllResults() === 0) {
            CLI::write('No transport settings in the database to purge.');

            return EXIT_SUCCESS;
        }

        if (! $force && CLI::prompt('Delete these transport settings from the database?', ['n', 'y']) !== 'y') {
            CLI::write('Nothing was purged.');

            return EXIT_SUCCESS;
        }

        $this->rows()->delete();

        // The settings service still holds what it read before the delete.
        Services::resetSingle('settings');

        CLI::write('Purged the transport settings from the database.', 'green');

        return EXIT_SUCCESS;
    }

    /**
     * The transport rows in every context, so no stored password is left behind.
     */
    private function rows(): BaseBuilder
    {
        $config = config('Settings');

        return db_connect($config->database['group'])
            ->table($config->database['table'])
            ->where('class', config('Email')::class)
            ->whereIn('key', array_keys(self::FRAMEWORK_DEFAULTS));
    }
}
