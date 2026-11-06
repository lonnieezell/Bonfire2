<?php

namespace Tests\Resources;

use Bonfire\Resources\Resource;
use Bonfire\Resources\ResourceException;
use Bonfire\Resources\ResourceRegistry;
use Bonfire\Resources\ResourceTab;
use Bonfire\Users\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Database\Query;
use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\Entities\Product;
use Tests\Support\Models\CategoryModel;
use Tests\Support\Models\ProductArrayModel;
use Tests\Support\Models\ProductModel;
use Tests\Support\Modules\Categories\CategoryResource;
use Tests\Support\Modules\Products\ProductResource;
use Tests\Support\Modules\Products\ProductRowResource;
use Tests\Support\Modules\Products\StrayResource;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * Creating and editing records through a resource: the Products resource.
 *
 * @internal
 */
final class ResourceFormTest extends TestCase
{
    use SubmitsForms;

    private const SERVICES = ['auth', 'bonfire', 'menus', 'widgets', 'resources', 'resourceTabs'];

    protected $refresh = true;
    private User $admin;
    private int $categoryId;

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

        $this->adminGroupHas(['admin.access', 'products.list', 'products.create', 'products.edit']);

        $this->admin = $this->createUser();
        $this->admin->addGroup('admin');

        $this->categoryId = (int) model(CategoryModel::class)->insert(['name' => 'Gadgets']);

