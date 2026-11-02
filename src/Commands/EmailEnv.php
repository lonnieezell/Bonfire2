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

use Bonfire\Email\LegacySettingsTransport;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use Myth\Postal\Config\Mailer as PostalMailer;
use Myth\Postal\LegacyConfigMapper;

/**
 * Helps an install move its Email.* transport settings out of the Settings
 * database and into .env and app/Config/Mailer.php. It writes no files.
 */
class EmailEnv extends BaseCommand
{
    private const FRAMEWORK_DEFAULTS = LegacySettingsTransport::FRAMEWORK_DEFAULTS;

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

        $rows = LegacySettingsTransport::storedRows()->select('key, context')->get()->getResultArray();

        // Rows under a context never reach setting(), but they still hold passwords to purge.
        $stored = array_column(array_filter($rows, static fn (array $row): bool => $row['context'] === null), 'key');
        $values = [];

        foreach ([...array_keys(self::FRAMEWORK_DEFAULTS), ...self::MAPPER_ONLY_KEYS] as $key) {
            $values[$key] = setting('Email.' . $key);
        }

        if ($rows === [] && $this->onlyFrameworkDefaults($values)) {
            CLI::write('Nothing to migrate');

            return EXIT_SUCCESS;
        }

        $mailer = LegacyConfigMapper::toMailer($values);

        $this->printSources($this->sources($stored), count($rows) - count($stored));
        $this->printEnv($mailer);
        $this->printSnippet($mailer);

        if (! array_key_exists('purge', $params)) {
            return EXIT_SUCCESS;
        }

        return $this->purge($rows !== [], array_key_exists('force', $params));
    }

    /**
     * Where setting() gets each value from: a row wins, then .env, then the file.
     *
     * @param list<string> $stored Keys with a row setting() can read
     *
     * @return array<string, string>
     */
    private function sources(array $stored): array
    {
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
     * @param array<string, mixed> $values
     */
    private function onlyFrameworkDefaults(array $values): bool
    {
        foreach (self::FRAMEWORK_DEFAULTS as $key => $default) {
            if ($this->envKey($key) !== null || $values[$key] !== $default) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string> $sources
     */
    private function printSources(array $sources, int $contextRows): void
    {
        CLI::write('Where each value comes from (only database rows can be purged):');

        foreach ($sources as $key => $source) {
            CLI::write('  ' . str_pad($key, 15) . $source);
        }

        if ($contextRows > 0) {
            CLI::write("Not shown: {$contextRows} row(s) stored under a settings context. --purge deletes them too, so copy any you still need first.");
        }

        CLI::newLine();
    }

    /**
     * @param array<string, mixed> $mailer
     */
    private function printEnv(array $mailer): void
    {
        CLI::write('.env (replaces any email.* transport keys; keep email.fromEmail and email.fromName):');

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
     * DotEnv expands ${name} even inside quotes, and it reads one line at a time.
     */
    private function changedByEnvRoute(string $value): bool
    {
        return trim($value, '\'"') !== $value
            || $value === 'true'
            || $value === 'false'
            || str_contains($value, '${')
            || strpbrk($value, "\r\n") !== false;
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

    private function purge(bool $hasRows, bool $force): int
    {
        if (! $hasRows) {
            CLI::write('No transport settings in the database to purge.');

            return EXIT_SUCCESS;
        }

        if (config('Mailer')::class === PostalMailer::class) {
            CLI::error('Not purging: the legacy mail bridge still reads these settings, and without them mail would go through PHP mail(). Create app/Config/Mailer.php from the snippet above first.');

            return EXIT_ERROR;
        }

        if (! $force && CLI::prompt('Delete these transport settings from the database, including any stored under a context?', ['n', 'y']) !== 'y') {
            CLI::write('Nothing was purged.');

            return EXIT_SUCCESS;
        }

        if (LegacySettingsTransport::storedRows()->delete() === false) {
            CLI::error('Could not delete the transport settings. They are still in the database.');

            return EXIT_ERROR;
        }

        // The settings service still holds what it read before the delete.
        Services::resetSingle('settings');

        CLI::write('Purged the transport settings from the database.', 'green');

        return EXIT_SUCCESS;
    }
}
