<?php

namespace Tests\Commands;

use CodeIgniter\Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class EmailPruneTest extends TestCase
{
    use StreamFilterTrait;

    protected $refresh = true;

    protected function tearDown(): void
    {
        Services::resetSingle('settings');

        parent::tearDown();
    }

    public function testDeletesRowsPastTheRetentionAndReportsHowMany(): void
    {
        setting('Email.logRetentionDays', 10);
        $this->seedRow('old', 11);
        $this->seedRow('older', 40);
        $this->seedRow('recent', 2);

        command('email:prune');

        $this->assertSame(['recent'], array_column(db_connect()->table('email_log')->get()->getResultArray(), 'subject'));
        $this->assertStringContainsString('2', $this->getStreamFilterBuffer());
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
