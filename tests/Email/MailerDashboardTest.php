<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Email\LegacySettingsTransport;
use Bonfire\Email\MailerDashboard;
use CodeIgniter\Config\Factories;
use Config\Services;
use Myth\Postal\Config\Mailer as PostalMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Tests\Support\Email\FailingTransport;
use Tests\Support\Email\RecordingTransport;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MailerDashboardTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingTransport::reset();
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'The Site');
    }

    protected function tearDown(): void
    {
        Factories::reset('config');

        foreach (['mailer', 'settings'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    /**
     * @param array<string, mixed>        $definition
     * @param array<string, list<string>> $expected   key => [value, masked as 'masked' or 'shown']
     */
    #[DataProvider('provideSettingsAreMaskedByKeyName')]
    public function testSettingsAreMaskedByKeyName(array $definition, array $expected): void
    {
        $rows = $this->dashboard(['m' => ['transport' => 'log', ...$definition]])->mailers()[0]['settings'];

        $actual = [];

        foreach ($rows as $row) {
            $actual[$row['key']] = [$row['value'], $row['masked'] ? 'masked' : 'shown'];
        }

        $this->assertSame($expected, $actual);
    }

    public static function provideSettingsAreMaskedByKeyName(): iterable
    {
        yield 'each pattern word, any case' => [
            ['password' => 'a', 'clientSecret' => 'b', 'API_KEY' => 'c', 'token' => 'd', 'CREDENTIAL' => 'e', 'authType' => 'f'],
            [
                'password'     => ['set', 'masked'],
                'clientSecret' => ['set', 'masked'],
                'API_KEY'      => ['set', 'masked'],
                'token'        => ['set', 'masked'],
                'CREDENTIAL'   => ['set', 'masked'],
                'authType'     => ['set', 'masked'],
            ],
        ];

        yield 'empty values say so' => [
            ['password' => '', 'secret' => null, 'token' => [], 'auth' => false],
            [
                'password' => ['empty', 'masked'],
                'secret'   => ['empty', 'masked'],
                'token'    => ['empty', 'masked'],
                'auth'     => ['empty', 'masked'],
            ],
        ];

        yield 'a password of 0 is still set' => [
            ['password' => '0'],
            ['password' => ['set', 'masked']],
        ];

        yield 'nested secrets, beside values that are safe to show' => [
            ['region' => 'eu-west-1', 'dkim' => ['selector' => 'mail', 'privateKey' => 'abc', 'nested' => ['passphrase' => 'def']]],
            [
                'region'                 => ['eu-west-1', 'shown'],
                'dkim.selector'          => ['mail', 'shown'],
                'dkim.privateKey'        => ['set', 'masked'],
                'dkim.nested.passphrase' => ['set', 'masked'],
            ],
        ];

        yield 'a matching array is masked whole' => [
            ['credentials' => ['id' => 'abc', 'secret' => 'def']],
            ['credentials' => ['set', 'masked']],
        ];

        yield 'plain values print' => [
            ['host' => 'smtp.example.com', 'port' => 587, 'keepAlive' => true, 'tls' => false, 'cc' => null, 'hosts' => ['a', 'b']],
            [
                'host'      => ['smtp.example.com', 'shown'],
                'port'      => ['587', 'shown'],
                'keepAlive' => ['true', 'shown'],
                'tls'       => ['false', 'shown'],
                'cc'        => ['null', 'shown'],
                'hosts.0'   => ['a', 'shown'],
                'hosts.1'   => ['b', 'shown'],
            ],
        ];

        yield 'an object is never printed' => [
            ['client' => new stdClass()],
            ['client' => ['set', 'masked']],
        ];

        yield 'credentials inside a URL are removed' => [
            ['dsn' => 'smtp://user:hunter2@mail.example.com:587', 'nested' => ['url' => 'https://token@api.example.com/send']],
            [
                'dsn'        => ['smtp://***@mail.example.com:587', 'shown'],
                'nested.url' => ['https://***@api.example.com/send', 'shown'],
            ],
        ];

        yield 'a URL password holding a slash or an @ is removed whole' => [
            ['dsn' => 'smtp://user:pa/ss@wd@mail.example.com'],
            ['dsn' => ['smtp://***@mail.example.com', 'shown']],
        ];

        yield 'a URL without credentials is left alone' => [
            ['endpoint' => 'https://api.example.com/send'],
            ['endpoint' => ['https://api.example.com/send', 'shown']],
        ];
    }

    public function testListsEveryMailerWithItsTransportAndMarksTheDefault(): void
    {
        $mailers = $this->dashboard([
            'main'   => ['transport' => 'smtp', 'host' => 'smtp.example.com'],
            'backup' => ['transport' => 'log'],
        ], 'main')->mailers();

        $this->assertSame(['main', 'backup'], array_column($mailers, 'name'));
        $this->assertSame(['smtp', 'log'], array_column($mailers, 'transport'));
        $this->assertSame([true, false], array_column($mailers, 'isDefault'));
        $this->assertSame(['host'], array_column($mailers[0]['settings'], 'key'));
    }

    public function testFailoverShowsItsChainAndFlagsMissingChildren(): void
    {
        $mailers = $this->dashboard([
            'both' => ['transport' => 'failover', 'chain' => ['main', 'gone']],
            'main' => ['transport' => 'log', 'chain' => ['not', 'a', 'failover']],
        ])->mailers();

        $this->assertSame([['name' => 'main', 'defined' => true], ['name' => 'gone', 'defined' => false]], $mailers[0]['chain']);
        $this->assertSame([], $mailers[0]['settings']);
        $this->assertFalse($mailers[0]['loops']);
        $this->assertNull($mailers[1]['chain']);
        $this->assertSame('chain.0', $mailers[1]['settings'][0]['key']);
    }

    public function testFlagsChainsThatLoop(): void
    {
        $mailers = $this->dashboard([
            'entry'   => ['transport' => 'failover', 'chain' => ['a']],
            'a'       => ['transport' => 'failover', 'chain' => ['b']],
            'b'       => ['transport' => 'failover', 'chain' => ['a']],
            'diamond' => ['transport' => 'failover', 'chain' => ['leaf', 'leaf']],
            'leaf'    => ['transport' => 'log'],
        ])->mailers();

        $this->assertSame([true, true, true, false, false], array_column($mailers, 'loops'));
    }

    public function testFlagsAnUnregisteredTransport(): void
    {
        $mailers = $this->dashboard([
            'odd'  => ['transport' => 'nope'],
            'none' => [],
            'bad'  => 'smtp',
            'fine' => ['transport' => 'log'],
        ])->mailers();

        $this->assertSame([true, true, true, false], array_column($mailers, 'unknownTransport'));
    }

    /**
     * @param array<string, array<string, mixed>> $mailers
     * @param list<string>                        $expected
     */
    #[DataProvider('provideWarnings')]
    public function testWarnings(array $mailers, string $default, array $expected): void
    {
        $this->assertSame($expected, $this->dashboard($mailers, $default)->warnings());
    }

    public static function provideWarnings(): iterable
    {
        yield 'a working default' => [['main' => ['transport' => 'log']], 'main', []];

        yield 'null default' => [['null' => ['transport' => 'null']], 'null', ['nullDefault']];

        yield 'a renamed null default' => [['off' => ['transport' => 'null']], 'off', ['nullDefault']];

        yield 'undefined default' => [['main' => ['transport' => 'log']], 'gone', ['undefinedDefault']];
    }

    public function testWarnsAboutLeftoverRowsOnlyOnceTheAppHasItsOwnConfig(): void
    {
        setting('Email.SMTPPass', 'hunter2');

        $this->assertSame(['leftoverRows'], $this->dashboard(['main' => ['transport' => 'log']], 'main')->warnings());
        $this->assertSame(['bridge'], $this->bridgedDashboard()->warnings());
    }

    public function testBridgedMailerShowsTheResolvedSettingsMasked(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPHost', 'smtp.example.com');
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'hunter2');

        $mailers = array_column($this->bridgedDashboard()->mailers(), null, 'name');
        $legacy  = $mailers['legacy'];

        $this->assertTrue($legacy['isDefault']);
        $this->assertTrue($legacy['bridged']);
        $this->assertSame('smtp', $legacy['transport']);
        $this->assertFalse($legacy['unknownTransport']);

        $values = array_column($legacy['settings'], 'value', 'key');
        $this->assertSame('smtp.example.com', $values['host']);
        $this->assertSame('set', $values['password']);
        $this->assertFalse($mailers['log']['bridged']);
    }

    public function testTestSendGoesToTheChosenMailerWithTheSavedFrom(): void
    {
        $dashboard = $this->dashboard([
            'first'  => ['transport' => 'recording', 'label' => 'first'],
            'second' => ['transport' => 'recording', 'label' => 'second'],
        ], 'first');

        $result = $dashboard->sendTest('second', 'someone@example.com');

        $this->assertTrue($result->success);
        $this->assertCount(1, RecordingTransport::$sent);

        ['settings' => $settings, 'email' => $email] = RecordingTransport::$sent[0];

        $this->assertSame('second', $settings['label']);
        $this->assertSame(['someone@example.com'], array_map(static fn ($a) => $a->email, $email->to));
        $this->assertSame('site@example.com', $email->from->email);
        $this->assertSame('The Site', $email->from->name);
    }

    public function testTestSendRefusesAMailerThatIsNotDefined(): void
    {
        $result = $this->dashboard(['first' => ['transport' => 'recording']], 'first')->sendTest('gone', 'someone@example.com');

        $this->assertFalse($result->success);
        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testTestSendRefusesALoopingChainInsteadOfOverflowingTheStack(): void
    {
        $result = $this->dashboard([
            'a' => ['transport' => 'failover', 'chain' => ['b']],
            'b' => ['transport' => 'failover', 'chain' => ['a']],
        ])->sendTest('a', 'someone@example.com');

        $this->assertFalse($result->success);
        $this->assertNotSame('', (string) $result->error);
    }

    public function testTestSendNeedsAFromAddress(): void
    {
        setting('Email.fromEmail', '');

        $result = $this->dashboard(['first' => ['transport' => 'recording']], 'first')->sendTest('first', 'someone@example.com');

        $this->assertFalse($result->success);
        $this->assertSame([], RecordingTransport::$sent);
    }

    /**
     * @param array<string, mixed> $definition
     */
    #[DataProvider('provideFailuresHaveKnownSecretsRemoved')]
    public function testFailuresHaveKnownSecretsRemoved(array $definition, string $expected): void
    {
        $result = $this->dashboard(['main' => ['transport' => 'failing', ...$definition]], 'main')->sendTest('main', 'someone@example.com');

        $this->assertFalse($result->success);
        $this->assertSame($expected, $result->error);
    }

    public static function provideFailuresHaveKnownSecretsRemoved(): iterable
    {
        yield 'returned by the transport' => [
            ['password' => 'hunter2', 'error' => 'Login hunter2 refused'],
            'Login *** refused',
        ];

        yield 'thrown by the transport' => [
            ['nested' => ['apiKey' => 'abc123'], 'error' => 'Bad key abc123', 'throw' => true],
            'Bad key ***',
        ];

        yield 'a longer secret holding a shorter one' => [
            ['password' => 'hunter', 'token' => 'hunter2', 'error' => 'hunter2 and hunter'],
            '*** and ***',
        ];

        yield 'URL credentials, encoded or not' => [
            ['dsn' => 'smtp://user:p%40ss@mail.example.com', 'error' => 'Refused user:p%40ss and p@ss'],
            'Refused *** and ***',
        ];
    }

    public function testFailuresHaveTheSecretsOfFailoverChildrenRemoved(): void
    {
        $result = $this->dashboard([
            'main'  => ['transport' => 'failing', 'password' => 'hunter2', 'error' => 'Login hunter2 refused'],
            'chain' => ['transport' => 'failover', 'chain' => ['main']],
        ], 'chain')->sendTest('chain', 'someone@example.com');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Login *** refused', (string) $result->error);
    }

    public function testBridgeFailuresHaveTheResolvedPasswordRemoved(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPHost', 'localhost');
        setting('Email.SMTPPort', 1);
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'hunter2');

        $this->assertSame('Bad *** login', $this->bridgedDashboard()->scrub('legacy', 'Bad hunter2 login'));
    }

    #[DataProvider('provideDeliverySaysWhatReallyHappensToTheMessage')]
    public function testDeliverySaysWhatReallyHappensToTheMessage(string $transport, string $expected): void
    {
        $this->assertSame($expected, $this->dashboard(['m' => ['transport' => $transport]], 'm')->delivery('m'));
    }

    public static function provideDeliverySaysWhatReallyHappensToTheMessage(): iterable
    {
        yield ['null', 'discarded'];

        yield ['log', 'logged'];

        yield ['smtp', 'sent'];

        yield ['failover', 'sent'];
    }

    private function bridgedDashboard(): MailerDashboard
    {
        Factories::reset('config');
        LegacySettingsTransport::register();

        return new MailerDashboard(config('Mailer'));
    }

    /**
     * Makes this the app's own Mailer config, so the bridge steps aside.
     *
     * @param array<string, mixed> $mailers
     */
    private function dashboard(array $mailers, string $default = 'null'): MailerDashboard
    {
        $config                          = new class () extends PostalMailer {};
        $config->mailers                 = $mailers;
        $config->default                 = $default;
        $config->transports['recording'] = RecordingTransport::class;
        $config->transports['failing']   = FailingTransport::class;

        Factories::injectMock('config', 'Mailer', $config);
        Services::resetSingle('mailer');

        return new MailerDashboard($config);
    }
}
