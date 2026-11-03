<?php

namespace Tests\Commands;

use Bonfire\Assets\Config\Assets;
use Bonfire\Auth\Config\Auth;
use Bonfire\Auth\Config\AuthGroups;
use Bonfire\Auth\Config\AuthToken;
use Bonfire\Commands\Install\ContentEdits;
use Bonfire\Config\Alerts;
use Bonfire\Config\Bonfire;
use Bonfire\Config\Site;
use Bonfire\Config\Themes;
use Bonfire\Consent\Config\Consent;
use Bonfire\Content\Config\Content;
use Bonfire\Dashboard\Config\Dashboard;
use Bonfire\Privacy\Config\Privacy;
use Bonfire\Recycler\Config\Recycler;
use Bonfire\Users\Config\Users;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\Support\TestCase;

/**
 * The text edits `bf:install` makes, against the text of the framework
 * templates they edit.
 *
 * @internal
 */
final class InstallContentEditsTest extends TestCase
{
    private const ENV = <<<'ENV_WRAP'
        #--------------------------------------------------------------------
        # Example Environment Configuration file
        #--------------------------------------------------------------------

        # CI_ENVIRONMENT = production

        # app.baseURL = ''
        # app_baseURL = ''

        # database.default.hostname = localhost
        # database.default.database = ci4
        # database.default.DBPrefix =
        # database.default.port = 3306

        # encryption.key =

        ENV_WRAP;

    // --- replaceText ---

    public function testReplaceTextReplacesTheText(): void
    {
        $edit = ContentEdits::replaceText("a = 'cookie';\n", "'cookie'", "'session'");

        $this->assertTrue($edit->complete());
        $this->assertTrue($edit->changed());
        $this->assertSame("a = 'session';\n", $edit->content);
    }

    public function testReplaceTextReportsTextItCannotFind(): void
    {
        $edit = ContentEdits::replaceText("a = 'array';\n", "'cookie'", "'session'");

        $this->assertFalse($edit->complete());
        $this->assertFalse($edit->changed());
        $this->assertSame(["'cookie'"], $edit->missing);
    }

    public function testReplaceTextIsAFineNoOpWhenAlreadyApplied(): void
    {
        $edit = ContentEdits::replaceText("a = 'session';\n", "'cookie'", "'session'");

        $this->assertTrue($edit->complete());
        $this->assertFalse($edit->changed());
    }

    public function testReplaceTextIgnoresTheReplacementTurningUpInAComment(): void
    {
        $edit = ContentEdits::replaceText("// \$p = 'session';\n\$p = 'cookie';\n", "\$p = 'cookie';", "\$p = 'session';");

        $this->assertSame("// \$p = 'session';\n\$p = 'session';\n", $edit->content);
    }

    public function testReplaceTextDoesNotRepeatAnEditWhoseResultContainsTheOriginal(): void
    {
        $find    = '# database.default.port = 3306';
        $replace = $find . "\ndatabase.default.foreignKeys = true";

        $once  = ContentEdits::replaceText(self::ENV, $find, $replace);
        $twice = ContentEdits::replaceText($once->content, $find, $replace);

        $this->assertTrue($once->changed());
        $this->assertFalse($twice->changed());
        $this->assertSame(1, substr_count($twice->content, 'database.default.foreignKeys'));
    }

    // --- setEnv ---

    public function testSetEnvUncommentsTheKeyWithTheValue(): void
    {
        $edit = ContentEdits::setEnv(self::ENV, 'database.default.hostname', 'db.test');

        $this->assertTrue($edit->changed());
        $this->assertStringContainsString("\ndatabase.default.hostname = db.test\n", $edit->content);
        $this->assertStringNotContainsString('# database.default.hostname', $edit->content);
        $this->assertSame(self::ENV, str_replace('database.default.hostname = db.test', '# database.default.hostname = localhost', $edit->content));
    }

    public function testSetEnvFindsAKeyWhateverItsCommentedValue(): void
    {
        $env = "# database.default.DBDriver = Postgre\n";

        $this->assertSame("database.default.DBDriver = SQLite3\n", ContentEdits::setEnv($env, 'database.default.DBDriver', 'SQLite3')->content);
    }

