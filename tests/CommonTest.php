<?php

namespace Tests\Bonfire;

use CodeIgniter\I18n\Time;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class CommonTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        helper('setting');
    }

    #[DataProvider('provideAppDate')]
    public function testAppDate(string $format, string $timeFormat, bool $includeTime, string $expected)
    {
        $time = '2021-01-15 15:32:00';
        Time::setTestNow($time, 'America/Chicago');

        setting('App.dateFormat', $format);
        setting('App.timeFormat', $timeFormat);

        $this->assertSame($expected, \app_date($time, $includeTime));
    }

    public static function provideAppDate(): iterable
    {
        return [
            ['m/d/Y', 'g:i A', false, '01/15/2021'],
            ['m/d/Y', 'g:i A', true, '01/15/2021 3:32 PM'],
            ['m/d/Y', 'H:i', true, '01/15/2021 15:32'],
            ['d/m/Y', 'g:i A', false, '15/01/2021'],
            ['M j, Y', 'g:i A', false, 'Jan 15, 2021'],
        ];
    }

    #[DataProvider('provideAdminAccent')]
    public function testAdminAccent(?string $stored, string $expected)
    {
        setting('Site.adminAccent', $stored);

        $this->assertSame($expected, \admin_accent());
    }

    public static function provideAdminAccent(): iterable
    {
        return [
            'valid'     => ['#2F6FEB', '#2F6FEB'],
            'lowercase' => ['#0f9f6e', '#0f9f6e'],
            'unset'     => [null, '#D9622B'],
            'not a hex' => ['red', '#D9622B'],
            'injection' => ['#fff;}body{display:none', '#D9622B'],
        ];
    }
}
