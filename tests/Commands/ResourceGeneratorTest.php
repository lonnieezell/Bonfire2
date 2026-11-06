<?php

namespace Tests\Commands;

use Bonfire\Commands\Generators\ResourceGenerator;
use Bonfire\Fields\Field;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\StreamFilterTrait;
use Config\Services;
use ParseError;
use Tests\Support\Models\ProductModel;
use Tests\Support\Models\TypeSampleModel;
use Tests\Support\TestCase;

/**
 * bf:resource, run against the table the Products resource tests use.
 *
 * @internal
 */
final class ResourceGeneratorTest extends TestCase
{
    use StreamFilterTrait;

    private const SERVICES = ['auth', 'bonfire', 'menus', 'widgets', 'resources', 'resourceTabs'];

    /**
     * Where the command puts modules; the tests map it to a temp folder.
     */
    private const MODULES = APP_NAMESPACE . '\Modules';

    protected $refresh = true;

    /**
     * One folder for every test: PHP can't load a generated class a second time from another path.
     */
    private static string $folder;

    private string $root;

    /**
     * @var list<mixed>
     */
    private array $registrars;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$folder = sys_get_temp_dir() . '/bonfire-' . bin2hex(random_bytes(8)) . '/';
    }

    public static function tearDownAfterClass(): void
    {
        rmdir(self::$folder);

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        helper(['filesystem', 'inflector']);

        $this->root       = self::$folder;
        $this->registrars = BaseConfig::$registrars;

        mkdir($this->root . 'Modules', 0777, true);
        service('autoloader')->addNamespace(self::MODULES, $this->root . 'Modules');

        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }
    }

    protected function tearDown(): void
    {
        BaseConfig::$registrars = $this->registrars;

        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }

        Factories::reset('config');

        // Bonfire registers each module it discovers as a namespace of its own
        foreach (['Items', 'Gadgets', 'Samples'] as $module) {
            service('autoloader')->removeNamespace(self::MODULES . '\\' . $module);
        }
        db_connect()->query('DROP TABLE IF EXISTS ' . db_connect()->prefixTable('type_samples'));
        service('autoloader')->removeNamespace(self::MODULES);
        delete_files($this->root, true, true);

        parent::tearDown();
    }

    // The generated resource

    public function testWritesTheResourceAndTheModuleFiles(): void
    {
        $this->assertSame(0, $this->generate());

        foreach (['ItemResource.php', 'Module.php', 'Config/Routes.php', 'Config/Registrar.php'] as $file) {
            $this->assertFileExists($this->root . 'Modules/Items/' . $file);
        }

        foreach (get_filenames($this->root, true, false, false) as $file) {
            try {
                token_get_all(file_get_contents($file), TOKEN_PARSE);
            } catch (ParseError $e) {
                $this->fail($file . ': ' . $e->getMessage());
            }
        }
    }

    public function testTakesTheModelFromTheEqualsSignForm(): void
    {
        $this->assertSame(0, $this->generate(['model=' . ProductModel::class => null], model: null));
        $this->assertFileExists($this->root . 'Modules/Items/ItemResource.php');
    }

    public function testColumnTypesBecomeFieldTypes(): void
    {
        $this->createSampleTable();
        $this->generate(name: 'Sample', model: TypeSampleModel::class);

        $this->assertSame([
            'title'     => 'text',
            'note'      => 'text',
            'body'      => 'textarea',
            'qty'       => 'integer',
            'price'     => 'decimal',
            'active'    => 'toggle',
            'level'     => 'integer',
            'starts_on' => 'date',
            'starts_at' => 'datetime',
            'seen_at'   => 'datetime',
            'parent_id' => 'integer',
        ], array_map(static fn ($field): string => $field->type, $this->generatedFields('Sample')));
    }

    public function testAColumnEndingInIdIsACommentedBelongsToStub(): void
    {
        $this->createSampleTable();
        $this->generate(name: 'Sample', model: TypeSampleModel::class);

        $source = file_get_contents($this->root . 'Modules/Samples/SampleResource.php');

        $this->assertStringContainsString("// BelongsTo::make('owner_id')->label('Owner')->model(OwnerModel::class)->title('name'),", (string) $source);
        $this->assertStringNotContainsString("\n            BelongsTo::make", (string) $source);
        $this->assertArrayNotHasKey('owner_id', $this->generatedFields('Sample'));
    }

    public function testARequiredColumnEndingInIdStillGetsAnInput(): void
    {
        $this->createSampleTable();
        $this->generate(name: 'Sample', model: TypeSampleModel::class);

        $fields = $this->generatedFields('Sample');

        $this->assertSame('integer', $fields['parent_id']->type);
        $this->assertSame('required', $fields['parent_id']->validate);
        $this->assertStringContainsString("// BelongsTo::make('parent_id')", (string) file_get_contents($this->root . 'Modules/Samples/SampleResource.php'));
    }

    public function testTheFieldsCarryTheRulesTheColumnsNeed(): void
    {
        $this->createSampleTable();
        $this->generate(name: 'Sample', model: TypeSampleModel::class);

        $rules = array_map(static fn ($field): array|string => $field->validate, $this->generatedFields('Sample'));

        // NOT NULL with no default is required, a varchar has its length, and a default makes a column optional.
        $this->assertSame('required|max_length[120]', $rules['title']);
        $this->assertSame('max_length[50]', $rules['note']);
        $this->assertSame('', $rules['qty']);
        $this->assertSame('', $rules['active']);
    }

    public function testADecimalKeepsItsPlaces(): void
    {
        $this->createSampleTable();
        $this->generate(name: 'Sample', model: TypeSampleModel::class);

        $this->assertSame(3, $this->generatedFields('Sample')['price']->options['places']);
    }

    public function testTheGeneratedResourceListsCreatesAndEditsWithNoHandEdits(): void
    {
        $this->generate();
        $this->bootGeneratedModule();

        $admin = $this->createUser();
        $admin->addGroup('admin');

        $this->actingAs($admin)->get(ADMIN_AREA . '/items')->assertOK();
        $this->actingAs($admin)->get(ADMIN_AREA . '/items/new')->assertOK();

        $this->actingAs($admin)
            ->post(ADMIN_AREA . '/items/new', ['fields' => [
                'name'         => 'Blue Widget',
                'sku'          => 'BW-001',
                'summary'      => 'Short',
                'description'  => 'Long',
                'price'        => '19.99',
                'stock'        => '5',
                'status'       => 'active',
                'featured'     => '1',
                'released_on'  => '2026-01-15',
                'available_at' => '2026-02-01T09:30',
            ]])
            ->assertRedirect();

        $product = model(ProductModel::class)->first();
        $this->assertSame('Blue Widget', $product->name);
        $this->assertSame('19.99', (string) $product->price);
        $this->assertTrue($product->featured);
        $this->assertSame('2026-02-01 09:30:00', $product->available_at);

        $this->actingAs($admin)->get(ADMIN_AREA . '/items/' . $product->id)->assertOK();
        $this->actingAs($admin)
            ->post(ADMIN_AREA . '/items/' . $product->id, ['fields' => [
                'name'     => 'Renamed',
                'sku'      => 'BW-001',
                'status'   => 'active',
                'featured' => '1',
            ]])
            ->assertRedirect();
        $this->assertSame('Renamed', model(ProductModel::class)->find($product->id)->name);

        $this->actingAs($admin)->get(ADMIN_AREA . '/items')->assertSee('Renamed');
    }

    public function testARequiredFieldLeftEmptyIsSentBack(): void
    {
        $this->generate();
        $this->bootGeneratedModule();

        $admin = $this->createUser();
        $admin->addGroup('admin');

        $this->actingAs($admin)
            ->post(ADMIN_AREA . '/items/new', ['fields' => ['name' => '', 'status' => 'draft']])
            ->assertOK();

        $this->assertSame(0, model(ProductModel::class)->countAllResults());
    }

    // Existing files

    public function testLeavesExistingFilesAloneAndSaysWhatToAddByHand(): void
    {
        $module = $this->root . 'Modules/Items/';
        mkdir($module . 'Config', 0777, true);

        foreach (['Module.php', 'Config/Routes.php', 'Config/Registrar.php'] as $file) {
            file_put_contents($module . $file, 'mine');
        }

        $this->assertSame(0, $this->generate());

        foreach (['Module.php', 'Config/Routes.php', 'Config/Registrar.php'] as $file) {
            $this->assertSame('mine', file_get_contents($module . $file));
        }

        $this->assertFileExists($module . 'ItemResource.php');

        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString("service('resources')->register(ItemResource::class);", $output);
        $this->assertStringContainsString('resource_routes($routes, ItemResource::class);', $output);
        $this->assertStringContainsString("'superadmin' => ['items.*'],", $output);

        foreach (['items.list', 'items.create', 'items.edit', 'items.delete'] as $permission) {
            $this->assertStringContainsString("'{$permission}' =>", $output);
        }
    }

    public function testSaysNothingToAddForFilesItWrote(): void
    {
        $this->generate();

        $output = $this->getStreamFilterBuffer();

        $this->assertStringNotContainsString('Add this to', $output);
        $this->assertStringNotContainsString('Left ', $output);
    }

    public function testLeavesAnExistingResourceAlone(): void
    {
        $module = $this->root . 'Modules/Items/';
        mkdir($module, 0777, true);
        file_put_contents($module . 'ItemResource.php', '<?php // mine');

        $this->assertSame(0, $this->generate());
        $this->assertSame('<?php // mine', file_get_contents($module . 'ItemResource.php'));
        $this->assertFileExists($module . 'Module.php');
    }

    // Permissions

    public function testTheRegistrarDeclaresThePermissions(): void
    {
        $this->generate();

        $registrar = $this->registrar('Items');
        $config    = $registrar::AuthGroups();

        $this->assertSame(['items.list', 'items.create', 'items.edit', 'items.delete'], array_keys($config['permissions']));
        $this->assertSame(['items.*'], $config['moduleGrants']['items']['superadmin']);
        $this->assertSame(['items.list', 'items.create', 'items.edit', 'items.delete'], $config['moduleGrants']['items']['admin']);
    }

    public function testThePermissionsOfAPageThatIsOffAreLeftOut(): void
    {
        // A resource that is already there has no create page.
        $module    = $this->root . 'Modules/Gadgets/';
        $namespace = self::MODULES . '\Gadgets';
        mkdir($module, 0777, true);
        file_put_contents($module . 'GadgetResource.php', <<<PHP
            <?php

            namespace {$namespace};

            use Bonfire\\Resources\\Resource;
            use Tests\\Support\\Models\\ProductModel;

            class GadgetResource extends Resource
            {
                public string \$model      = ProductModel::class;
                public string \$slug       = 'gadgets';
                public string \$permission = 'gadgets';
                public array \$pages       = ['list', 'edit'];
            }
            PHP);

        $this->assertSame(0, $this->generate(name: 'Gadget'));

        $registrar = $this->registrar('Gadgets');
        $config    = $registrar::AuthGroups();

        $this->assertSame(['gadgets.list', 'gadgets.edit', 'gadgets.delete'], array_keys($config['permissions']));
        $this->assertSame(['gadgets.list', 'gadgets.edit', 'gadgets.delete'], $config['moduleGrants']['gadgets']['admin']);
    }

    public function testThePermissionsAppearUnderSettingsGroups(): void
    {
        $this->generate();

        BaseConfig::$registrars[] = $this->registrar('Items');
        Factories::reset('config');

        $superadmin = $this->createUser();
        $superadmin->addGroup('superadmin');
        $auth                         = config('AuthGroups');
        $auth->matrix['superadmin'][] = 'groups.*';
        setting('AuthGroups.matrix', $auth->matrix);

        $page = $this->actingAs($superadmin)->get(ADMIN_AREA . '/settings/groups/admin/permissions');

        foreach (['items.list', 'items.create', 'items.edit', 'items.delete'] as $permission) {
            $page->assertSee($permission);
        }
    }

    // Refusals

    public function testRefusesAModelClassThatDoesNotExist(): void
    {
        $this->assertSame(1, $this->generate(['model' => 'Tests\Support\Models\NoSuchModel']));
        $this->assertDirectoryDoesNotExist($this->root . 'Modules/Items');
    }

    public function testRefusesAClassThatIsNoModel(): void
    {
        $this->assertSame(1, $this->generate(['model' => self::class]));
        $this->assertDirectoryDoesNotExist($this->root . 'Modules/Items');
    }

    public function testRefusesAModelWhoseTableDoesNotExist(): void
    {
        // The ArticleModel's table is only there while its generator test runs.
        $this->assertSame(1, $this->generate(['model' => 'Tests\Support\Models\ArticleModel']));
        $this->assertDirectoryDoesNotExist($this->root . 'Modules/Items');
    }

    /**
     * Runs bf:resource for ProductModel into the temp folder. It is called "Item" so that its routes,
     * permissions and slug aren't those of the Products fixtures.
     *
     * @param string|null $model the model class, or null to leave it out
     */
    private function generate(array $options = [], string $name = 'Item', ?string $model = ProductModel::class): int
    {
        $command = new ResourceGenerator(service('logger'), service('commands'));
        $params  = [$name, ...($model === null ? [] : ['model' => $model]), ...$options];

        return $command->run($params);
    }

    /**
     * The table of TypeSampleModel, with the types written the way MySQL reports them: SQLite keeps
     * what a CREATE TABLE says, where the forge would leave out lengths.
     */
    private function createSampleTable(): void
    {
        $db    = db_connect();
        $table = $db->prefixTable('type_samples');

        $db->query(<<<SQL
            CREATE TABLE {$table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(120) NOT NULL,
                note VARCHAR(50) NOT NULL DEFAULT 'x',
                body TEXT,
                qty INT,
                price DECIMAL(10,3),
                active TINYINT(1) NOT NULL DEFAULT 0,
                level TINYINT NOT NULL DEFAULT 0,
                starts_on DATE,
                starts_at DATETIME,
                seen_at TIMESTAMP,
                owner_id INT,
                parent_id INT NOT NULL
            )
            SQL);
    }

    /**
     * The fields of the generated resource, by handle.
     *
     * @return array<string, Field>
     */
    private function generatedFields(string $name = 'Item'): array
    {
        $resource = self::MODULES . '\\' . plural($name) . '\\' . $name . 'Resource';
        $fields   = [];

        foreach ((new $resource())->formFields() as $field) {
            $fields[$field->handle] = $field;
        }

        return $fields;
    }

    /**
     * @return class-string
     */
    private function registrar(string $module): string
    {
        return self::MODULES . '\\' . $module . '\Config\Registrar';
    }

    /**
     * Does what an install does once it has a module: discovers it and grants its permissions.
     */
    private function bootGeneratedModule(): void
    {
        config('Bonfire')->appModules = [self::MODULES => $this->root . 'Modules'];
        // The shared router keeps the previous routes, without the module's
        Services::resetSingle('routes');
        Services::resetSingle('router');

        $registrar = $this->registrar('Items');
        $config    = config('AuthGroups');

        $config->permissions += $registrar::AuthGroups()['permissions'];
        $config->moduleGrants = $registrar::AuthGroups()['moduleGrants'];
        Factories::injectMock('config', 'AuthGroups', $config);
        command('bonfire:permissions');
    }
}