    public function testSetEnvFillsAKeyWithNoValue(): void
    {
        $edit = ContentEdits::setEnv(self::ENV, 'database.default.DBPrefix', 'bf_');

        $this->assertStringContainsString("\ndatabase.default.DBPrefix = bf_\n", $edit->content);
    }

    public function testSetEnvKeepsTheValueOfAKeyThatIsSetAlready(): void
    {
        $env = "encryption.key = hex2bin:abc\n# encryption.key =\n";

        $edit = ContentEdits::setEnv($env, 'encryption.key', 'hex2bin:new');

        $this->assertTrue($edit->complete());
        $this->assertFalse($edit->changed());
    }

    public function testSetEnvKeepsTheValueWhenTheTemplateLineIsGone(): void
    {
        $edit = ContentEdits::setEnv("encryption.key = hex2bin:abc\n", 'encryption.key', 'hex2bin:new');

        $this->assertTrue($edit->complete());
        $this->assertSame("encryption.key = hex2bin:abc\n", $edit->content);
    }

    public function testSetEnvReportsAKeyTheTemplateDoesNotHave(): void
    {
        $edit = ContentEdits::setEnv("# some.other = 1\n", 'database.default.DBDriver', 'SQLite3');

        $this->assertFalse($edit->complete());
        $this->assertFalse($edit->changed());
        $this->assertCount(1, $edit->missing);
    }

    public function testSetEnvDoesNotMatchAKeyThatOnlySharesAPrefix(): void
    {
        $edit = ContentEdits::setEnv("# app_baseURL = ''\n", 'app.baseURL', "'http://x'");

        $this->assertFalse($edit->complete());
    }

    public function testSetEnvKeepsDollarSignsInTheValue(): void
    {
        $edit = ContentEdits::setEnv(self::ENV, 'database.default.hostname', 'pa$$word$1');

        $this->assertStringContainsString("database.default.hostname = pa\$\$word\$1\n", $edit->content);
    }

    public function testSetEnvKeepsCrlfLineEndings(): void
    {
        $edit = ContentEdits::setEnv("# app.baseURL = ''\r\n# other = 1\r\n", 'app.baseURL', "'http://x'");

        $this->assertSame("app.baseURL = 'http://x'\r\n# other = 1\r\n", $edit->content);
    }

    // --- removeIndexPage ---

    public function testRemoveIndexPageAddsTheSettingAboveTheBaseUrl(): void
    {
        $env = "CI_ENVIRONMENT = development\napp.baseURL = 'http://localhost:8080'\n";

        $edit = ContentEdits::removeIndexPage($env);

        $this->assertSame(
            "CI_ENVIRONMENT = development\napp.indexPage = ''" . PHP_EOL . "app.baseURL = 'http://localhost:8080'\n",
            $edit->content,
        );
    }

    public function testRemoveIndexPageKeepsTheTrailingNewline(): void
    {
        $edit = ContentEdits::removeIndexPage("app.baseURL = 'http://x'\n");

        $this->assertStringEndsWith("\n", $edit->content);
    }

    public function testRemoveIndexPageRunsOnlyOnce(): void
    {
        $once  = ContentEdits::removeIndexPage("app.baseURL = 'http://x'\n");
        $twice = ContentEdits::removeIndexPage($once->content);

        $this->assertFalse($twice->changed());
        $this->assertSame(1, substr_count($twice->content, 'app.indexPage'));
    }

    public function testRemoveIndexPageReportsAMissingBaseUrl(): void
    {
        $edit = ContentEdits::removeIndexPage("# app.baseURL = ''\n");

        $this->assertFalse($edit->complete());
        $this->assertFalse($edit->changed());
    }

    #[DataProvider('provideSqliteDatabaseConfigAddsTheOptionsAfterThePort')]
    public function testSqliteDatabaseConfigAddsTheOptionsAfterThePort(string $config): void
    {
        $edit = ContentEdits::sqliteDatabaseConfig($config);

        $this->assertTrue($edit->complete());
        $this->assertTrue($edit->changed());
        $this->assertMatchesRegularExpression("/'port'[^\\n]*\\n\\s*'foreignKeys' => true,\\R\\s*'busyTimeout' => 1000,/", $edit->content);
    }

