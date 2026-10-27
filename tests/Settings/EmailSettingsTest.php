<?php

namespace Tests\Settings;

use Bonfire\Users\User;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class EmailSettingsTest extends TestCase
{
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    public function testEverySmtpSettingRoundTripsThroughTheForm(): void
    {
        $settings = [
            'Email.fromName'      => 'Ada',
            'Email.fromEmail'     => 'ada@example.com',
            'Email.protocol'      => 'smtp',
            'Email.mailPath'      => '/usr/sbin/sendmail',
            'Email.SMTPHost'      => 'smtp.example.com',
            'Email.SMTPPort'      => '587',
            'Email.SMTPUser'      => 'ada',
            'Email.SMTPPass'      => 'secret',
            'Email.SMTPCrypto'    => 'tls',
            'Email.SMTPTimeout'   => '9',
            'Email.SMTPKeepAlive' => '1',
        ];

        $this->assertRoundTrips($settings);
    }

    public function testACustomPortAndOtherProtocolRoundTrip(): void
    {
        $this->assertRoundTrips([
            'Email.fromName'      => 'Ada',
            'Email.fromEmail'     => 'ada@example.com',
            'Email.protocol'      => 'sendmail',
            'Email.SMTPPort'      => '2526',
            'Email.SMTPCrypto'    => '',
            'Email.SMTPKeepAlive' => '0',
        ]);
    }

    public function testProtocolIsASegmentedControl(): void
    {
        $html = $this->actingAs($this->user)->get('/admin/settings/email')->getBody();

        $this->assertMatchesRegularExpression('#role="radiogroup" aria-label="Protocol">.*?<input type="radio" name="protocol" value="smtp"#s', $html);
    }

    /**
     * @param array<string, string> $settings
     */
    private function assertRoundTrips(array $settings): void
    {
        foreach ($settings as $key => $value) {
            setting($key, $value);
        }

        $html   = $this->actingAs($this->user)->get('/admin/settings/email')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/email'));

        $this->actingAs($this->user)->post('/admin/settings/email', $fields)->assertSessionMissing('errors');

        foreach ($settings as $key => $value) {
            $this->assertSame($value, (string) setting($key), $key);
        }
    }
}
