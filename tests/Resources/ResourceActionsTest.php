<?php

namespace Tests\Resources;

use Bonfire\Resources\Actions\DeleteRecords;
use Bonfire\Users\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\Actions\TouchProducts;
use Tests\Support\Models\ProductModel;
use Tests\Support\Modules\Products\FeatureProducts;
use Tests\Support\TestCase;

/**
 * A resource's actions as row and bulk actions of its list: the Products resource.
 *
 * @internal
 */
final class ResourceActionsTest extends TestCase
{
    private const SERVICES = ['auth', 'bonfire', 'menus', 'widgets', 'resources', 'recycler', 'actions'];

    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withTestResources();
        setting('Site.siteOnline', true);
        setting('Site.perPage', 10);

        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }

        // url_to() before a request finds no routes if an earlier test left them unloaded.
        service('routes')->loadRoutes();

        $this->adminGroupHas(['admin.access', 'products.list', 'products.delete', 'products.feature', 'recycler.view']);

        $this->admin = $this->createUser();
        $this->admin->addGroup('admin');
    }

    protected function tearDown(): void
    {
        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }

        Factories::reset('config');

        parent::tearDown();
    }

    // What the list shows

    public function testTheListShowsTheRowActionsOfEveryProduct(): void
    {
        $first  = $this->createProduct('Anvil');
        $second = $this->createProduct('Bellows');

        $body = $this->visit('admin/products');

        $this->assertSame([$first, $second], $this->rowActionIds($body, 'delete-records'));
        $this->assertSame([$first, $second], $this->rowActionIds($body, 'feature-products'));
    }

    public function testCheckingRowsOffersTheBulkActions(): void
    {
        $id = $this->createProduct('Anvil');

        $body = $this->visit('admin/products');

        $this->assertSame([(string) $id], $this->checkboxes($body));
        $this->assertStringContainsString('class="form-check select-all"', $body);
        $this->assertSame(['delete-records', 'feature-products'], $this->bulkActions($body));
        // The selection bar posts the checked rows with the form's CSRF field.
        $this->assertStringContainsString('name="' . csrf_token() . '"', $body);
    }

    public function testTheDeleteActionAsksBeforeDeleting(): void
    {
        $this->createProduct('Anvil');

        $body = $this->visit('admin/products');

        $this->assertMatchesRegularExpression('#formaction="[^"]*/admin/actions/products/delete-records"[^>]*data-confirm=#', $body);
    }

    public function testAnHtmxRefreshOfTheTableKeepsTheActions(): void
    {
        $id = $this->createProduct('Anvil');

        $body = html_entity_decode((string) $this->withHeaders(['HX-Request' => 'true'])->actingAs($this->admin)->get('admin/products')->response()->getBody());

        $this->assertStringNotContainsString('<html', $body);
        $this->assertSame([(string) $id], $this->checkboxes($body));
        $this->assertSame([$id], $this->rowActionIds($body, 'delete-records'));
    }

    public function testAResourceWithoutActionsHasNoCheckboxesOrActionsColumn(): void
    {
        $this->adminGroupHas(['admin.access', 'categories.list']);
        model('Tests\Support\Models\CategoryModel')->insert(['name' => 'Gadgets']);

        $body = $this->visit('admin/categories');

        $this->assertSame([], $this->checkboxes($body));
        $this->assertStringNotContainsString('select-all', $body);
        $this->assertStringNotContainsString('bf-row-menu', $body);
    }

    // Permissions

    public function testAnActionTheUserLacksThePermissionForDoesNotShow(): void
    {
        $this->adminGroupHas(['admin.access', 'products.list', 'products.delete']);
        $id = $this->createProduct('Anvil');

        $body = $this->visit('admin/products');

        $this->assertSame([$id], $this->rowActionIds($body, 'delete-records'));
        $this->assertSame([], $this->rowActionIds($body, 'feature-products'));
        $this->assertSame(['delete-records'], $this->bulkActions($body));
    }

    public function testWithoutAnyActionPermissionTheListHasNoCheckboxes(): void
    {
        $this->adminGroupHas(['admin.access', 'products.list']);
        $this->createProduct('Anvil');

        $body = $this->visit('admin/products');

        $this->assertSame([], $this->checkboxes($body));
        $this->assertStringNotContainsString('bf-row-menu', $body);
    }

    public function testTheEndpointRefusesAnActionTheUserLacksThePermissionFor(): void
    {
        $this->adminGroupHas(['admin.access', 'products.list']);
        $id = $this->createProduct('Anvil');

        foreach (['delete-records', 'feature-products'] as $action) {
            $response = $this->runAction($action, [$id]);

            $response->assertRedirect();
            $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        }

        $this->assertNotNull(model(ProductModel::class)->find($id));
        $this->assertSame(0, (int) model(ProductModel::class)->find($id)->featured);
    }

    // Deleting

    public function testDeletingAProductAsksForConfirmationFirst(): void
    {
        $id = $this->createProduct('Anvil');

        $page = $this->runAction('delete-records', [$id]);

        $page->assertOK();
        $this->assertStringContainsString('<input type="hidden" name="confirmed" value="1">', $page->getBody());
        $this->assertNotNull(model(ProductModel::class)->find($id));
    }

    public function testConfirmingARowDeleteSoftDeletesTheProductAndTheRecyclerRestoresIt(): void
    {
        $id    = $this->createProduct('Anvil');
        $other = $this->createProduct('Bellows');

        $response = $this->runAction('delete-records', [$id], confirmed: true);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertNull(model(ProductModel::class)->find($id));
        $this->assertNotNull(model(ProductModel::class)->withDeleted()->find($id)->deleted_at);
        $this->assertNotNull(model(ProductModel::class)->find($other));
        $this->assertSame([$other], $this->rowActionIds($this->visit('admin/products'), 'delete-records'));

        // It is in the Recycler, and restoring it puts it back in the list.
        $this->actingAs($this->admin)
            ->post("admin/tools/recycler/restore/products/{$id}", [csrf_token() => csrf_hash()])
            ->assertSessionHas('message');

        $this->assertNull(model(ProductModel::class)->withDeleted()->find($id)->deleted_at);
        $this->assertSame([$id, $other], $this->rowActionIds($this->visit('admin/products'), 'delete-records'));
    }

    public function testConfirmingABulkDeleteSoftDeletesEveryCheckedProduct(): void
    {
        $first  = $this->createProduct('Anvil');
        $second = $this->createProduct('Bellows');
        $kept   = $this->createProduct('Crucible');

        $this->runAction('delete-records', [$first, $second], confirmed: true)->assertSessionHas('message');

        $this->assertSame([$kept], array_map(static fn ($product) => $product->id, model(ProductModel::class)->findAll()));
        $this->assertCount(2, model(ProductModel::class)->onlyDeleted()->findAll());
    }

    public function testADeleteTheResourceRefusesIsSkippedAndItsRowDoesNotOfferIt(): void
    {
        $archived = $this->createProduct('Anvil', ['status' => 'archived']);
        $active   = $this->createProduct('Bellows');

        $this->assertSame([$active], $this->rowActionIds($this->visit('admin/products'), 'delete-records'));

        $this->runAction('delete-records', [$archived, $active], confirmed: true)->assertSessionHas('message');

        $this->assertNotNull(model(ProductModel::class)->find($archived));
        $this->assertNull(model(ProductModel::class)->find($active));

        $this->runAction('delete-records', [$archived], confirmed: true)->assertSessionHas('error', lang('Actions.noneAllowed'));
        $this->assertNotNull(model(ProductModel::class)->find($archived));
    }

    // Custom and added actions

    public function testACustomActionRunsOnTheSelectedProducts(): void
    {
        $first  = $this->createProduct('Anvil');
        $second = $this->createProduct('Bellows');
        $third  = $this->createProduct('Crucible');

        $response = $this->runAction('feature-products', [$first, $third]);

        $response->assertSessionHas('message', '2 products featured.');

        $featured = static fn (int $id): int => (int) model(ProductModel::class)->find($id)->featured;

        $this->assertSame([1, 0, 1], [$featured($first), $featured($second), $featured($third)]);
    }

    public function testACustomActionSkipsTheProductsItsAllowsRejects(): void
    {
        $archived = $this->createProduct('Anvil', ['status' => 'archived']);
        $active   = $this->createProduct('Bellows');

        $this->runAction('feature-products', [$archived, $active])->assertSessionHas('message', '1 products featured. Skipped 1.');

        $this->assertSame(0, (int) model(ProductModel::class)->find($archived)->featured);
        $this->assertSame(1, (int) model(ProductModel::class)->find($active)->featured);
        // And the archived product's row doesn't offer it.
        $this->assertSame([$active], $this->rowActionIds($this->visit('admin/products'), 'feature-products'));
    }

    public function testAnotherModuleAddsAnActionToTheListBySlug(): void
    {
        $touch = new TouchProducts();
        service('actions')->for('products')->add($touch);
        $id = $this->createProduct('Anvil');

        $body = $this->visit('admin/products');

        $this->assertSame([$id], $this->rowActionIds($body, 'touch-products'));
        $this->assertContains('touch-products', $this->bulkActions($body));

        $this->runAction('touch-products', [$id])->assertSessionHas('message', '1 products touched.');
        $this->assertSame([$id], $touch->touched);
    }

    public function testRegisteringAResourcePutsItsActionsOnTheListNamedBySlug(): void
    {
        // The admin registers the resources when it boots for a request.
        $this->visit('admin/products');

        $delete = service('actions')->find('products', 'delete-records');

        $this->assertInstanceOf(DeleteRecords::class, $delete);
        $this->assertInstanceOf(FeatureProducts::class, service('actions')->find('products', 'feature-products'));
        $this->assertSame('products.delete', $delete->permission);
        $this->assertCount(1, service('actions')->for('products')->source()->find([$this->createProduct('Anvil')]));
    }

    /**
     * @param array<string, mixed> $with
     */
    private function createProduct(string $name, array $with = []): int
    {
        return (int) model(ProductModel::class)->insert($with + ['name' => $name, 'sku' => strtoupper(substr($name, 0, 3)) . '-1', 'status' => 'draft']);
    }

    /**
     * Gives the admin group these permissions and no others.
     *
     * @param list<string> $permissions
     */
    private function adminGroupHas(array $permissions): void
    {
        $config                  = config('AuthGroups');
        $config->matrix['admin'] = $permissions;
        Factories::injectMock('config', 'AuthGroups', $config);
    }

    private function visit(string $url): string
    {
        Services::resetSingle('pager');

        return html_entity_decode((string) $this->actingAs($this->admin)->get($url)->response()->getBody());
    }

    /**
     * Posts the way a row's menu or the selection bar does.
     *
     * @param list<int> $ids
     */
    private function runAction(string $action, array $ids, bool $confirmed = false): TestResponse
    {
        $data = [csrf_token() => csrf_hash(), 'selects' => $ids];

        if ($confirmed) {
            $data['confirmed'] = '1';
        }

        return $this->actingAs($this->admin)->post("admin/actions/products/{$action}", $data);
    }

    /**
     * The IDs whose row menu has the action.
     *
     * @return list<int>
     */
    private function rowActionIds(string $body, string $action): array
    {
        preg_match_all('#form="bf-action-form" formaction="[^"]*/admin/actions/products/' . preg_quote($action, '#') . '"\s+name="selects\[\]" value="(\d+)"#', $body, $matches);

        return array_map(intval(...), $matches[1]);
    }

    /**
     * The values of the row checkboxes.
     *
     * @return list<string>
     */
    private function checkboxes(string $body): array
    {
        preg_match_all('#<input type="checkbox" name="selects\[\]" value="([^"]*)"#', $body, $matches);

        return $matches[1];
    }

    /**
     * The actions the selection bar offers.
     *
     * @return list<string>
     */
    private function bulkActions(string $body): array
    {
        preg_match_all('#formaction="[^"]*/admin/actions/products/([a-z-]+)" data-confirm-bulk="1"#', $body, $matches);

        // The bar is there twice: once for browsers without JavaScript.
        return array_values(array_unique($matches[1]));
    }
}