        ProductResource::$saved    = [];
        ProductRowResource::$saved = [];
    }

    protected function tearDown(): void
    {
        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }

        Factories::reset('config');

        parent::tearDown();
    }

    // Create and edit

    public function testAnAdminCreatesAProductAndEveryFieldTypeRoundTrips(): void
    {
        $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product()])->assertRedirect();

        $product = $this->onlyProduct();

        $this->assertSame('Blue Widget', $product->name);
        $this->assertSame('BW-001', $product->sku);
        $this->assertSame("Line one\nline two", $product->summary);
        $this->assertSame("# Heading\n\nSome *markdown*", $product->description);
        $this->assertSame('19.99', (string) $product->price);
        $this->assertSame(5, (int) $product->stock);
        $this->assertSame($this->categoryId, (int) $product->category_id);
        $this->assertSame('active', $product->status);
        $this->assertTrue($product->featured);
        $this->assertSame('2026-01-15', $product->released_on);
        $this->assertSame('2026-02-01 09:30:00', $product->available_at);

        // The edit form shows what was saved: posting it as it is changes nothing.
        $edit   = (string) $this->actingAs($this->admin)->get("admin/products/{$product->id}")->response()->getBody();
        $fields = $this->formFields($edit, url_to('products-update', $product->id));

        $this->assertSame('Blue Widget', $fields['fields']['name']);
        $this->assertSame('19.99', $fields['fields']['price']);
        $this->assertSame((string) $this->categoryId, $fields['fields']['category_id']);
        $this->assertSame('active', $fields['fields']['status']);

        // A browser drops the line break right after <textarea>, which DOMDocument keeps.
        foreach (['summary', 'description'] as $handle) {
            $fields['fields'][$handle] = preg_replace('/^\n/', '', $fields['fields'][$handle]);
        }

        $this->actingAs($this->admin)->post("admin/products/{$product->id}", $fields)->assertRedirect();

        $this->assertSame($product->toArray(), $this->onlyProduct()->toArray());
    }

    public function testAnAdminEditsAProduct(): void
    {
        $id = $this->createProduct();

        $this->actingAs($this->admin)->post("admin/products/{$id}", ['fields' => ['stock' => '8', 'featured' => '1'] + $this->product(['name' => 'Red Widget', 'status' => 'active'])])
            ->assertRedirectTo(url_to('products-edit', $id));

        $product = $this->onlyProduct();

        $this->assertSame('Red Widget', $product->name);
        $this->assertSame(8, (int) $product->stock);
    }

    public function testTheFormHasTheSectionsAndTheSaveBar(): void
    {
        $body = (string) $this->actingAs($this->admin)->get('admin/products/new')->response()->getBody();

        $this->assertSame(3, substr_count($body, '<fieldset class="mb-4" data-section'));
        $this->assertStringContainsString('data-columns="2"', $body);
        $this->assertStringContainsString('data-columns="3"', $body);
        $this->assertStringContainsString('bfSaveBar', $body);

        foreach (['name', 'sku', 'summary', 'description', 'price', 'stock', 'category_id', 'status', 'featured', 'released_on', 'available_at'] as $handle) {
            $this->assertStringContainsString('data-field="' . $handle . '"', $body);
        }
    }

    public function testAModelThatReturnsArraysSavesThroughAnArray(): void
    {
        $this->actingAs($this->admin)->post('admin/product-rows/new', ['fields' => ['name' => 'Rowed', 'status' => 'active']])->assertRedirect();

        $row = model(ProductArrayModel::class)->first();

        $this->assertSame('Rowed', $row['name']);
        $this->assertCount(1, ProductRowResource::$saved);
        $this->assertSame('Rowed', ProductRowResource::$saved[0]['name']);

        $this->actingAs($this->admin)->post("admin/product-rows/{$row['id']}", ['fields' => ['name' => 'Renamed', 'status' => 'draft']])->assertRedirect();

        $this->assertSame('Renamed', model(ProductArrayModel::class)->find($row['id'])['name']);
        $this->assertSame('draft', model(ProductArrayModel::class)->find($row['id'])['status']);
    }

    public function testSavingWithNothingChangedIsNotAnError(): void
    {
        $id = $this->createProduct();

        $this->actingAs($this->admin)->post("admin/products/{$id}", ['fields' => $this->product(['name' => 'Existing', 'sku' => 'EX-1', 'status' => 'draft', 'featured' => '0', 'price' => '', 'stock' => '', 'category_id' => '', 'summary' => '', 'description' => '', 'released_on' => '', 'available_at' => ''])])
            ->assertRedirect();

        $this->assertSame('Existing', $this->onlyProduct()->name);
    }

    public function testAMissingRecordIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get('admin/products/999');
    }

    // Validation

    public function testAFieldRuleErrorAndAModelRuleErrorShowTogetherUnderTheirInputs(): void
    {
        // The SKU is required by the field, and a name of two letters is too short for the model.
        $response = $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['name' => 'ab', 'sku' => ''])]);
        $body     = (string) $response->response()->getBody();

        $this->assertStringContainsString('data-error="sku"', $body);
        $this->assertStringContainsString('data-error="name"', $body);
        $this->assertSame(0, model(ProductModel::class)->countAllResults());

        // What was posted comes back.
        $this->assertStringContainsString('value="ab"', $body);
    }

    public function testAModelRuleErrorAloneIsShownToo(): void
    {
        $body = (string) $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['name' => 'ab'])])->response()->getBody();

        $this->assertStringContainsString('data-error="name"', $body);
        $this->assertStringNotContainsString('data-error="sku"', $body);
        $this->assertSame(0, model(ProductModel::class)->countAllResults());
    }

    public function testAProductKeepsItsOwnSkuButCannotTakeAnothers(): void
    {
        $first  = $this->createProduct(['name' => 'First', 'sku' => 'AAA']);
        $second = $this->createProduct(['name' => 'Second', 'sku' => 'BBB']);

        $this->actingAs($this->admin)->post("admin/products/{$second}", ['fields' => $this->product(['name' => 'Second renamed', 'sku' => 'BBB'])])->assertRedirect();
        $this->assertSame('Second renamed', model(ProductModel::class)->find($second)->name);

        $body = (string) $this->actingAs($this->admin)->post("admin/products/{$second}", ['fields' => $this->product(['name' => 'Second again', 'sku' => 'AAA'])])->response()->getBody();

        $this->assertStringContainsString('data-error="sku"', $body);
        $this->assertSame('BBB', model(ProductModel::class)->find($second)->sku);
        $this->assertSame('AAA', model(ProductModel::class)->find($first)->sku);

        // A new product can't take a SKU that exists either.
        $created = (string) $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['sku' => 'AAA'])])->response()->getBody();

        $this->assertStringContainsString('data-error="sku"', $created);
        $this->assertSame(2, model(ProductModel::class)->countAllResults());
    }

    public function testFieldTypesRefuseWhatTheyDoNotAccept(): void
    {
        $body = (string) $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['price' => '1.234', 'stock' => '-1', 'status' => 'bogus', 'category_id' => '999'])])->response()->getBody();

        foreach (['price', 'stock', 'status', 'category_id'] as $handle) {
            $this->assertStringContainsString('data-error="' . $handle . '"', $body);
        }
    }

    // $allowedFields

    public function testAFieldMissingFromAllowedFieldsThrowsAResourceExceptionNamingItOutsideProduction(): void
    {
        try {
            (new ResourceRegistry(production: false))->register(StrayResource::class);
            $this->fail('A field the model would drop should be refused.');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('`notes`', $e->getMessage());
            $this->assertStringNotContainsString('`name`', $e->getMessage());
        }
    }

    public function testInProductionAFieldMissingFromAllowedFieldsIsLoggedAndTheResourceStillRegisters(): void
    {
        // The sidebar the resource adds its item to exists once the admin has booted.
        $this->visit('admin');

        $registry = new ResourceRegistry(production: true);

        $registry->register(StrayResource::class);

        $this->assertInstanceOf(StrayResource::class, $registry->get('strays'));
        $this->assertLogContains('error', '`notes`');
    }

    public function testAResourceWithOnlyAListNeedsNoAllowedFields(): void
    {
        $this->visit('admin');

        $registry = new ResourceRegistry(production: false);

        $registry->register(CategoryResource::class);

        $this->assertInstanceOf(Resource::class, $registry->get('categories'));
    }

    // Hooks

    public function testBeforeSaveCanAddAndDropValues(): void
    {
        // Added: the slug, which is no field. Dropped: `featured`, unless the product is active.
        $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['name' => 'Green Gadget', 'status' => 'draft', 'featured' => '1'])])->assertRedirect();

        $product = $this->onlyProduct();

        $this->assertSame('green-gadget', $product->slug);
        $this->assertFalse($product->featured);

        $this->actingAs($this->admin)->post("admin/products/{$product->id}", ['fields' => $this->product(['name' => 'Green Gadget', 'status' => 'active', 'featured' => '1'])])->assertRedirect();

        $this->assertTrue($this->onlyProduct()->featured);

        // The dropped value leaves the stored one as it was.
        $this->actingAs($this->admin)->post("admin/products/{$product->id}", ['fields' => $this->product(['name' => 'Green Gadget', 'status' => 'draft', 'featured' => '0'])])->assertRedirect();

        $this->assertTrue($this->onlyProduct()->featured);
    }

    public function testAfterSaveReceivesTheSavedEntity(): void
    {
        $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['name' => 'Saved One'])])->assertRedirect();

        $this->assertCount(1, ProductResource::$saved);

        $saved = ProductResource::$saved[0];

        $this->assertInstanceOf(Product::class, $saved);
        $this->assertSame('Saved One', $saved->name);
        $this->assertSame($this->onlyProduct()->id, $saved->id);

        $this->actingAs($this->admin)->post("admin/products/{$saved->id}", ['fields' => $this->product(['name' => 'Saved Two'])])->assertRedirect();

        $this->assertCount(2, ProductResource::$saved);
        $this->assertSame('Saved Two', ProductResource::$saved[1]->name);
    }

    public function testAfterSaveDoesNotRunWhenTheSaveIsRefused(): void
    {
        $this->actingAs($this->admin)->post('admin/products/new', ['fields' => $this->product(['name' => 'ab'])]);

        $this->assertSame([], ProductResource::$saved);
    }

    // Permissions

    public function testCreateAndEditNeedTheirPermissions(): void
    {
        $id = $this->createProduct();

        $this->adminGroupHas(['admin.access', 'products.list']);

        foreach (['admin/products/new', "admin/products/{$id}"] as $url) {
            $this->assertRefused($this->actingAs($this->admin)->get($url));
        }

        foreach (['admin/products/new', "admin/products/{$id}"] as $url) {
            $this->assertRefused($this->actingAs($this->admin)->post($url, ['fields' => $this->product(['name' => 'Sneaky'])]));
        }

        $this->assertSame(1, model(ProductModel::class)->countAllResults());
        $this->assertSame('Existing', model(ProductModel::class)->find($id)->name);
    }

    public function testTheNewButtonAndTheEditLinksNeedTheirPermissions(): void
    {
        $id = $this->createProduct();

        $with = $this->visit('admin/products');

        $this->assertStringContainsString('data-new-record', $with);
        $this->assertMatchesRegularExpression('#<td data-cell="name"><a href="[^"]*/admin/products/' . $id . '">Existing</a></td>#', $with);

        $this->adminGroupHas(['admin.access', 'products.list']);

        $without = $this->visit('admin/products');

        $this->assertStringNotContainsString('data-new-record', $without);
        $this->assertMatchesRegularExpression('#<td data-cell="name">Existing</td>#', $without);
    }

    public function testCanRefusesASingleRecordsEditPageAndSave(): void
    {
        $archived = $this->createProduct(['name' => 'Archived', 'sku' => 'ARC', 'status' => 'archived']);
        $open     = $this->createProduct(['name' => 'Open', 'sku' => 'OPN']);

        $this->assertRefused($this->actingAs($this->admin)->get("admin/products/{$archived}"));
        $this->assertRefused($this->actingAs($this->admin)->post("admin/products/{$archived}", ['fields' => $this->product(['name' => 'Changed', 'sku' => 'ARC'])]));

        $this->assertSame('Archived', model(ProductModel::class)->find($archived)->name);

        // Another record is still editable, and the list only links it.
        $this->actingAs($this->admin)->get("admin/products/{$open}")->assertOK();

        $list = $this->visit('admin/products');

        $this->assertStringNotContainsString("admin/products/{$archived}\"", $list);
        $this->assertStringContainsString("admin/products/{$open}\"", $list);
    }

    // The list

    public function testTheListShowsTheRelationAndTheComputedColumn(): void
    {
        $this->createProduct(['name' => 'Priced', 'sku' => 'PR', 'price' => '2.50', 'stock' => 4, 'category_id' => $this->categoryId]);
        $this->createProduct(['name' => 'Loose', 'sku' => 'LO']);

        $body = $this->visit('admin/products');

        $this->assertSame(['Gadgets', ''], $this->cells($body, 'category.name'));
        $this->assertSame(['10.00', ''], $this->cells($body, 'stock_value'));
    }

    public function testTheListDoesNotQueryOnceForEachRow(): void
    {
        $this->createProduct(['name' => 'One', 'sku' => 'ONE', 'category_id' => $this->categoryId]);
        $this->createProduct(['name' => 'Two', 'sku' => 'TWO', 'category_id' => $this->categoryId]);

        // The first request loads settings and the like.
        $this->visit('admin/products');

        $few = $this->queriesFor('admin/products');

        foreach (['Three', 'Four', 'Five', 'Six'] as $name) {
            $this->createProduct(['name' => $name, 'sku' => strtoupper($name), 'category_id' => $this->categoryId]);
        }

        $many = $this->queriesFor('admin/products');

        // Housekeeping queries of the session and the like come and go, so count the ones about the records.
        $about = static fn (array $queries): array => array_values(array_filter($queries, static fn (string $sql): bool => str_contains($sql, 'db_products') || str_contains($sql, 'db_categories')));

        // The page count and the page of rows, with the category joined in.
        $this->assertCount(2, $about($few), implode("\n", $few));
        $this->assertCount(2, $about($many), implode("\n", $many));
        $this->assertCount(6, $this->cells($this->visit('admin/products'), 'category.name'));
    }

    public function testAColumnThatIsNotATableColumnCannotBeSorted(): void
    {
        $this->createProduct();

        $body = $this->visit('admin/products');

        $this->assertDoesNotMatchRegularExpression('#<th data-column="category.name"[^>]*>\s*<a#', $body);
        $this->assertMatchesRegularExpression('#<th data-column="name"[^>]*>\s*<a#', $body);
    }

    // Views and tabs

    public function testAFormPhpBesideTheResourceReplacesThePackageForm(): void
    {
        $plain = $this->visit('admin/products/new');

        $this->assertStringContainsString('data-resource-form="products"', $plain);
        $this->assertStringNotContainsString('data-custom-form', $plain);

        $custom = $this->visit('admin/catalog/new');

        $this->assertStringContainsString('data-custom-form="catalog"', $custom);
        $this->assertStringNotContainsString('data-resource-form', $custom);
        $this->assertStringContainsString('data-field="name"', $custom);

        // The replacement posts the same way.
        $this->actingAs($this->admin)->post('admin/catalog/new', ['fields' => $this->product()])->assertRedirectTo(url_to('catalog-edit', $this->onlyProduct()->id));
    }

    public function testTheEditPageShowsTheResourceTabs(): void
    {
        service('resourceTabs')->addTabFor('products', new ResourceTab(['title' => 'Reviews', 'url' => 'products/(id)/reviews']));

        $id = $this->createProduct();

        $this->assertMatchesRegularExpression('#href="[^"]*/admin/products/' . $id . '/reviews"#', $this->visit("admin/products/{$id}"));
        $this->assertStringNotContainsString('/reviews"', $this->visit('admin/products/new'));
    }

    /**
     * The form's values for a product, to override one at a time. `fields` is what the browser posts.
     *
     * @param array<string, string> $with
     *
     * @return array<string, string>
     */
    private function product(array $with = []): array
    {
        return $with + [
            'name'         => 'Blue Widget',
            'sku'          => 'BW-001',
            'summary'      => "Line one\nline two",
            'description'  => "# Heading\n\nSome *markdown*",
            'price'        => '19.99',
            'stock'        => '5',
            'category_id'  => (string) $this->categoryId,
            'status'       => 'active',
            'featured'     => '1',
            'released_on'  => '2026-01-15',
            'available_at' => '2026-02-01T09:30',
        ];
    }

    /**
     * @param array<string, mixed> $with
     */
    private function createProduct(array $with = []): int
    {
        return (int) model(ProductModel::class)->insert($with + ['name' => 'Existing', 'sku' => 'EX-1', 'status' => 'draft']);
    }

    private function onlyProduct(): Product
    {
        $products = model(ProductModel::class)->findAll();

        $this->assertCount(1, $products);

        return $products[0];
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

    /**
     * The page as the admin gets it, with its links as plain text.
     */
    private function visit(string $url): string
    {
        Services::resetSingle('pager');

        return html_entity_decode((string) $this->actingAs($this->admin)->get($url)->response()->getBody());
    }

    private function assertRefused(TestResponse $response): void
    {
        $response->assertRedirect();
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
    }

    /**
     * The queries a page runs.
     *
     * @return list<string>
     */
    private function queriesFor(string $url): array
    {
        $queries  = [];
        $listener = static function (Query $query) use (&$queries): void {
            $queries[] = (string) $query;
        };

        Events::on('DBQuery', $listener);
        $this->visit($url);
        Events::removeListener('DBQuery', $listener);

        return $queries;
    }

    /**
     * What a column of the list shows, row by row.
     *
     * @return list<string>
     */
    private function cells(string $body, string $column): array
    {
        preg_match_all('#<td data-cell="' . preg_quote($column, '#') . '">(.*?)</td>#', $body, $matches);

        return $matches[1];
    }
}