    // --- sqliteDatabaseConfig ---

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideSqliteDatabaseConfigAddsTheOptionsAfterThePort(): iterable
    {
        yield 'wide alignment (CodeIgniter 4.7)' => [<<<'PHP'
            public array $default = [
                'failover'     => [],
                'port'         => 3306,
                'numberNative' => false,
            ];
            PHP];

        yield 'narrow alignment, port last (older)' => [<<<'PHP'
            public $default = [
                'failover' => [],
                'port'     => 3306,
            ];
            PHP];

        yield 'windows line endings' => ["public \$default = [\r\n    'port' => 3306,\r\n    'x' => 1,\r\n];\r\n"];
    }

    public function testSqliteDatabaseConfigIsValidPhpAndRunsOnlyOnce(): void
    {
        $config = "<?php\nreturn [\n    'port' => 3306,\n    'x' => 1,\n];\n";

        $once  = ContentEdits::sqliteDatabaseConfig($config);
        $twice = ContentEdits::sqliteDatabaseConfig($once->content);

        $this->assertFalse($twice->changed());
        $this->assertSame(
            ['port' => 3306, 'foreignKeys' => true, 'busyTimeout' => 1000, 'x' => 1],
            eval('?>' . $once->content),
        );
    }

    public function testSqliteDatabaseConfigEditsTheDefaultConnectionNotTheTestsOne(): void
    {
        $config = (string) file_get_contents(CIPATH . 'app/Config/Database.php');

        $once = ContentEdits::sqliteDatabaseConfig($config);

        $this->assertTrue($once->changed());
        $this->assertStringContainsString("'foreignKeys' => true,\n        'busyTimeout' => 1000,\n        'numberNative'", $once->content);
        $this->assertFalse(ContentEdits::sqliteDatabaseConfig($once->content)->changed());
    }

    public function testSqliteDatabaseConfigIgnoresTheCommentedSample(): void
    {
        $config = "    //        'foreignKeys' => true,\n    'port'  => 3306,\n";

        $this->assertTrue(ContentEdits::sqliteDatabaseConfig($config)->changed());
    }

    public function testSqliteDatabaseConfigReportsAConfigWithoutAPort(): void
    {
        $edit = ContentEdits::sqliteDatabaseConfig("public array \$default = [];\n");

        $this->assertFalse($edit->complete());
        $this->assertFalse($edit->changed());
    }

    #[DataProvider('provideAutoloadHelpersSetsTheList')]
    public function testAutoloadHelpersSetsTheList(string $content): void
    {
        $edit = ContentEdits::autoloadHelpers($content, ['url', 'auth', 'setting']);

        $this->assertTrue($edit->complete());
        $this->assertTrue($edit->changed());
        $this->assertStringContainsString("\$helpers = ['url', 'auth', 'setting'];", $edit->content);
        $this->assertStringStartsWith("class Autoload\n{\n", $edit->content);
        $this->assertStringEndsWith("\n}\n", $edit->content);
    }

    // --- autoloadHelpers ---

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAutoloadHelpersSetsTheList(): iterable
    {
        yield 'untyped, empty (CodeIgniter 4.7)' => ["class Autoload\n{\n    public \$helpers = [];\n}\n"];

        yield 'typed property' => ["class Autoload\n{\n    public array \$helpers = ['url'];\n}\n"];

        yield 'tab indented' => ["class Autoload\n{\n\tpublic \$helpers = ['url'];\n}\n"];

        yield 'array over several lines' => ["class Autoload\n{\n    public \$helpers = [\n        'url',\n        'form',\n    ];\n}\n"];
    }

    public function testAutoloadHelpersIsAFineNoOpWhenTheListIsThere(): void
    {
        $edit = ContentEdits::autoloadHelpers("    public \$helpers = ['auth', 'setting'];\n", ['auth', 'setting']);

        $this->assertTrue($edit->complete());
        $this->assertFalse($edit->changed());
    }

