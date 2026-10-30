<?php

namespace Tests\Commands;

use Bonfire\Commands\Generators\ModuleGenerator;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\StreamFilterTrait;
use Config\Database;
use ParseError;
use Tests\Support\Models\ArrayArticleModel;
use Tests\Support\Models\ArticleModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ModuleGeneratorTest extends TestCase
{
    use StreamFilterTrait;

    protected $refresh = true;

    /**
     * One folder for every test: PHP can't load a generated class a second
     * time from another path.
     */
    private static string $folder;

    private string $root;

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

        helper('filesystem');

        $this->root = self::$folder;
        mkdir($this->root . 'Modules', 0777, true);
        service('autoloader')->addNamespace('Tests\Generated', $this->root . 'Modules');
    }

    protected function tearDown(): void
    {
        Database::forge()->dropTable('articles', true);
        // Bonfire registers each module it discovers as a namespace of its own
        service('autoloader')->removeNamespace('Tests\Generated\Articles');
        service('autoloader')->removeNamespace('Tests\Generated');
        delete_files($this->root, true, true);

        parent::tearDown();
    }

    public function testWritesTheModuleAndItsTest(): void
    {
        $this->assertSame(0, $this->generate());

        $module = $this->root . 'Modules/Articles/';

        foreach ([
            'Module.php',
            'Config/Routes.php',
            'Config/Registrar.php',
            'Controllers/ArticleController.php',
            'Actions/DeleteArticles.php',
            'Views/list.php',
            'Views/_table.php',
            'Views/form.php',
        ] as $file) {
            $this->assertFileExists($module . $file);
        }

        $this->assertFileExists($this->root . 'tests/feature/ArticlesModuleTest.php');
        $this->assertFileExists($this->root . 'tests/_support/AdminTestCase.php');
    }

    public function testGeneratedPhpIsValid(): void
    {
        $this->generate();

        foreach (get_filenames($this->root, true, false, false) as $file) {
            try {
                token_get_all(file_get_contents($file), TOKEN_PARSE);
            } catch (ParseError $e) {
                $this->fail($file . ': ' . $e->getMessage());
            }
        }

        $this->assertNotEmpty(get_filenames($this->root, true, false, false));
    }

    public function testRefusesToOverwriteWithoutForce(): void
    {
        $this->generate();
        file_put_contents($this->root . 'Modules/Articles/Module.php', 'mine');

        $this->assertSame(1, $this->generate());
        $this->assertSame('mine', file_get_contents($this->root . 'Modules/Articles/Module.php'));

        $this->assertSame(0, $this->generate(['force' => null]));
        $this->assertNotSame('mine', file_get_contents($this->root . 'Modules/Articles/Module.php'));
    }

    public function testKeepsAnExistingAdminTestCase(): void
    {
        mkdir($this->root . 'tests/_support', 0777, true);
        file_put_contents($this->root . 'tests/_support/AdminTestCase.php', 'mine');

        $this->assertSame(0, $this->generate());
        $this->assertSame('mine', file_get_contents($this->root . 'tests/_support/AdminTestCase.php'));
    }

    public function testRefusesAMissingModel(): void
    {
        $this->assertSame(1, $this->generate(['model' => 'Tests\Support\Models\NoSuchModel']));
        $this->assertDirectoryDoesNotExist($this->root . 'Modules/Articles');
    }

    public function testRefusesAModelThatReturnsArrays(): void
    {
        $this->assertSame(1, $this->generate(['model' => ArrayArticleModel::class]));
        $this->assertDirectoryDoesNotExist($this->root . 'Modules/Articles');
    }

    public function testRefusesANamespaceWithNoFolder(): void
    {
        $this->assertSame(1, $this->generate(['namespace' => 'Nowhere\Articles']));
    }

    public function testRegistrarGrantsTheModulePermissions(): void
    {
        $this->generate();

        $registrar = 'Tests\Generated\Articles\Config\Registrar';
        $config    = $registrar::AuthGroups();

        $this->assertSame(
            ['articles.list', 'articles.create', 'articles.edit', 'articles.delete'],
            array_keys($config['permissions']),
        );
        $this->assertSame(['articles.*'], $config['moduleGrants']['articles']['superadmin']);
    }

    public function testTheGeneratedModuleListsCreatesEditsAndDeletes(): void
    {
        $this->generate();
        $this->bootGeneratedModule();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(ADMIN_AREA . '/articles')->assertOK();
        $this->actingAs($admin)->get(ADMIN_AREA . '/articles/new')->assertOK();

        $this->actingAs($admin)
            ->post(ADMIN_AREA . '/articles/save', [csrf_token() => csrf_hash(), 'title' => 'First', 'summary' => 'One'])
            ->assertRedirect();
        $article = model(ArticleModel::class)->first();
        $this->assertSame('First', $article->title);

        $this->actingAs($admin)->get(ADMIN_AREA . '/articles/' . $article->id)->assertOK();
        $this->actingAs($admin)
            ->post(ADMIN_AREA . '/articles/' . $article->id . '/save', [csrf_token() => csrf_hash(), 'title' => 'Renamed', 'summary' => 'One'])
            ->assertRedirect();
        $this->assertSame('Renamed', model(ArticleModel::class)->find($article->id)->title);

        $list = $this->actingAs($admin)->withHeaders(['HX-Request' => 'true'])->get(ADMIN_AREA . '/articles?search=Ren');
        $list->assertSee('Renamed');
        $list->assertDontSeeElement('input[name=search]');

        $this->actingAs($admin)
            ->post(ADMIN_AREA . '/actions/articles/delete-articles', [csrf_token() => csrf_hash(), 'selects' => [$article->id], 'confirmed' => '1'])
            ->assertRedirect();
        $this->assertNull(model(ArticleModel::class)->find($article->id));
        $this->assertNotNull(model(ArticleModel::class)->withDeleted()->find($article->id));
    }

    public function testTheListAndFormShowEmptyColumns(): void
    {
        $this->generate();
        $this->bootGeneratedModule();
        $id    = model(ArticleModel::class)->insert(['title' => 'No summary', 'summary' => null]);
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(ADMIN_AREA . '/articles')->assertSee('No summary');
        $this->actingAs($admin)->get(ADMIN_AREA . '/articles/' . $id)->assertOK();
    }

    public function testInvalidInputIsSentBackWithTheModelErrors(): void
    {
        $this->generate();
        $this->bootGeneratedModule();

        $response = $this->actingAs($this->createAdmin())
            ->post(ADMIN_AREA . '/articles/save', [csrf_token() => csrf_hash(), 'title' => '', 'summary' => 'One']);

        $response->assertRedirect();
        $response->assertSessionHas('errors');
        $this->assertSame(0, model(ArticleModel::class)->countAllResults());
    }

    public function testUsersWithoutThePermissionAreTurnedAway(): void
    {
        $this->generate();
        $this->bootGeneratedModule();
        $user = $this->createUser();
        $user->addGroup('developer');

        $this->actingAs($user)->get(ADMIN_AREA . '/articles')->assertRedirectTo(site_url(ADMIN_AREA));
        $this->actingAs($user)->get(ADMIN_AREA . '/articles/new')->assertRedirect();
        $this->actingAs($user)
            ->post(ADMIN_AREA . '/articles/save', [csrf_token() => csrf_hash(), 'title' => 'Sneaky'])
            ->assertRedirect();
        $this->assertSame(0, model(ArticleModel::class)->countAllResults());
    }

    /**
     * Runs make:module for ArticleModel into the temp folder.
     */
    private function generate(array $options = []): int
    {
        $command = new class (service('logger'), service('commands')) extends ModuleGenerator {
            public string $root;

            protected function rootPath(): string
            {
                return $this->root;
            }
        };
        $command->root = $this->root;

        return $command->run(['Articles', 'model' => ArticleModel::class, 'namespace' => 'Tests\Generated\Articles', ...$options]);
    }

    /**
     * Does what an install does once it has a module: creates its table,
     * discovers it and grants its permissions.
     */
    private function bootGeneratedModule(): void
    {
        $forge = Database::forge();
        $forge->addField([
            'id'         => ['type' => 'int', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'varchar', 'constraint' => 200],
            'summary'    => ['type' => 'varchar', 'constraint' => 200, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('articles');

        config('Bonfire')->appModules = ['Tests\Generated' => $this->root . 'Modules'];
        // The shared router keeps the previous routes, without the module's
        Services::resetSingle('routes');
        Services::resetSingle('router');

        $registrar = 'Tests\Generated\Articles\Config\Registrar';
        config('AuthGroups')->permissions += $registrar::AuthGroups()['permissions'];
        config('AuthGroups')->moduleGrants = $registrar::AuthGroups()['moduleGrants'];
        command('bonfire:permissions');
    }

    private function createAdmin()
    {
        $admin = $this->createUser();
        $admin->addGroup('admin');

        return $admin;
    }
}
