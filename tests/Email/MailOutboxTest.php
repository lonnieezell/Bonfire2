<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Tests\Support\Mails\ReceiptMail;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MailOutboxTest extends TestCase
{
    protected $refresh = true;
    private User $admin;

    /**
     * @var array<string, string>
     */
    private array $namespaces = [];

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        $this->namespaces                                           = config('Postal')->mailableNamespaces;
        config('Postal')->mailableNamespaces['Tests\Support\Mails'] = SUPPORTPATH . 'Mails';

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        config('Postal')->mailableNamespaces = $this->namespaces;

        foreach (['auth', 'bonfire', 'menus', 'settings', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testListsEveryRowNewestFirst(): void
    {
        $first  = $this->log(['subject' => 'First', 'created_at' => '2026-10-01 10:00:00']);
        $second = $this->log(['subject' => 'Second', 'created_at' => '2026-10-02 10:00:00']);

        $this->assertSame([$second, $first], $this->listed());
    }

    public function testARowShowsItsStatusRecipientsAndError(): void
    {
        $id = $this->log(['status' => 'failed', 'to' => ['a@example.com'], 'cc' => ['c@example.com'], 'error' => 'Connection refused']);

        $response = $this->open();

        $this->assertSame('failed', $this->xpath($response)->query('//*[@data-row="' . $id . '"]//*[@data-status]')->item(0)->getAttribute('data-status'));
        $this->assertStringContainsString('a@example.com', $this->cell($response, $id, 'recipients'));
        $this->assertStringContainsString('c@example.com', $this->cell($response, $id, 'recipients'));
        $this->assertSame('Connection refused', $this->cell($response, $id, 'error'));
    }

    public function testFiltersByStatus(): void
    {
        $sent   = $this->log(['status' => 'sent']);
        $failed = $this->log(['status' => 'failed']);

        $this->assertSame([$failed], $this->listed(['status' => 'failed']));
        $this->assertSame([$sent], $this->listed(['status' => 'sent']));
        $this->assertSame([$failed, $sent], $this->listed(['status' => 'nonsense']));
    }

    public function testFiltersByMailer(): void
    {
        $this->log(['mailer' => 'smtp']);
        $ses = $this->log(['mailer' => 'ses']);

        $this->assertSame([$ses], $this->listed(['mailer' => 'ses']));
    }

    public function testFiltersByRecipientInToCcAndBcc(): void
    {
        $to  = $this->log(['to' => ['ann@example.com']]);
        $cc  = $this->log(['to' => ['x@example.com'], 'cc' => ['ann@example.org']]);
        $bcc = $this->log(['to' => ['y@example.com'], 'bcc' => ['ann@example.net']]);
        $this->log(['to' => ['bob@example.com']]);

        $this->assertSame([$bcc, $cc, $to], $this->listed(['recipient' => 'ann@']));
        $this->assertSame([$cc], $this->listed(['recipient' => 'ann@example.org']));
    }

    public function testARecipientFilterTreatsWildcardsAsText(): void
    {
        $this->log(['to' => ['ann@example.com']]);

        $this->assertSame([], $this->listed(['recipient' => '%']));
        $this->assertSame([], $this->listed(['recipient' => 'a_n@']));
    }

    public function testFiltersByDateRangeInclusively(): void
    {
        $this->log(['created_at' => '2026-10-01 23:59:59']);
        $start = $this->log(['created_at' => '2026-10-02 00:00:00']);
        $end   = $this->log(['created_at' => '2026-10-03 23:59:59']);
        $this->log(['created_at' => '2026-10-04 00:00:00']);

        $this->assertSame([$end, $start], $this->listed(['date_from' => '2026-10-02', 'date_to' => '2026-10-03']));
        $this->assertSame([$end, $start], $this->listed(['date_from' => '2026-10-02', 'date_to' => '2026-10-03', 'junk' => 'x']));
    }

    public function testAnInvalidDateIsIgnored(): void
    {
        $id = $this->log([]);

        $this->assertSame([$id], $this->listed(['date_from' => 'yesterday', 'date_to' => '2026-13-45']));
    }

    public function testBlankFilterFieldsAreNotActiveFilters(): void
    {
        $this->log([]);

        $response = $this->open(['status' => '', 'mailer' => '', 'recipient' => ' ', 'date_from' => '', 'date_to' => '']);

        $this->assertCount(0, $this->xpath($response)->query('//a[@data-reset]'));
        $this->assertCount(1, $this->xpath($response)->query('//*[@data-row]'));
    }

    public function testFiltersCombine(): void
    {
        $this->log(['status' => 'failed', 'mailer' => 'smtp']);
        $match = $this->log(['status' => 'failed', 'mailer' => 'ses', 'to' => ['ann@example.com']]);
        $this->log(['status' => 'sent', 'mailer' => 'ses', 'to' => ['ann@example.com']]);

        $this->assertSame([$match], $this->listed(['status' => 'failed', 'mailer' => 'ses', 'recipient' => 'ann']));
    }

    public function testTheFilterFormOffersTheMailersInTheLog(): void
    {
        $this->log(['mailer' => 'smtp']);
        $this->log(['mailer' => 'ses']);
        $this->log(['mailer' => 'smtp']);
        $this->log(['mailer' => null]);

        $options = [];

        foreach ($this->xpath($this->open())->query('//form[@data-filters]//select[@name="mailer"]/option') as $option) {
            /** @var DOMElement $option */
            $options[] = $option->getAttribute('value');
        }

        $this->assertSame(['', 'ses', 'smtp'], $options);
    }

    public function testAMailableRowLinksToItsPreview(): void
    {
        $mailable = $this->log(['mailable_class' => ReceiptMail::class]);
        $legacy   = $this->log(['mailable_class' => null]);
        $removed  = $this->log(['mailable_class' => 'App\Mails\RemovedMail']);

        $response = $this->open();

        $this->assertSame(url_to('mail-preview', 'Tests.Support.Mails.ReceiptMail'), $this->link($response, $mailable));
        $this->assertNull($this->link($response, $legacy));
        $this->assertNull($this->link($response, $removed));
    }

    public function testNoPreviewLinkWithoutThePreviewPermission(): void
    {
        $id = $this->log(['mailable_class' => ReceiptMail::class]);

        $user = $this->createUser();
        $user->addPermission('admin.access', 'email.log');

        $response = $this->actingAs($user)->get(route_to('mail-outbox'));

        $response->assertOK();
        $this->assertNull($this->link($response, $id));
        $this->assertNotNull($this->xpath($response)->query('//*[@data-row="' . $id . '"]')->item(0));
    }

    public function testUsersWithoutThePermissionGetNotFound(): void
    {
        $user = $this->createUser();
        $user->addGroup('developer');

        $this->expectException(PageNotFoundException::class);

        $this->actingAs($user)->get(route_to('mail-outbox'));
    }

    public function testUsersWithoutThePermissionSeeNoMenuItem(): void
    {
        $user = $this->createUser();
        $user->addGroup('developer');

        $body = (string) $this->actingAs($user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringNotContainsString(url_to('mail-outbox'), $body);
    }

    public function testASuperadminSeesTheMenuItem(): void
    {
        $body = (string) $this->actingAs($this->admin)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringContainsString('href="' . url_to('mail-outbox') . '"', $body);
    }

    public function testAnEmptyOutboxStillRenders(): void
    {
        $this->open()->assertOK();
        $this->assertSame([], $this->listed());
    }

    /**
     * Ids of the listed rows, in order.
     *
     * @param array<string, string> $query
     *
     * @return list<int>
     */
    private function listed(array $query = []): array
    {
        $ids = [];

        foreach ($this->xpath($this->open($query))->query('//*[@data-row]') as $row) {
            /** @var DOMElement $row */
            $ids[] = (int) $row->getAttribute('data-row');
        }

        return $ids;
    }

    /**
     * @param array<string, string> $query
     */
    private function open(array $query = []): TestResponse
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-outbox') . ($query === [] ? '' : '?' . http_build_query($query)));

        $response->assertOK();

        return $response;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function log(array $row): int
    {
        $row += [
            'mailer'     => 'smtp',
            'transport'  => 'smtp',
            'subject'    => 'Hello',
            'status'     => 'sent',
            'to'         => ['to@example.com'],
            'created_at' => '2026-10-01 12:00:00',
        ];

        foreach (['to', 'cc', 'bcc', 'attachments'] as $list) {
            $row[$list] = json_encode($row[$list] ?? [], JSON_THROW_ON_ERROR);
        }

        db_connect()->table('email_log')->insert($row);

        return db_connect()->insertID();
    }

    private function cell(TestResponse $response, int $id, string $cell): string
    {
        return trim($this->xpath($response)->query('//*[@data-row="' . $id . '"]//*[@data-' . $cell . ']')->item(0)->textContent);
    }

    private function link(TestResponse $response, int $id): ?string
    {
        $link = $this->xpath($response)->query('//*[@data-row="' . $id . '"]//a[@data-preview-link]')->item(0);

        return $link instanceof DOMElement ? $link->getAttribute('href') : null;
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $response->response()->getBody());
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