    public function testAutoloadHelpersIgnoresTheDocCommentExample(): void
    {
        $content = "    /**\n     *   \$helpers = [\n     *       'form',\n     *   ];\n     */\n    public \$helpers = [];\n";

        $edit = ContentEdits::autoloadHelpers($content, ['auth']);

        $this->assertStringStartsWith("    /**\n     *   \$helpers = [\n", $edit->content);
        $this->assertStringEndsWith("public \$helpers = ['auth'];\n", $edit->content);
    }

    public function testAutoloadHelpersReportsAConfigItDoesNotRecognise(): void
    {
        $edit = ContentEdits::autoloadHelpers("class Autoload\n{\n    public \$other = [];\n}\n", ['auth']);

        $this->assertFalse($edit->complete());
        $this->assertFalse($edit->changed());
    }

    // --- composerUpdateScript ---

    public function testComposerUpdateScriptAddsTheCommand(): void
    {
        $edit = ContentEdits::composerUpdateScript('{"name": "a/b", "scripts": {"test": "phpunit"}}', 'php spark x');

        $this->assertTrue($edit->changed());
        $this->assertSame(
            ['name' => 'a/b', 'scripts' => ['test' => 'phpunit', 'post-update-cmd' => ['php spark x']]],
            json_decode($edit->content, true),
        );
        $this->assertStringEndsWith("\n", $edit->content);
    }

    public function testComposerUpdateScriptCreatesTheScriptsSection(): void
    {
        $edit = ContentEdits::composerUpdateScript('{"name": "a/b"}', 'php spark x');

        $this->assertSame(['php spark x'], json_decode($edit->content, true)['scripts']['post-update-cmd']);
    }

    public function testComposerUpdateScriptAppendsToExistingCommands(): void
    {
        $edit = ContentEdits::composerUpdateScript('{"scripts": {"post-update-cmd": ["@php a"]}}', 'php spark x');

        $this->assertSame(['@php a', 'php spark x'], json_decode($edit->content, true)['scripts']['post-update-cmd']);
    }

    public function testComposerUpdateScriptKeepsASingleCommandGivenAsAString(): void
    {
        $edit = ContentEdits::composerUpdateScript('{"scripts": {"post-update-cmd": "@php a"}}', 'php spark x');

        $this->assertSame(['@php a', 'php spark x'], json_decode($edit->content, true)['scripts']['post-update-cmd']);
    }

    public function testComposerUpdateScriptLeavesTheFileAloneWhenTheCommandIsThere(): void
    {
        $json = '{"scripts":{"post-update-cmd":["php spark x"]}}';

        $edit = ContentEdits::composerUpdateScript($json, 'php spark x');

        $this->assertTrue($edit->complete());
        $this->assertFalse($edit->changed());
        $this->assertSame($json, $edit->content);
    }

    public function testComposerUpdateScriptKeepsEmptyObjectsAndSlashes(): void
    {
        $edit = ContentEdits::composerUpdateScript('{"extra": {}, "homepage": "https://a.test/b", "authors": [{"name": "Zoë"}]}', 'php spark x');

        $this->assertStringContainsString('"extra": {}', $edit->content);
        $this->assertStringContainsString('https://a.test/b', $edit->content);
        $this->assertStringContainsString('Zoë', $edit->content);
    }

    public function testComposerUpdateScriptReportsInvalidJson(): void
    {
        $edit = ContentEdits::composerUpdateScript('{"scripts": ', 'php spark x');

        $this->assertFalse($edit->complete());
        $this->assertSame('{"scripts": ', $edit->content);
    }

    public function testComposerUpdateScriptReportsJsonThatIsNotAnObject(): void
    {
        $this->assertFalse(ContentEdits::composerUpdateScript('[1, 2]', 'php spark x')->complete());
        $this->assertFalse(ContentEdits::composerUpdateScript('{"scripts": "oops"}', 'php spark x')->complete());
    }

