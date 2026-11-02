<?php

namespace Tests\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\DotEnv;
use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\Mock\MockInputOutput;
use CodeIgniter\Test\StreamFilterTrait;
use Myth\Postal\Config\Mailer as PostalMailer;
use Myth\Postal\LegacyConfigMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class EmailEnvTest extends TestCase
{
    use StreamFilterTrait;

    private const TRANSPORT_KEYS = [
        'protocol',
        'mailPath',
        'SMTPHost',
        'SMTPPort',
        'SMTPUser',
        'SMTPPass',
        'SMTPCrypto',
        'SMTPTimeout',
        'SMTPKeepAlive',
    ];

    protected $refresh = true;

    /**
     * @var list<string>
     */
    private array $envKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSharedState();
    }

    protected function tearDown(): void
    {
        foreach ($this->envKeys as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        CLI::resetInputOutput();
        $this->resetSharedState();

        parent::tearDown();
    }

    private function resetSharedState(): void
    {
        Services::resetSingle('settings');
        Factories::reset('config');
    }

    /**
     * Loads the printed .env lines with CodeIgniter's own parser and the printed
     * class as an app would, so this fails if the keys stop reaching the config.
     *
     * @param array<string, mixed> $settings
     */
    #[DataProvider('providePrintedEnvAndSnippetBuildTheBridgesMailer')]
    public function testPrintedEnvAndSnippetBuildTheBridgesMailer(array $settings, string $name): void
    {
        foreach ($settings as $key => $value) {
            setting('Email.' . $key, $value);
        }

        $this->assertSame(EXIT_SUCCESS, service('commands')->run('bonfire:email-env', []));

        $output = $this->getStreamFilterBuffer();

        $this->loadEnv($output);
        $config = $this->loadSnippet($output);

        $this->assertSame($name, $config->default);
        $this->assertSame([$name], array_keys($config->mailers));
        $this->assertSame(LegacyConfigMapper::toMailer($settings), $config->mailers[$name]);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function providePrintedEnvAndSnippetBuildTheBridgesMailer(): iterable
    {
        return [
            'smtp with credentials' => [
                [
                    'protocol'      => 'smtp',
                    'SMTPHost'      => 'smtp.example.com',
                    'SMTPPort'      => 587,
                    'SMTPUser'      => 'mailer',
                    'SMTPPass'      => 'p#ss "word" $1 \\',
                    'SMTPCrypto'    => '',
                    'SMTPTimeout'   => 9,
                    'SMTPKeepAlive' => true,
                ],
                'smtp',
            ],
            'smtp without credentials' => [
                ['protocol' => 'smtp', 'SMTPHost' => 'relay.internal', 'SMTPPort' => '25'],
                'smtp',
            ],
            'sendmail' => [
                ['protocol' => 'sendmail', 'mailPath' => '/opt/bin/send mail'],
                'sendmail',
            ],
            'mail' => [
                ['protocol' => 'mail', 'SMTPHost' => 'ignored.example.com'],
                'mail',
            ],
        ];
    }

    public function testPasswordIsOnlyInTheEnvLinesNotTheSnippet(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'hunter2');

        command('bonfire:email-env');

        $output = $this->getStreamFilterBuffer();

        $this->assertSame(1, substr_count($output, 'hunter2'));
        $this->assertMatchesRegularExpression('/^mailer\.mailers\.smtp\.password = "hunter2"$/m', $output);
    }

    public function testPrintsDsnSoTheMailerStillMatchesTheBridge(): void
    {
        $settings = ['protocol' => 'smtp', 'SMTPHost' => 'smtp.example.com', 'DSN' => true];

        foreach ($settings as $key => $value) {
            setting('Email.' . $key, $value);
        }

        command('bonfire:email-env');

        $config = $this->loadSnippet($this->getStreamFilterBuffer());

        $this->assertSame(LegacyConfigMapper::toMailer($settings), $config->mailers['smtp']);
    }

    #[DataProvider('provideWarnsWhenAValueWouldNotSurviveTheEnvRoute')]
    public function testWarnsWhenAValueWouldNotSurviveTheEnvRoute(string $password): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', $password);

        command('bonfire:email-env');

        $this->assertMatchesRegularExpression('/warning.*mailer\.mailers\.smtp\.password/i', $this->getStreamFilterBuffer());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideWarnsWhenAValueWouldNotSurviveTheEnvRoute(): iterable
    {
        return [
            'trailing quote' => ['secret"'],
            'leading quote'  => ["'secret"],
            'boolean word'   => ['true'],
            'nested env'     => ['pa${HOME}ss'],
            'line break'     => ["sec\nret"],
        ];
    }

    public function testNoWarningForAnOrdinaryPassword(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'p#ss "word" $1');

        command('bonfire:email-env');

        $this->assertDoesNotMatchRegularExpression('/warning/i', $this->getStreamFilterBuffer());
    }

    public function testNothingToMigrateWithOnlyFrameworkDefaults(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('bonfire:email-env', []));

        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString('Nothing to migrate', $output);
        $this->assertStringNotContainsString('<?php', $output);
    }

    public function testFromRowsAloneAreNothingToMigrate(): void
    {
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'Site');

        command('bonfire:email-env');

        $this->assertStringContainsString('Nothing to migrate', $this->getStreamFilterBuffer());
    }

    public function testLabelsEachValuesSource(): void
    {
        setting('Email.SMTPPass', 'from-db');
        $this->setEnv('email.SMTPUser', 'env-user');
        config('Email')->SMTPHost = 'file.example.com';
        config('Email')->protocol = 'smtp';

        command('bonfire:email-env');

        $output = $this->getStreamFilterBuffer();

        $this->assertMatchesRegularExpression('/^\s*SMTPPass\s+database$/m', $output);
        $this->assertMatchesRegularExpression('/^\s*SMTPHost\s+Config\\\\Email$/m', $output);
        $this->assertMatchesRegularExpression('/^\s*SMTPUser\s+\.env \(email\.SMTPUser\)$/m', $output);
        $this->assertStringNotContainsString('Nothing to migrate', $output);
        $this->assertMatchesRegularExpression('/^mailer\.mailers\.smtp\.username = "env-user"$/m', $output);
    }

    public function testDatabaseWinsOverEnvInTheLabelAndTheValue(): void
    {
        $this->setEnv('email.SMTPHost', 'env.example.com');

        setting('Email.protocol', 'smtp');
        setting('Email.SMTPHost', 'db.example.com');

        command('bonfire:email-env');

        $output = $this->getStreamFilterBuffer();

        $this->assertMatchesRegularExpression('/^\s*SMTPHost\s+database$/m', $output);
        $this->assertMatchesRegularExpression('/^mailer\.mailers\.smtp\.host = "db\.example\.com"$/m', $output);
    }

    public function testEnvOnlyInstallIsToldThereIsNothingToPurge(): void
    {
        $this->setEnv('email.SMTPHost', 'env.example.com');
        $this->useOwnMailerConfig();

        $this->assertSame(EXIT_SUCCESS, $this->purgeForced());

        $output = $this->getStreamFilterBuffer();

        $this->assertStringNotContainsString('Nothing to migrate', $output);
        $this->assertStringContainsString('to purge', $output);
    }

    public function testPurgeWithNoRowsSucceedsWhileTheBridgeIsActive(): void
    {
        $this->setEnv('email.SMTPHost', 'env.example.com');

        $this->assertSame(EXIT_SUCCESS, $this->purgeForced());
    }

    public function testConfigFileValuesAreMigrated(): void
    {
        config('Email')->protocol = 'sendmail';

        command('bonfire:email-env');

        $output = $this->getStreamFilterBuffer();

        $this->assertStringNotContainsString('Nothing to migrate', $output);
        $this->assertMatchesRegularExpression('/^mailer\.mailers\.sendmail\.path = /m', $output);
    }

    public function testWritesNoFiles(): void
    {
        setting('Email.protocol', 'smtp');

        $before = is_file(APPPATH . 'Config/Mailer.php');

        command('bonfire:email-env');

        $this->assertSame($before, is_file(APPPATH . 'Config/Mailer.php'));
    }

    public function testPurgeRefusesWhileTheBridgeIsActive(): void
    {
        $this->seedTransportRows();

        foreach ([['purge' => null], ['purge' => null, 'force' => null]] as $params) {
            $this->assertSame(EXIT_ERROR, service('commands')->run('bonfire:email-env', $params));
            $this->assertStringContainsString('app/Config/Mailer.php', $this->getStreamFilterBuffer());
            $this->assertSame(count(self::TRANSPORT_KEYS), $this->transportRowCount());
        }
    }

    public function testPurgeWithForceRemovesOnlyTheTransportKeys(): void
    {
        $this->useOwnMailerConfig();
        $this->seedTransportRows();
        setting('Email.fromName', 'Site');
        setting('Email.fromEmail', 'site@example.com');
        setting('Site.siteName', 'Kept');

        $this->assertSame(EXIT_SUCCESS, $this->purgeForced());

        $this->assertSame(0, $this->transportRowCount());
        $this->assertSame(1, $this->db->table('settings')->where('key', 'fromName')->countAllResults());
        $this->assertSame(1, $this->db->table('settings')->where('key', 'fromEmail')->countAllResults());
        $this->assertSame(1, $this->db->table('settings')->where('key', 'siteName')->countAllResults());
    }

    public function testPurgeDropsTheCachedValuesToo(): void
    {
        $this->useOwnMailerConfig();
        setting('Email.SMTPPass', 'hunter2');

        $this->purgeForced();

        $this->assertSame('', setting('Email.SMTPPass'));
    }

    public function testPurgeAlsoRemovesRowsStoredUnderAContext(): void
    {
        $this->useOwnMailerConfig();
        service('settings')->set('Email.SMTPPass', 'hunter2', 'tenant-1');

        $this->purgeForced();

        $this->assertStringNotContainsString('Nothing to migrate', $this->getStreamFilterBuffer());
        $this->assertSame(0, $this->transportRowCount());
    }

    public function testContextRowsAloneStillRefuseWhileTheBridgeIsActive(): void
    {
        service('settings')->set('Email.SMTPPass', 'hunter2', 'tenant-1');

        $this->assertSame(EXIT_ERROR, $this->purgeForced());
        $this->assertSame(1, $this->transportRowCount());
    }

    public function testPurgeAndForceWorkAsCommandLineOptions(): void
    {
        $this->useOwnMailerConfig();
        $this->seedTransportRows();

        command('bonfire:email-env --purge --force');

        $this->assertSame(0, $this->transportRowCount());
    }

    public function testPurgeAsksAndDeletesOnYes(): void
    {
        $this->useOwnMailerConfig();
        $this->seedTransportRows();
        $io = $this->mockInput('y');

        $this->assertSame(EXIT_SUCCESS, service('commands')->run('bonfire:email-env', ['purge' => null]));

        $this->assertMatchesRegularExpression('/\?.*n.*y.*: y$/m', $io->getOutput());
        $this->assertSame(0, $this->transportRowCount());
    }

    public function testPurgeKeepsRowsOnNo(): void
    {
        $this->useOwnMailerConfig();
        $this->seedTransportRows();
        $this->mockInput('n');

        $this->assertSame(EXIT_SUCCESS, service('commands')->run('bonfire:email-env', ['purge' => null]));

        $this->assertSame(count(self::TRANSPORT_KEYS), $this->transportRowCount());
    }

    public function testWithoutPurgeRowsStay(): void
    {
        $this->useOwnMailerConfig();
        $this->seedTransportRows();

        command('bonfire:email-env');

        $this->assertSame(count(self::TRANSPORT_KEYS), $this->transportRowCount());
    }

    private function mockInput(string $answer): MockInputOutput
    {
        $io = new MockInputOutput();
        $io->setInputs([$answer]);
        CLI::setInputOutput($io);

        return $io;
    }

    private function useOwnMailerConfig(): void
    {
        Factories::injectMock('config', 'Mailer', new class () extends PostalMailer {});
    }

    private function seedTransportRows(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.mailPath', '/usr/sbin/sendmail');
        setting('Email.SMTPHost', 'smtp.example.com');
        setting('Email.SMTPPort', 587);
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'hunter2');
        setting('Email.SMTPCrypto', 'tls');
        setting('Email.SMTPTimeout', 5);
        setting('Email.SMTPKeepAlive', false);
    }

    private function transportRowCount(): int
    {
        return $this->db->table('settings')->whereIn('key', self::TRANSPORT_KEYS)->countAllResults();
    }

    private function setEnv(string $key, string $value): void
    {
        $this->envKeys[] = $key;

        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");

        // BaseConfig reads the environment when a config is built.
        Factories::reset('config');
    }

    private function purgeForced(): int
    {
        return service('commands')->run('bonfire:email-env', ['purge' => null, 'force' => null]);
    }

    private function loadEnv(string $output): void
    {
        preg_match_all('/^mailer\.\S+ = .*$/m', $output, $matches);

        $dir = sys_get_temp_dir() . '/bf-email-env-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/.env', implode("\n", $matches[0]) . "\n");

        $vars = (new DotEnv($dir))->parse() ?? [];

        unlink($dir . '/.env');
        rmdir($dir);

        array_push($this->envKeys, ...array_keys($vars));
    }

    /**
     * Requires the printed class under a unique namespace. Its short name stays
     * Mailer, which is the prefix BaseConfig looks for in the environment.
     */
    private function loadSnippet(string $output): PostalMailer
    {
        $this->assertSame(1, preg_match('/<\?php.*^}$/ms', $output, $matches));

        $namespace = 'Tests\Generated\Mailer' . bin2hex(random_bytes(4));
        $code      = str_replace('namespace Config;', "namespace {$namespace};", $matches[0]);
        $file      = tempnam(sys_get_temp_dir(), 'bf-mailer');
        file_put_contents($file, $code);

        require $file;
        unlink($file);

        $class = $namespace . '\Mailer';

        return new $class();
    }
}
