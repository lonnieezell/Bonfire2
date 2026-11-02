<?php

declare(strict_types=1);

namespace Tests\Email;

use Config\Services;
use Myth\Postal\Mailable;
use Myth\Postal\Mailer;
use Myth\Postal\Transport\FakeTransport;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DefaultFromTest extends TestCase
{
    protected $refresh = true;
    private FakeTransport $mail;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('settings');
        $this->mail = Mailer::fake();
    }

    protected function tearDown(): void
    {
        Services::resetSingle('mailer');
        Services::resetSingle('settings');

        parent::tearDown();
    }

    public function testAMessageWithoutAFromGetsTheSettingsFrom(): void
    {
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'The Site');

        $this->mailable()->send();

        $from = $this->mail->sent()[0]->from;
        $this->assertSame('site@example.com', $from?->email);
        $this->assertSame('The Site', $from->name);
    }

    public function testAMessageWithoutAFromGetsTheSettingsAddressWhenThereIsNoName(): void
    {
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', '');

        $this->mailable()->send();

        $from = $this->mail->sent()[0]->from;
        $this->assertSame('site@example.com', $from?->email);
        $this->assertSame('', $from->name);
    }

    public function testAMessageWithItsOwnFromKeepsIt(): void
    {
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'The Site');

        $this->mailable('sales@example.com', 'Sales')->send();

        $from = $this->mail->sent()[0]->from;
        $this->assertSame('sales@example.com', $from?->email);
        $this->assertSame('Sales', $from->name);
    }

    public function testNoSettingsFromLeavesTheMessageWithoutOne(): void
    {
        setting('Email.fromEmail', '');

        $this->assertTrue($this->mailable()->send()->success);

        $this->assertNull($this->mail->sent()[0]->from);
    }

    private function mailable(?string $from = null, string $name = ''): Mailable
    {
        return new class ($from, $name) extends Mailable {
            public function __construct(private readonly ?string $fromAddress, private readonly string $fromName)
            {
                parent::__construct();
            }

            protected function build(): void
            {
                if ($this->fromAddress !== null) {
                    $this->from($this->fromAddress, $this->fromName);
                }

                $this->to('user@example.com')->subject('Hello')->text('Hello');
            }
        };
    }
}