    #[DataProvider('providePublishedConfigRewritesEveryConfigBonfirePublishes')]
    public function testPublishedConfigRewritesEveryConfigBonfirePublishes(string $className): void
    {
        $source = file_get_contents((new ReflectionClass($className))->getFileName());
        $short  = substr(strrchr($className, '\\'), 1);

        $edit = ContentEdits::publishedConfig($source, $className);

        $this->assertSame([], $edit->missing);
        $this->assertStringContainsString('namespace Config;', $edit->content);

        if (preg_match("/^class {$short} extends /m", $source) === 1) {
            $this->assertMatchesRegularExpression("/^class {$short} extends Bonfire{$short}\\b/m", $edit->content);
            $this->assertStringContainsString("use {$className} as Bonfire{$short};", $edit->content);
        }
    }

    // --- publishedConfig ---

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function providePublishedConfigRewritesEveryConfigBonfirePublishes(): iterable
    {
        foreach ([
            Alerts::class, Assets::class, Auth::class, AuthGroups::class, AuthToken::class, Bonfire::class, Site::class,
            Themes::class, Consent::class, Content::class, Dashboard::class, Privacy::class, Recycler::class, Users::class,
        ] as $class) {
            yield $class => [$class];
        }
    }

    public function testPublishedConfigPointsAssetsAtTheAppsThemes(): void
    {
        $edit = ContentEdits::publishedConfig(file_get_contents((new ReflectionClass(Assets::class))->getFileName()), Assets::class);

        $this->assertStringNotContainsString("__DIR__ . '/../../../themes", $edit->content);
        $this->assertStringContainsString("ROOTPATH . '/themes/Admin'", $edit->content);
    }

    public function testPublishedConfigReportsAClassThatNoLongerExtendsWhatItExpects(): void
    {
        $source = "<?php\n\nnamespace Bonfire\\Auth\\Config;\n\nuse Vendor\\NewAuth;\n\nclass Auth extends NewAuth\n{\n}\n";

        $edit = ContentEdits::publishedConfig($source, Auth::class);

        $this->assertFalse($edit->complete());
        $this->assertContains('class Auth extends BonfireAuth', $edit->missing);
    }

    public function testPublishedConfigReportsAnAssetsFileWithoutTheThemeFolders(): void
    {
        $source = "<?php\n\nnamespace Bonfire\\Assets\\Config;\n\nuse CodeIgniter\\Config\\BaseConfig;\n\nclass Assets extends BaseConfig\n{\n}\n";

        $edit = ContentEdits::publishedConfig($source, Assets::class);

        $this->assertContains("__DIR__ . '/../../../themes", $edit->missing);
    }

    // --- the framework installed here ---

    /**
     * What `composer update` would pull in is what the installer meets next.
     * Every edit has to find its text in the framework's own templates.
     */
    public function testEveryEditFindsItsTextInTheInstalledFramework(): void
    {
        $env = (string) file_get_contents(CIPATH . 'env');

        foreach ([
            'CI_ENVIRONMENT', 'app.baseURL', 'encryption.key', 'database.default.DBDriver', 'database.default.database',
            'database.default.hostname', 'database.default.username', 'database.default.password', 'database.default.DBPrefix',
        ] as $key) {
            $edit = ContentEdits::setEnv($env, $key, 'x');

            $this->assertSame([], $edit->missing, $key);
            $this->assertTrue($edit->changed(), $key);
        }

        $edit = ContentEdits::replaceText($env, '# database.default.port = 3306', "# database.default.port = 3306\ndatabase.default.foreignKeys = true");
        $this->assertSame([], $edit->missing);

        $this->assertSame([], ContentEdits::removeIndexPage("app.baseURL = 'x'\n")->missing);

        $database = ContentEdits::sqliteDatabaseConfig((string) file_get_contents(CIPATH . 'app/Config/Database.php'));
        $this->assertSame([], $database->missing);
        $this->assertTrue($database->changed());

        $autoload = ContentEdits::autoloadHelpers((string) file_get_contents(CIPATH . 'app/Config/Autoload.php'), ['auth']);
        $this->assertSame([], $autoload->missing);
        $this->assertTrue($autoload->changed());

        $security = ContentEdits::replaceText(
            (string) file_get_contents(CIPATH . 'app/Config/Security.php'),
            '$csrfProtection = \'cookie\';',
            '$csrfProtection = \'session\';',
        );
        $this->assertSame([], $security->missing);
        $this->assertTrue($security->changed());
    }
}
