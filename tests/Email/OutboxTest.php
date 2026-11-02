<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Email\Models\EmailLogModel;
use Bonfire\Email\Outbox;
use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;
use Config\Database;
use Config\Services;
use Myth\Postal\Email;
use Myth\Postal\LegacyEmailAdapter;
use Myth\Postal\SendResult;
use Tests\Support\Email\UsesAppMailers;
use Tests\Support\Mails\ReceiptMail;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class OutboxTest extends TestCase
{
    use UsesAppMailers;

    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('settings');
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'The Site');
        $this->useAppMailers([
            'main' => ['transport' => 'recording'],
            'down' => ['transport' => 'failing', 'error' => 'Connection refused', 'password' => 'hunter2hunter2'],
        ], 'main');
    }

    protected function tearDown(): void
    {
        Services::resetSingle('mailer');
        Services::resetSingle('settings');

        parent::tearDown();
    }

    public function testASuccessfulMailableSendLogsOneRowWithoutABody(): void
    {
        $result = (new ReceiptMail())->send();

        $this->assertTrue($result->success);

        $rows = $this->rows();
        $this->assertCount(1, $rows);
        $this->assertSame('sent', $rows[0]['status']);
        $this->assertSame('main', $rows[0]['mailer']);
        $this->assertSame('recording', $rows[0]['transport']);
        $this->assertSame(ReceiptMail::class, $rows[0]['mailable_class']);
        $this->assertSame('Your receipt', $rows[0]['subject']);
        $this->assertSame('site@example.com', $rows[0]['from_address']);
        $this->assertSame(['customer@example.com'], $rows[0]['to']);
        $this->assertSame('recorded', $rows[0]['message_id']);
        $this->assertNull($rows[0]['error']);
        $this->assertNotNull($rows[0]['created_at']);
        $this->assertStringNotContainsString('Thanks', json_encode($rows[0], JSON_THROW_ON_ERROR), 'The body is never stored');
    }

    public function testAFailedSendLogsTheError(): void
    {
        $result = service('mailer')->mailer('down')->send((new Email())->to('a@example.com')->subject('Hi')->text('Body'));

        $this->assertFalse($result->success);

        $rows = $this->rows();
        $this->assertCount(1, $rows);
        $this->assertSame('failed', $rows[0]['status']);
        $this->assertSame('Connection refused', $rows[0]['error']);
        $this->assertNull($rows[0]['message_id']);
        $this->assertNull($rows[0]['mailable_class']);
    }

    public function testAnErrorQuotingACredentialIsScrubbed(): void
    {
        $this->useAppMailers([
            'down' => ['transport' => 'failing', 'error' => 'Login failed for hunter2hunter2', 'password' => 'hunter2hunter2'],
        ], 'down');

        service('mailer')->send((new Email())->to('a@example.com')->subject('Hi')->text('Body'));

        $this->assertStringNotContainsString('hunter2hunter2', (string) $this->rows()[0]['error']);
    }

    public function testRecipientsAndAttachmentNamesAreLogged(): void
    {
        service('mailer')->send(
            (new Email())
                ->to(['a@example.com', 'Bee <b@example.com>'])
                ->cc('c@example.com')
                ->bcc('d@example.com')
                ->subject('Hi')
                ->text('Body')
                ->attachData('secret bytes', 'invoice.pdf'),
        );

        $row = $this->rows()[0];
        $this->assertSame(['a@example.com', 'b@example.com'], $row['to']);
        $this->assertSame(['c@example.com'], $row['cc']);
        $this->assertSame(['d@example.com'], $row['bcc']);
        $this->assertSame(['invoice.pdf'], $row['attachments']);
        $this->assertStringNotContainsString('secret bytes', json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function testALegacyAdapterSendLogsAnEmptyMailableClass(): void
    {
        // The framework's test service is a mock, not Postal's adapter
        $email = new LegacyEmailAdapter();
        $email->setFrom('site@example.com');
        $email->setTo('legacy@example.com');
        $email->setSubject('Old school');
        $email->setMessage('Body');

        $this->assertTrue($email->send(false));

        $rows = $this->rows();
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['mailable_class']);
        $this->assertSame(['legacy@example.com'], $rows[0]['to']);
    }

    public function testACancelledSendIsNotLogged(): void
    {
        $cancel = static fn (): bool => false;
        Events::on('email.sending', $cancel);

        try {
            $result = (new ReceiptMail())->send();
        } finally {
            Events::removeListener('email.sending', $cancel);
        }

        $this->assertTrue($result->cancelled);
        $this->assertSame([], $this->rows());
    }

    public function testASendStillSucceedsWhenTheLogTableIsMissing(): void
    {
        Database::forge()->dropTable('email_log', true);

        $result = (new ReceiptMail())->send();

        $this->assertTrue($result->success);
    }

    public function testARetentionOfZeroTurnsLoggingOff(): void
    {
        setting('Email.logRetentionDays', 0);

        (new ReceiptMail())->send();

        $this->assertSame([], $this->rows());
    }

    public function testRetentionDefaultsToThirtyDays(): void
    {
        $this->assertSame(30, Outbox::retentionDays());

        setting('Email.logRetentionDays', 7);

        $this->assertSame(7, Outbox::retentionDays());
    }

    public function testPruneDeletesRowsPastTheRetention(): void
    {
        setting('Email.logRetentionDays', 10);
        $this->seedRow('old', 11);
        $this->seedRow('recent', 9);

        $this->assertSame(1, (new Outbox())->prune());
        $this->assertSame(['recent'], array_column($this->rows(), 'subject'));
    }

    public function testPruneWithARetentionOfZeroDeletesEverything(): void
    {
        setting('Email.logRetentionDays', 0);
        $this->seedRow('today', 0);
        $this->seedRow('old', 5);

        $this->assertSame(2, (new Outbox())->prune());
        $this->assertSame([], $this->rows());
    }

    public function testAWriteCanPruneLazily(): void
    {
        setting('Email.logRetentionDays', 10);
        $this->seedRow('old', 11);

        $outbox = new Outbox(pruneOneIn: 1);
        $outbox->record((new Email())->to('a@example.com')->subject('New'), SendResult::ok());

        $this->assertSame(['New'], array_column($this->rows(), 'subject'));
    }

    public function testAWriteDoesNotPruneWhenLazyPruningIsOff(): void
    {
        setting('Email.logRetentionDays', 10);
        $this->seedRow('old', 11);

        $outbox = new Outbox(pruneOneIn: 0);
        $outbox->record((new Email())->to('a@example.com')->subject('New'), SendResult::ok());

        $this->assertSame(['old', 'New'], array_column($this->rows(), 'subject'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return (new EmailLogModel())->orderBy('id')->findAll();
    }

    private function seedRow(string $subject, int $daysOld): void
    {
        db_connect()->table('email_log')->insert([
            'subject'    => $subject,
            'status'     => 'sent',
            'to'         => '[]',
            'created_at' => Time::now()->subDays($daysOld)->toDateTimeString(),
        ]);
    }
}
